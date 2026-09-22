import json
import unittest

from ba_reader import CollectorError, extract_opportunities


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


if __name__ == "__main__":
    unittest.main()
