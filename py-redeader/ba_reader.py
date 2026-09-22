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
from dataclasses import asdict, dataclass
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
    application_url: str | None
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
        application_url=_optional_https_url(row.get("externeURL")),
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


def collect(query: str, city: str, pages: int, delay: float) -> list[Opportunity]:
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

    return list(collected.values())


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


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Read public Ausbildung listings from Bundesagentur Jobsuche."
    )
    parser.add_argument("--query", default="Ausbildung", help="Search keyword")
    parser.add_argument("--city", default="Berlin", help="City or region")
    parser.add_argument("--pages", type=int, default=1, choices=range(1, 6))
    parser.add_argument("--delay", type=float, default=1.5, help="Seconds between pages")
    parser.add_argument(
        "--output",
        type=Path,
        default=Path("storage/app/private/imports/ba-opportunities.json"),
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    try:
        opportunities = collect(args.query, args.city, args.pages, max(args.delay, 0))
        write_json(args.output, opportunities, args.query, args.city)
    except CollectorError as error:
        print(f"Collector error: {error}", file=sys.stderr)
        return 1

    print(f"Saved {len(opportunities)} opportunities to {args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
