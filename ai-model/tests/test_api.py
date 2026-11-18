"""Basic API contract checks using a stub predictor; no model artefacts required."""
import importlib.util
from pathlib import Path
import sys
import types
import unittest
from unittest.mock import patch

MODEL_DIR = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(MODEL_DIR))


class PredictionAPITests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        # Replace the predictor dependency before loading app.py, so these tests
        # check request handling without unpickling a trained model.
        fake_predictor = types.ModuleType("predictor")
        fake_predictor.PropertyPredictor = lambda **kwargs: types.SimpleNamespace(
            predict_from_payload=lambda payload: 250000.0
        )
        with patch.dict(sys.modules, {"predictor": fake_predictor}):
            spec = importlib.util.spec_from_file_location("prop_flow_api_under_test", MODEL_DIR / "app.py")
            module = importlib.util.module_from_spec(spec)
            spec.loader.exec_module(module)
        cls.client = module.app.test_client()

    def test_health(self):
        response = self.client.get("/health")
        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.get_json(), {"status": "ok"})

    def test_missing_required_fields(self):
        response = self.client.post("/predict", json={"postcode": "SW1A 1AA"})
        self.assertEqual(response.status_code, 400)
        self.assertIn("error", response.get_json())

    def test_invalid_floor_area(self):
        response = self.client.post("/predict", json={"postcode": "SW1A 1AA", "property_type": "F", "floor_area": -5})
        self.assertEqual(response.status_code, 400)

    def test_non_object_payload(self):
        response = self.client.post("/predict", json=["not", "a", "property"])
        self.assertEqual(response.status_code, 400)

    def test_valid_prediction(self):
        response = self.client.post("/predict", json={"postcode": "SW1A 1AA", "property_type": "F"})
        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.get_json()["predicted_price"], 250000.0)


if __name__ == "__main__":
    unittest.main()
