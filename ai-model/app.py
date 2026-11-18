"""Local Flask API serving a saved property-price regression model."""
from flask import Flask, request, jsonify
from predictor import PropertyPredictor
import math
import os

app = Flask(__name__)
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
predictor = PropertyPredictor(
    model_path=os.path.join(BASE_DIR, "price_predictor.pkl"),
    features_path=os.path.join(BASE_DIR, "feature_columns.json"),
    encoder_path=os.path.join(BASE_DIR, "ordinal_encoder.pkl"),
    district_map_path=os.path.join(BASE_DIR, "district_encode_map.pkl"),
)

@app.get("/health")
def health():
    return jsonify({"status": "ok"})

@app.post("/predict")
def predict():
    data = request.get_json(silent=True)
    if not isinstance(data, dict):
        return jsonify({"error": "A JSON object is required"}), 400
    postcode = data.get("postcode")
    property_type = data.get("property_type")
    if not isinstance(postcode, str) or not postcode.strip() or not isinstance(property_type, str) or not property_type.strip():
        return jsonify({"error": "postcode and property_type are required"}), 400
    try:
        for key in ("floor_area", "rooms", "year"):
            if key in data:
                value = float(data[key])
                if not math.isfinite(value):
                    raise ValueError(key)
                if key == "floor_area" and not 10 <= value <= 1000:
                    raise ValueError(key)
                if key == "rooms" and (value < 0 or not value.is_integer()):
                    raise ValueError(key)
                if key == "year" and (not 1900 <= value <= 2100 or not value.is_integer()):
                    raise ValueError(key)
        price = float(predictor.predict_from_payload(data))
        if not math.isfinite(price) or price < 0:
            raise ValueError("invalid model output")
    except (ValueError, TypeError, IndexError):
        return jsonify({"error": "Invalid property details"}), 400
    return jsonify({"predicted_price": round(price, 2)})

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=False)
