#!/usr/bin/env python3
"""Read Ausbildung listings from the public BA Jobsuche result page.

This first-stage collector only reads public data and writes a local JSON file.
It does not connect to the Laravel database or send data to any API.
"""

from __future__ import annotations

import argparse
import html
import ipaddress
import json
import re
import socket
import sys
import time
from dataclasses import asdict, dataclass, replace
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.parse import urljoin, urlencode, urlparse
from urllib.request import HTTPRedirectHandler, Request, build_opener, urlopen


BASE_URL = "https://www.arbeitsagentur.de/jobsuche/suche"
USER_AGENT = "AusbildungMatchCollector/0.1 (local development test)"
MAX_RESPONSE_BYTES = 8_000_000
MAX_COMPANY_RESPONSE_BYTES = 2_000_000
CONTACT_PAGE_WORDS = ("karriere", "career", "ausbildung", "jobs", "kontakt", "contact", "impressum")
JOB_PLATFORM_DOMAINS = {
    "arbeitsagentur.de", "gute-jobs.de", "jobs4us.de", "hogapage.de", "azubi.de",
    "studyflix.de", "indeed.com", "stepstone.de", "linkedin.com",
}


class CollectorError(RuntimeError):
    """Raised when the public result page cannot be read safely."""


@dataclass(frozen=True)
class Opportunity:
    external_id: str
    title_de: str
    employer_name: str | None
    occupation: str | None
    city: str | None
    postal_code: str | None
    state: str | None
    country: str | None
    start_date: str | None
    published_date: str | None
    updated_at: str | None
    description_de: str | None
    education_requirement: str | None
    contact_name: str | None
    contact_email: str | None
    contact_phone: str | None
    contact_address: str | None
    contact_form_url: str | None
    company_website: str | None
    contact_type: str | None
    contact_source_url: str | None
    contact_confidence: str | None
    contact_score: int | None
    contact_emails: tuple[dict[str, Any], ...]
    contact_phones: tuple[dict[str, Any], ...]
    application_url: str | None
    final_application_url: str | None
    application_redirect_count: int
    detail_url: str
    source_url: str
    source: str = "Bundesagentur für Arbeit"


def build_search_url(query: str, city: str, page: int) -> str:
    parameters = {
        "angebotsart": "4",  # Ausbildung
        "was": query,
        "wo": city,
        "page": page,
    }
    return f"{BASE_URL}?{urlencode(parameters)}"


def fetch_page(url: str, timeout: float = 30.0) -> str:
    request = Request(
        url,
        headers={
            "Accept": "text/html,application/xhtml+xml",
            "Accept-Language": "de-DE,de;q=0.9",
            "User-Agent": USER_AGENT,
        },
    )

    try:
        with urlopen(request, timeout=timeout) as response:
            content_type = response.headers.get_content_type()
            if content_type not in {"text/html", "application/xhtml+xml"}:
                raise CollectorError(f"Unexpected response type: {content_type}")

            body = response.read(MAX_RESPONSE_BYTES + 1)
    except HTTPError as error:
        raise CollectorError(f"Jobsuche returned HTTP {error.code}.") from error
    except URLError as error:
        raise CollectorError(f"Could not connect to Jobsuche: {error.reason}") from error

    if len(body) > MAX_RESPONSE_BYTES:
        raise CollectorError("Jobsuche response exceeded the 8 MB safety limit.")

    return body.decode("utf-8", errors="replace")


def extract_ng_state(document: str) -> dict[str, Any]:
    marker = '<script id="ng-state" type="application/json">'
    start = document.find(marker)
    if start == -1:
        raise CollectorError("The Jobsuche data block (ng-state) was not found.")

    start += len(marker)
    end = document.find("</script>", start)
    if end == -1:
        raise CollectorError("The Jobsuche data block is incomplete.")

    try:
        payload = json.loads(html.unescape(document[start:end]))
    except json.JSONDecodeError as error:
        raise CollectorError("The Jobsuche data block is not valid JSON.") from error

    if not isinstance(payload, dict):
        raise CollectorError("The Jobsuche data block has an unexpected structure.")

    return payload


