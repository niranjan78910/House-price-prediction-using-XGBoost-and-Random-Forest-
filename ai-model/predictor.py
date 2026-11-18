import json
import os
import joblib
import pandas as pd
import numpy as np

class PropertyPredictor:
    def __init__(self, 
                 model_path="price_predictor.pkl", 
                 features_path="feature_columns.json", 
                 encoder_path="ordinal_encoder.pkl", 
                 district_map_path="district_encode_map.pkl"):
                # Load the trained model
        self.model = joblib.load(model_path)

        # Load feature columns (not strictly needed for prediction, but for reference)
        with open(features_path, "r") as f:
            self.feature_columns = json.load(f)

        # Load encoders
        self.encoder = joblib.load(encoder_path)
        self.district_map = joblib.load(district_map_path)
        metadata_path = os.path.join(os.path.dirname(model_path), "training_metadata.json")
        if os.path.exists(metadata_path):
            with open(metadata_path, "r") as metadata_file:
                self.global_mean = json.load(metadata_file)["global_mean_log_price"]
        else:
            # Backward compatibility with previously saved model artefacts.
            self.global_mean = self.district_map.mean()

    def predict_from_payload(self, payload: dict) -> float:
        # Extract and sanitize features
        floor_area = float(payload.get("floor_area", 0))
        rooms = int(payload.get("rooms", 0))
        year = int(payload.get("year", 2024))
        property_type = str(payload.get("property_type", "D")).strip().upper()[0]
        freehold = str(payload.get("freehold", "F")).strip().upper()[0]
        postcode = str(payload.get("postcode", "")).strip().upper()
        postcode_district = postcode.split()[0] if postcode else ""

        # Get district encoded value (with smoothing)
        district_te = self.district_map.get(postcode_district, self.global_mean)

        # Encode categorical features
        cat_df = pd.DataFrame([[property_type, freehold]], columns=["property_type", "freehold"])
        cat_encoded = self.encoder.transform(cat_df)[0]

        # Create feature array (must match training order)
        X = pd.DataFrame([[
            floor_area,
            rooms,
            year,
            district_te,
            cat_encoded[0],  # property_type encoded
            cat_encoded[1]   # freehold encoded
        ]], columns=["floor_area", "rooms", "year", "district_te", "property_type", "freehold"])

        # Predict log price and convert back
        pred_log = self.model.predict(X)[0]
        pred_price = np.exp(pred_log)
        return float(pred_price)