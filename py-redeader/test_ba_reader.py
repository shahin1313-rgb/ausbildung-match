import json
import unittest

from ba_reader import CollectorError, enrich_from_detail, extract_opportunities


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


if __name__ == "__main__":
    unittest.main()