def normalize_row(row: dict[str, Any], source_url: str) -> Opportunity | None:
    external_id = str(row.get("referenznummer") or "").strip()
    title = str(row.get("stellenangebotsTitel") or "").strip()

    if not external_id or not title:
        return None

    locations = row.get("stellenlokationen") or []
    address: dict[str, Any] = {}
    if isinstance(locations, list) and locations and isinstance(locations[0], dict):
        possible_address = locations[0].get("adresse")
        if isinstance(possible_address, dict):
            address = possible_address

    entry_period = row.get("eintrittszeitraum") or {}
    publication_period = row.get("veroeffentlichungszeitraum") or {}

    detail_url = f"https://www.arbeitsagentur.de/jobsuche/jobdetail/{external_id}"

    return Opportunity(
        external_id=external_id,
        title_de=title,
        employer_name=_optional_string(row.get("firma")),
        occupation=_optional_string(row.get("hauptberuf")),
        city=_optional_string(address.get("ort")),
        postal_code=_optional_string(address.get("plz")),
        state=_optional_string(address.get("region")),
        country=_optional_string(address.get("land")),
        start_date=_optional_string(entry_period.get("von")),
        published_date=_optional_string(
            row.get("datumErsteVeroeffentlichung") or publication_period.get("von")
        ),
        updated_at=_optional_string(row.get("aenderungsdatum")),
        description_de=None,
        education_requirement=None,
        contact_name=None,
        contact_email=None,
        contact_phone=None,
        contact_address=None,
        contact_form_url=None,
        company_website=None,
        contact_type=None,
        contact_source_url=None,
        contact_confidence=None,
        contact_score=None,
        contact_emails=(),
        contact_phones=(),
        application_url=_optional_https_url(row.get("externeURL")),
        final_application_url=None,
        application_redirect_count=0,
        detail_url=detail_url,
        source_url=source_url,
    )


def extract_opportunities(document: str, source_url: str) -> list[Opportunity]:
    state = extract_ng_state(document)
    rows = state.get("suchergebnis", {}).get("ergebnisliste", [])
    if not isinstance(rows, list):
        raise CollectorError("The Jobsuche result list has an unexpected structure.")

    opportunities: list[Opportunity] = []
    for row in rows:
        if isinstance(row, dict):
            normalized = normalize_row(row, source_url)
            if normalized is not None:
                opportunities.append(normalized)

    return opportunities


def extract_job_detail(document: str) -> dict[str, Any]:
    state = extract_ng_state(document)
    detail = state.get("jobdetail")
    if not isinstance(detail, dict):
        raise CollectorError("The Jobsuche job detail has an unexpected structure.")
    return detail


def enrich_from_detail(opportunity: Opportunity, document: str) -> Opportunity:
    detail = extract_job_detail(document)
    description = _optional_string(detail.get("stellenangebotsBeschreibung"))
    contact = _first_dict(
        detail,
        "kontakt",
        "kontaktdaten",
        "ansprechpartner",
        "bewerbung.kontakt",
        "bewerbung.kontaktdaten",
    )

    first_name = _first_value(contact, "vorname", "firstName")
    last_name = _first_value(contact, "nachname", "familienname", "lastName")
    explicit_name = _first_value(contact, "name", "ansprechpartner", "kontaktperson")
    contact_name = explicit_name or " ".join(
        part for part in (first_name, last_name) if part
    ) or None

    contact_email = _first_value(
        contact,
        "email",
        "eMail",
        "emailadresse",
        "emailAddress",
    ) or _recursive_scalar(detail, {"email", "emailadresse", "e-mail"})
    contact_phone = _first_value(
        contact,
        "telefon",
        "telefonnummer",
        "phone",
        "phoneNumber",
    ) or _recursive_scalar(detail, {"telefon", "telefonnummer", "phone"})

    # Many BA listings publish contact data only inside the free-text description.
    contact_email = contact_email or _extract_email(description)
    contact_phone = contact_phone or _extract_phone(description)

    address = _first_dict(contact, "adresse", "address")
    contact_address = _format_address(address)
    application_url = (
        _optional_https_url(detail.get("externeURL"))
        or _optional_https_url(_first_value(detail, "bewerbung.url", "bewerbungsUrl"))
        or opportunity.application_url
    )
    company_website = _company_website(detail.get("allianzpartnerUrl"))
    contact_type = "recruiting" if contact_email or contact_phone else None
    contact_source_url = opportunity.detail_url if contact_type else None
    contact_confidence = "high" if contact_type else None

    email_evidence = _contact_evidence(
        _optional_email(contact_email), opportunity.detail_url, "job_detail", 100
    )
    phone_evidence = _contact_evidence(
        _optional_string(contact_phone), opportunity.detail_url, "job_detail", 100
    )

    result = replace(
        opportunity,
        description_de=description,
        education_requirement=_optional_string(detail.get("geforderterBildungsabschluss")),
        contact_name=contact_name,
        contact_email=_optional_email(contact_email),
        contact_phone=_optional_string(contact_phone),
        contact_address=contact_address,
        company_website=company_website,
        contact_type=contact_type,
        contact_source_url=contact_source_url,
        contact_confidence=contact_confidence,
        contact_score=100 if contact_type else None,
        contact_emails=(email_evidence,) if email_evidence else (),
        contact_phones=(phone_evidence,) if phone_evidence else (),
        application_url=application_url,
        start_date=_optional_string((detail.get("eintrittszeitraum") or {}).get("von"))
        or opportunity.start_date,
    )
    return _merge_jsonld_contacts(result, document, opportunity.detail_url)


