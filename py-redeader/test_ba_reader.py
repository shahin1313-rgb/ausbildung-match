import json
import unittest

from ba_reader import (
    CollectorError,
    _company_website,
    _find_contact_form,
    _parse_contact_html,
    enrich_from_detail,
    extract_opportunities,
)


class BaReaderTest(unittest.TestCase):
    def test_extracts_and_normalizes_jobsuche_state(self) -> None:
        state = {
            "suchergebnis": {
                "ergebnisliste": [
                    {
                        "referenznummer": "10000-TEST-1",
                        "stellenangebotsTitel": "Ausbildung Fachinformatiker/in",
                        "firma": "Beispiel GmbH",
                        "hauptberuf": "Fachinformatiker/in",
                        "stellenlokationen": [
                            {"adresse": {"plz": "10115", "ort": "Berlin", "region": "BERLIN", "land": "DEUTSCHLAND"}}
                        ],
                        "eintrittszeitraum": {"von": "2027-08-01"},
                        "datumErsteVeroeffentlichung": "2026-09-20",
                        "aenderungsdatum": "2026-09-21T10:00:00",
                        "externeURL": "https://example.org/apply/1",
                    }
                ]
            }
        }
        document = (
            '<html><script id="ng-state" type="application/json">'
            + json.dumps(state)
            + "</script></html>"
        )

        rows = extract_opportunities(document, "https://example.org/search")

        self.assertEqual(1, len(rows))
        self.assertEqual("10000-TEST-1", rows[0].external_id)
        self.assertEqual("Berlin", rows[0].city)
        self.assertEqual("https://example.org/apply/1", rows[0].application_url)

    def test_rejects_page_without_state(self) -> None:
        with self.assertRaises(CollectorError):
            extract_opportunities("<html></html>", "https://example.org/search")

    def test_enriches_description_and_contact_from_detail(self) -> None:
        list_state = {
            "suchergebnis": {
                "ergebnisliste": [
                    {
                        "referenznummer": "TEST-2",
                        "stellenangebotsTitel": "Ausbildung Test",
                        "stellenlokationen": [{"adresse": {"ort": "Berlin"}}],
                    }
                ]
            }
        }
        detail_state = {
            "jobdetail": {
                "stellenangebotsBeschreibung": "Vollständige Beschreibung",
                "geforderterBildungsabschluss": "MITTLERE_REIFE",
                "kontakt": {
                    "vorname": "Anna",
                    "nachname": "Muster",
                    "emailadresse": "anna@example.org",
                    "telefonnummer": "+49 30 123456",
                    "adresse": {
                        "strasse": "Teststraße",
                        "hausnummer": "10",
                        "plz": "10115",
                        "ort": "Berlin",
                    },
                },
                "externeURL": "https://example.org/apply",
            }
        }
        list_document = '<script id="ng-state" type="application/json">' + json.dumps(list_state) + "</script>"
        detail_document = '<script id="ng-state" type="application/json">' + json.dumps(detail_state) + "</script>"
        opportunity = extract_opportunities(list_document, "https://example.org/search")[0]

        enriched = enrich_from_detail(opportunity, detail_document)

        self.assertEqual("Anna Muster", enriched.contact_name)
        self.assertEqual("anna@example.org", enriched.contact_email)
        self.assertEqual("+49 30 123456", enriched.contact_phone)
        self.assertEqual("Teststraße 10, 10115 Berlin", enriched.contact_address)
        self.assertEqual("Vollständige Beschreibung", enriched.description_de)

    def test_extracts_email_and_international_phone_from_description(self) -> None:
        list_state = {
            "suchergebnis": {
                "ergebnisliste": [
                    {
                        "referenznummer": "TEST-3",
                        "stellenangebotsTitel": "Ausbildung Kontakt",
                        "stellenlokationen": [{"adresse": {"ort": "Berlin"}}],
                    }
                ]
            }
        }
        detail_state = {
            "jobdetail": {
                "stellenangebotsBeschreibung": (
                    "Start am 01.02.2027. Bewerbung an jobs@example.de "
                    "oder telefonisch unter +49 89 41999 038."
                )
            }
        }
        list_document = '<script id="ng-state" type="application/json">' + json.dumps(list_state) + "</script>"
        detail_document = '<script id="ng-state" type="application/json">' + json.dumps(detail_state) + "</script>"
        opportunity = extract_opportunities(list_document, "https://example.org/search")[0]

        enriched = enrich_from_detail(opportunity, detail_document)

        self.assertEqual("jobs@example.de", enriched.contact_email)
        self.assertEqual("+49 89 41999 038", enriched.contact_phone)

    def test_does_not_treat_a_date_as_a_phone_number(self) -> None:
        list_state = {
            "suchergebnis": {
                "ergebnisliste": [
                    {
                        "referenznummer": "TEST-4",
                        "stellenangebotsTitel": "Ausbildung Datum",
                        "stellenlokationen": [{"adresse": {"ort": "Berlin"}}],
                    }
                ]
            }
        }
        detail_state = {
            "jobdetail": {"stellenangebotsBeschreibung": "Die Ausbildung startet am 01.02.2027."}
        }
        list_document = '<script id="ng-state" type="application/json">' + json.dumps(list_state) + "</script>"
        detail_document = '<script id="ng-state" type="application/json">' + json.dumps(detail_state) + "</script>"
        opportunity = extract_opportunities(list_document, "https://example.org/search")[0]

        enriched = enrich_from_detail(opportunity, detail_document)

        self.assertIsNone(enriched.contact_phone)

    def test_discovers_contact_data_and_form_on_official_page(self) -> None:
        document = """
            <html><body>
                <a href="mailto:karriere@example.de">E-Mail</a>
                <a href="tel:+4930123456">Telefon</a>
                <a href="/karriere/bewerben">Jetzt bewerben</a>
            </body></html>
        """
        parsed = _parse_contact_html(document, "https://example.de/karriere")

        self.assertEqual("karriere@example.de", parsed.emails[0])
        self.assertEqual("+4930123456", parsed.phones[0])
        self.assertEqual(
            "https://example.de/karriere/bewerben",
            _find_contact_form(parsed.links, "https://example.de/karriere"),
        )

    def test_rejects_job_platform_as_company_website(self) -> None:
        self.assertIsNone(_company_website("www.gute-jobs.de"))
        self.assertEqual("https://www.example.de/", _company_website("www.example.de/karriere"))

    def test_reads_json_ld_contact_without_country_filtering(self) -> None:
        list_state = {
            "suchergebnis": {
                "ergebnisliste": [{
                    "referenznummer": "TEST-JSONLD",
                    "stellenangebotsTitel": "Ausbildung JSON-LD",
                    "stellenlokationen": [{"adresse": {"ort": "Berlin"}}],
                }]
            }
        }
        detail_state = {"jobdetail": {"stellenangebotsBeschreibung": "Beschreibung"}}
        list_document = '<script id="ng-state" type="application/json">' + json.dumps(list_state) + "</script>"
        detail_document = (
            '<script type="application/ld+json">'
            + json.dumps({
                "@type": "JobPosting",
                "applicationContact": {
                    "email": "jobs@example.de",
                    "telephone": "+31 88 864 2310",
                },
            })
            + "</script>"
            + '<script id="ng-state" type="application/json">'
            + json.dumps(detail_state)
            + "</script>"
        )
        opportunity = extract_opportunities(list_document, "https://example.org/search")[0]

        enriched = enrich_from_detail(opportunity, detail_document)

        self.assertEqual("jobs@example.de", enriched.contact_email)
        self.assertEqual("+31 88 864 2310", enriched.contact_phone)
        self.assertEqual(95, enriched.contact_emails[0]["confidence_score"])
        self.assertEqual(95, enriched.contact_phones[0]["confidence_score"])


if __name__ == "__main__":
    unittest.main()
