#!/usr/bin/env python3
"""Read Ausbildung listings from the public BA Jobsuche result page.

This first-stage collector only reads public data and writes a local JSON file.
It does not connect to the Laravel database or send data to any API.
"""

from __future__ import annotations

import argparse
import html
import json
import sys
import time
from dataclasses import asdict, dataclass, replace
from datetime import datetime, timezone
from pathlib import Path
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import Request, urlopen


BASE_URL = "https://www.arbeitsagentur.de/jobsuche/suche"
USER_AGENT = "AusbildungMatchCollector/0.1 (local development test)"
MAX_RESPONSE_BYTES = 8_000_000


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
    application_url: str | None
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
        application_url=_optional_https_url(row.get("externeURL")),
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

    address = _first_dict(contact, "adresse", "address")
    contact_address = _format_address(address)
    application_url = (
        _optional_https_url(detail.get("externeURL"))
        or _optional_https_url(_first_value(detail, "bewerbung.url", "bewerbungsUrl"))
        or opportunity.application_url
    )

    return replace(
        opportunity,
        description_de=_optional_string(detail.get("stellenangebotsBeschreibung")),
        education_requirement=_optional_string(detail.get("geforderterBildungsabschluss")),
        contact_name=contact_name,
        contact_email=_optional_email(contact_email),
        contact_phone=_optional_string(contact_phone),
        contact_address=contact_address,
        application_url=application_url,
        start_date=_optional_string((detail.get("eintrittszeitraum") or {}).get("von"))
        or opportunity.start_date,
    )


def collect(
    query: str,
    city: str,
    pages: int,
    delay: float,
    include_details: bool = True,
    detail_delay: float = 1.0,
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

    return enriched


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


def _optional_email(value: Any) -> str | None:
    text = _optional_string(value)
    return text if text and "@" in text and " " not in text else None


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
        )
        write_json(args.output, opportunities, args.query, args.city)
    except CollectorError as error:
        print(f"Collector error: {error}", file=sys.stderr)
        return 1

    print(f"Saved {len(opportunities)} opportunities to {args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