class _ContactHtmlParser(HTMLParser):
    def __init__(self, page_url: str) -> None:
        super().__init__(convert_charrefs=True)
        self.page_url = page_url
        self.links: list[str] = []
        self.emails: list[str] = []
        self.phones: list[str] = []
        self.text_parts: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        attributes = dict(attrs)
        href = attributes.get("href")
        if not href:
            return
        if href.lower().startswith("mailto:"):
            email = href[7:].split("?", 1)[0].strip()
            if _optional_email(email):
                self.emails.append(email)
        elif href.lower().startswith("tel:"):
            phone = _normalize_phone(href[4:])
            if phone:
                self.phones.append(phone)
        elif tag == "a":
            self.links.append(urljoin(self.page_url, href))

    def handle_data(self, data: str) -> None:
        if data.strip():
            self.text_parts.append(data.strip())

    @property
    def visible_text(self) -> str:
        return " ".join(self.text_parts)


def enrich_from_company_website(opportunity: Opportunity, delay: float = 0.7) -> Opportunity:
    base_url = opportunity.company_website
    if not base_url:
        return _ensure_contact_method(opportunity)

    candidates = [base_url]
    try:
        homepage = _fetch_company_page(base_url)
        home_parser = _parse_contact_html(homepage, base_url)
        candidates.extend(_prioritized_contact_links(home_parser.links, base_url))
    except CollectorError:
        return _ensure_contact_method(opportunity)

    # Also try conventional paths when the homepage does not expose navigation links.
    candidates.extend(urljoin(base_url.rstrip("/") + "/", path) for path in (
        "karriere", "ausbildung", "kontakt", "impressum"
    ))

    seen: set[str] = set()
    for page_url in candidates:
        normalized = page_url.split("#", 1)[0]
        if normalized in seen or len(seen) >= 6 or not _same_site(base_url, normalized):
            continue
        seen.add(normalized)
        try:
            document = homepage if normalized == base_url else _fetch_company_page(normalized)
            parsed = _parse_contact_html(document, normalized)
        except CollectorError:
            continue

        text_email = _extract_email(parsed.visible_text)
        text_phone = _extract_phone(parsed.visible_text)
        emails = _unique_values([*parsed.emails, text_email])
        phones = _unique_values([*parsed.phones, text_phone])
        email = emails[0] if emails else None
        phone = phones[0] if phones else None
        form_url = _find_contact_form(parsed.links, normalized)
        page_type, confidence, score = _contact_page_type(normalized)

        if email or phone or form_url:
            existing_emails = list(opportunity.contact_emails)
            existing_phones = list(opportunity.contact_phones)
            existing_emails.extend(
                item for value in emails
                if (item := _contact_evidence(value, normalized, page_type, score))
            )
            existing_phones.extend(
                item for value in phones
                if (item := _contact_evidence(value, normalized, page_type, score))
            )
            result = replace(
                opportunity,
                contact_email=opportunity.contact_email or email,
                contact_phone=opportunity.contact_phone or phone,
                contact_form_url=form_url,
                contact_type=opportunity.contact_type or page_type,
                contact_source_url=opportunity.contact_source_url or normalized,
                contact_confidence=opportunity.contact_confidence or confidence,
                contact_score=opportunity.contact_score or score,
                contact_emails=_dedupe_evidence(existing_emails),
                contact_phones=_dedupe_evidence(existing_phones),
            )
            return _merge_jsonld_contacts(result, document, normalized)
        if delay > 0:
            time.sleep(delay)

    return _ensure_contact_method(opportunity)


def _ensure_contact_method(opportunity: Opportunity) -> Opportunity:
    if opportunity.contact_email or opportunity.contact_phone or opportunity.contact_form_url:
        return opportunity
    if opportunity.application_url:
        return replace(
            opportunity,
            contact_form_url=opportunity.application_url,
            contact_type="application",
            contact_source_url=opportunity.application_url,
            contact_confidence="high",
            contact_score=80,
        )
    return opportunity


def collect(
    query: str,
    city: str,
    pages: int,
    delay: float,
    include_details: bool = True,
    detail_delay: float = 1.0,
    include_company_contacts: bool = False,
    company_delay: float = 0.7,
) -> list[Opportunity]:
    collected: dict[str, Opportunity] = {}

    for page in range(1, pages + 1):
        url = build_search_url(query, city, page)
        document = fetch_page(url)
        page_rows = extract_opportunities(document, url)

        for opportunity in page_rows:
            collected[opportunity.external_id] = opportunity

        print(f"Page {page}: read {len(page_rows)} rows", file=sys.stderr)
        if page < pages and delay > 0:
            time.sleep(delay)

    opportunities = list(collected.values())
    if not include_details:
        return opportunities

    enriched: list[Opportunity] = []
    for index, opportunity in enumerate(opportunities, start=1):
        try:
            detail_document = fetch_page(opportunity.detail_url)
            enriched.append(enrich_from_detail(opportunity, detail_document))
            print(
                f"Detail {index}/{len(opportunities)}: {opportunity.external_id}",
                file=sys.stderr,
            )
        except CollectorError as error:
            print(
                f"Detail {index}/{len(opportunities)} skipped: {error}",
                file=sys.stderr,
            )
            enriched.append(opportunity)

        if index < len(opportunities) and detail_delay > 0:
            time.sleep(detail_delay)

    if not include_company_contacts:
        return [_ensure_contact_method(item) for item in enriched]

    company_enriched: list[Opportunity] = []
    company_cache: dict[str, Opportunity] = {}
    for index, opportunity in enumerate(enriched, start=1):
        opportunity = _resolve_application_destination(opportunity)
        if opportunity.contact_email or opportunity.contact_phone:
            company_enriched.append(_ensure_contact_method(opportunity))
            continue
        cache_key = opportunity.company_website or ""
        if cache_key and cache_key in company_cache:
            company_enriched.append(_copy_company_contact(opportunity, company_cache[cache_key]))
            continue
        result = enrich_from_company_website(opportunity, company_delay)
        company_enriched.append(result)
        if cache_key:
            company_cache[cache_key] = result
        print(
            f"Company contact {index}/{len(enriched)}: {opportunity.employer_name or opportunity.external_id}",
            file=sys.stderr,
        )
    return company_enriched


def write_json(path: Path, opportunities: list[Opportunity], query: str, city: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    result = {
        "source": "Bundesagentur für Arbeit – Jobsuche",
        "collected_at": datetime.now(timezone.utc).isoformat(),
        "query": {"keyword": query, "city": city},
        "count": len(opportunities),
        "opportunities": [asdict(item) for item in opportunities],
    }
    path.write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")


def _optional_string(value: Any) -> str | None:
    if value is None:
        return None
    text = str(value).strip()
    return text or None


def _optional_https_url(value: Any) -> str | None:
    text = _optional_string(value)
    return text if text and text.startswith("https://") else None


def _company_website(value: Any) -> str | None:
    text = _optional_string(value)
    if not text:
        return None
    url = text if text.startswith(("https://", "http://")) else f"https://{text.lstrip('/')}"
    parsed = urlparse(url)
    host = (parsed.hostname or "").lower().removeprefix("www.")
    if parsed.scheme not in {"http", "https"} or not host:
        return None
    if any(host == domain or host.endswith("." + domain) for domain in JOB_PLATFORM_DOMAINS):
        return None
    return f"https://{parsed.netloc}/"


def _fetch_company_page(url: str, timeout: float = 15.0) -> str:
    _assert_public_http_url(url)
    request = Request(url, headers={
        "Accept": "text/html,application/xhtml+xml",
        "Accept-Language": "de-DE,de;q=0.9",
        "User-Agent": USER_AGENT,
    })
    try:
        with urlopen(request, timeout=timeout) as response:
            final_url = response.geturl()
            _assert_public_http_url(final_url)
            content_type = response.headers.get_content_type()
            if content_type not in {"text/html", "application/xhtml+xml"}:
                raise CollectorError(f"Unsupported company page type: {content_type}")
            body = response.read(MAX_COMPANY_RESPONSE_BYTES + 1)
    except (HTTPError, URLError, TimeoutError, ValueError) as error:
        raise CollectorError(f"Could not read company page: {url}") from error
    if len(body) > MAX_COMPANY_RESPONSE_BYTES:
        raise CollectorError("Company page exceeded the 2 MB safety limit.")
    return body.decode("utf-8", errors="replace")


def _assert_public_http_url(url: str) -> None:
    parsed = urlparse(url)
    if parsed.scheme not in {"http", "https"} or not parsed.hostname or parsed.username or parsed.password:
        raise CollectorError("Company URL is not a safe public HTTP(S) URL.")
    try:
        default_port = 443 if parsed.scheme == "https" else 80
        addresses = {item[4][0] for item in socket.getaddrinfo(parsed.hostname, parsed.port or default_port)}
    except socket.gaierror as error:
        raise CollectorError("Company host could not be resolved.") from error
    for address in addresses:
        ip = ipaddress.ip_address(address)
        if not ip.is_global:
            raise CollectorError("Company URL resolves to a private or reserved network.")


def _parse_contact_html(document: str, page_url: str) -> _ContactHtmlParser:
    parser = _ContactHtmlParser(page_url)
    parser.feed(document)
    return parser


def _same_site(base_url: str, candidate_url: str) -> bool:
    base = (urlparse(base_url).hostname or "").lower().removeprefix("www.")
    candidate = (urlparse(candidate_url).hostname or "").lower().removeprefix("www.")
    return bool(base and candidate and (candidate == base or candidate.endswith("." + base)))


def _prioritized_contact_links(links: list[str], base_url: str) -> list[str]:
    unique: list[str] = []
    for link in links:
        lower = link.lower()
        if _same_site(base_url, link) and any(word in lower for word in CONTACT_PAGE_WORDS):
            clean = link.split("#", 1)[0]
            if clean not in unique:
                unique.append(clean)
    return sorted(unique, key=lambda url: _contact_page_type(url)[1] != "high")[:5]


def _find_contact_form(links: list[str], page_url: str) -> str | None:
    for link in links:
        lower = link.lower()
        if _same_site(page_url, link) and any(word in lower for word in ("bewerben", "application", "kontakt", "contact")):
            return link.split("#", 1)[0]
    return None


def _contact_page_type(url: str) -> tuple[str, str, int]:
    lower = url.lower()
    if any(word in lower for word in ("karriere", "career", "ausbildung", "jobs", "bewerben")):
        return "recruiting", "high", 90
    if any(word in lower for word in ("kontakt", "contact")):
        return "company_general", "medium", 60
    return "impressum", "low", 30


class _TrackingRedirectHandler(HTTPRedirectHandler):
    def __init__(self) -> None:
        super().__init__()
        self.count = 0

    def redirect_request(self, req: Request, fp: Any, code: int, msg: str, headers: Any, newurl: str) -> Request | None:
        self.count += 1
        return super().redirect_request(req, fp, code, msg, headers, newurl)


def _resolve_application_destination(opportunity: Opportunity) -> Opportunity:
    if not opportunity.application_url:
        return opportunity
    try:
        _assert_public_http_url(opportunity.application_url)
        tracker = _TrackingRedirectHandler()
        opener = build_opener(tracker)
        request = Request(opportunity.application_url, method="HEAD", headers={"User-Agent": USER_AGENT})
        with opener.open(request, timeout=15) as response:
            final_url = response.geturl()
        _assert_public_http_url(final_url)
        return replace(
            opportunity,
            final_application_url=final_url,
            application_redirect_count=tracker.count,
        )
    except (CollectorError, HTTPError, URLError, TimeoutError, ValueError):
        return replace(opportunity, final_application_url=opportunity.application_url)


def _copy_company_contact(opportunity: Opportunity, cached: Opportunity) -> Opportunity:
    return replace(
        opportunity,
        contact_email=cached.contact_email,
        contact_phone=cached.contact_phone,
        contact_form_url=cached.contact_form_url,
        contact_type=cached.contact_type,
        contact_source_url=cached.contact_source_url,
        contact_confidence=cached.contact_confidence,
        contact_score=cached.contact_score,
        contact_emails=cached.contact_emails,
        contact_phones=cached.contact_phones,
    )


def _optional_email(value: Any) -> str | None:
    text = _optional_string(value)
    return text if text and "@" in text and " " not in text else None


def _unique_values(values: list[str | None]) -> list[str]:
    result: list[str] = []
    for value in values:
        normalized = _optional_string(value)
        if normalized and normalized not in result:
            result.append(normalized)
    return result


def _contact_evidence(
    value: str | None, source_url: str, source_field: str, score: int
) -> dict[str, Any] | None:
    if not value:
        return None
    return {
        "value": value,
        "source_url": source_url,
        "source_field": source_field,
        "confidence_score": score,
        "extracted_at": datetime.now(timezone.utc).isoformat(),
    }


def _dedupe_evidence(items: list[dict[str, Any]]) -> tuple[dict[str, Any], ...]:
    best: dict[str, dict[str, Any]] = {}
    for item in items:
        value = str(item.get("value") or "").casefold()
        if not value:
            continue
        if value not in best or int(item.get("confidence_score") or 0) > int(best[value].get("confidence_score") or 0):
            best[value] = item
    return tuple(sorted(best.values(), key=lambda item: int(item.get("confidence_score") or 0), reverse=True))


def _merge_jsonld_contacts(opportunity: Opportunity, document: str, page_url: str) -> Opportunity:
    emails: list[dict[str, Any]] = list(opportunity.contact_emails)
    phones: list[dict[str, Any]] = list(opportunity.contact_phones)
    application_url = opportunity.application_url

    for match in re.finditer(
        r'<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>',
        document,
        flags=re.IGNORECASE | re.DOTALL,
    ):
        try:
            payload = json.loads(html.unescape(match.group(1)))
        except json.JSONDecodeError:
            continue
        for key, value in _walk_json(payload):
            key_lower = key.casefold()
            text = _optional_string(value) if not isinstance(value, (dict, list)) else None
            if not text:
                continue
            if key_lower in {"email", "emailaddress"}:
                evidence = _contact_evidence(_optional_email(text), page_url, "json_ld", 95)
                if evidence:
                    emails.append(evidence)
            elif key_lower in {"telephone", "phone", "phonenumber"}:
                evidence = _contact_evidence(_normalize_phone(text), page_url, "json_ld", 95)
                if evidence:
                    phones.append(evidence)
            elif key_lower in {"url", "applicationurl"} and "apply" in text.lower():
                application_url = _optional_https_url(urljoin(page_url, text)) or application_url

    merged_emails = _dedupe_evidence(emails)
    merged_phones = _dedupe_evidence(phones)
    return replace(
        opportunity,
        contact_emails=merged_emails,
        contact_phones=merged_phones,
        contact_email=opportunity.contact_email or (merged_emails[0]["value"] if merged_emails else None),
        contact_phone=opportunity.contact_phone or (merged_phones[0]["value"] if merged_phones else None),
        application_url=application_url,
    )


def _walk_json(value: Any) -> list[tuple[str, Any]]:
    result: list[tuple[str, Any]] = []
    if isinstance(value, dict):
        for key, child in value.items():
            result.append((str(key), child))
            result.extend(_walk_json(child))
    elif isinstance(value, list):
        for child in value:
            result.extend(_walk_json(child))
    return result


def _extract_email(text: str | None) -> str | None:
    if not text:
        return None
    match = re.search(
        r"(?<![\w.+-])([\w.!#$%&'*+/=?^`{|}~-]+@[\w-]+(?:\.[\w-]+)+)",
        text,
        flags=re.IGNORECASE,
    )
    return match.group(1).rstrip(".,;:)") if match else None


def _extract_phone(text: str | None) -> str | None:
    if not text:
        return None

    # International German numbers are reliable even without a preceding label.
    international = re.search(r"(?<!\w)(\+49[\s()/.\-]*\d(?:[\d\s()/.\-]{5,}\d))", text)
    if international:
        return _normalize_phone(international.group(1))

    # National numbers are accepted only when explicitly labelled. This prevents
    # dates such as 01.02.2027 from being interpreted as telephone numbers.
    labelled = re.search(
        r"(?:telefon|tel\.?|mobil|handy|phone)\s*(?:nummer)?\s*[:\-]?\s*"
        r"((?:0\d)[\d\s()/.\-]{5,}\d)",
        text,
        flags=re.IGNORECASE,
    )
    return _normalize_phone(labelled.group(1)) if labelled else None


def _normalize_phone(value: str) -> str | None:
    normalized = re.sub(r"\s+", " ", value).strip(" .,;:-")
    digits = re.sub(r"\D", "", normalized)
    return normalized if 7 <= len(digits) <= 15 else None


def _value_at_path(data: dict[str, Any], path: str) -> Any:
    current: Any = data
    for segment in path.split("."):
        if not isinstance(current, dict) or segment not in current:
            return None
        current = current[segment]
    return current


def _first_value(data: dict[str, Any], *paths: str) -> str | None:
    for path in paths:
        value = _optional_string(_value_at_path(data, path))
        if value:
            return value
    return None


def _first_dict(data: dict[str, Any], *paths: str) -> dict[str, Any]:
    for path in paths:
        value = _value_at_path(data, path)
        if isinstance(value, dict):
            return value
    return {}


def _recursive_scalar(data: Any, wanted_keys: set[str]) -> str | None:
    if isinstance(data, dict):
        for key, value in data.items():
            if key.casefold() in wanted_keys and not isinstance(value, (dict, list)):
                text = _optional_string(value)
                if text:
                    return text
        for value in data.values():
            found = _recursive_scalar(value, wanted_keys)
            if found:
                return found
    elif isinstance(data, list):
        for value in data:
            found = _recursive_scalar(value, wanted_keys)
            if found:
                return found
    return None


def _format_address(address: dict[str, Any]) -> str | None:
    if not address:
        return None
    street = _first_value(address, "strasse", "straße", "street")
    house_number = _first_value(address, "hausnummer", "houseNumber")
    postal_code = _first_value(address, "plz", "postalCode")
    city = _first_value(address, "ort", "city")
    line_one = " ".join(part for part in (street, house_number) if part)
    line_two = " ".join(part for part in (postal_code, city) if part)
    return ", ".join(part for part in (line_one, line_two) if part) or None


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Read public Ausbildung listings from Bundesagentur Jobsuche."
    )
    parser.add_argument("--query", default="Ausbildung", help="Search keyword")
    parser.add_argument("--city", default="Berlin", help="City or region")
    parser.add_argument("--pages", type=int, default=1, choices=range(1, 6))
    parser.add_argument("--delay", type=float, default=1.5, help="Seconds between pages")
    parser.add_argument(
        "--detail-delay",
        type=float,
        default=1.0,
        help="Seconds between job-detail requests",
    )
    parser.add_argument(
        "--skip-details",
        action="store_true",
        help="Only read the result list; do not fetch descriptions or contacts",
    )
    parser.add_argument(
        "--company-contacts",
        action="store_true",
        help="Search official company career/contact/imprint pages for public contact methods",
    )
    parser.add_argument(
        "--company-delay",
        type=float,
        default=0.7,
        help="Seconds between company website requests",
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=Path("storage/app/private/imports/ba-opportunities.json"),
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    try:
        opportunities = collect(
            args.query,
            args.city,
            args.pages,
            max(args.delay, 0),
            include_details=not args.skip_details,
            detail_delay=max(args.detail_delay, 0),
            include_company_contacts=args.company_contacts and not args.skip_details,
            company_delay=max(args.company_delay, 0),
        )
        write_json(args.output, opportunities, args.query, args.city)
    except CollectorError as error:
        print(f"Collector error: {error}", file=sys.stderr)
        return 1

    print(f"Saved {len(opportunities)} opportunities to {args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
