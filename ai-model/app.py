from flask import Flask, request, jsonify
from predictor import PropertyPredictor
import os

# Create Flask app instance
# Flask is a lightweight web framework for creating REST APIs
app = Flask(__name__)

# Get absolute path to this file's directory
# This ensures model files are found even if Flask runs from different directories
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# Initialize the predictor with paths to saved model and feature list
# The predictor handles all the machine learning predictions
# model_path: path to price_predictor.pkl (trained Random Forest)
# features_path: path to feature_columns.json (list of exact features used in training)
predictor = PropertyPredictor(
    model_path=os.path.join(BASE_DIR, "price_predictor.pkl"),
    features_path=os.path.join(BASE_DIR, "feature_columns.json"),
    encoder_path=os.path.join(BASE_DIR, "ordinal_encoder.pkl"),
    district_map_path=os.path.join(BASE_DIR, "district_encode_map.pkl")
)

# Health check endpoint - tells PHP that the API is running and responding
@app.get("/health")
def health():
    # Return simple JSON response indicating the API is healthy
    # PHP uses this to confirm Flask is running before making predictions
    return jsonify({"status": "ok"})

# Main prediction endpoint - receives property details and returns price prediction
@app.post("/predict")
def predict():
    # Get JSON data from the request body
    # silent=True means don't crash if JSON is malformed, just return empty dict
    data = request.get_json(silent=True) or {}

    # Extract required fields from the request
    postcode = str(data.get("postcode", "")).strip()
    property_type = str(data.get("property_type", "")).strip()

    # Validate that required fields are provided
    # If missing, return error message with HTTP 400 (bad request)
    if postcode == "" or property_type == "":
        return jsonify({"error": "postcode and property_type are required"}), 400

    # Call the predictor to make a price prediction
    # Pass the entire data dict so predictor can extract all needed fields
    # predictor handles one-hot encoding and feature ordering internally
    price = predictor.predict_from_payload(data)

    # Return the prediction as JSON with rounded price to 2 decimal places
    # PHP will extract predicted_price and store it in the database
    return jsonify(
        {
            "predicted_price": round(price, 2)
        }
    )

# Main entry point - run the Flask app
if __name__ == "__main__":
    # host="127.0.0.1" means only accept connections from localhost (not internet)
    # port=5001 avoids conflicts on macOS where port 5000 may be used by AirPlay/AirTunes
    # debug=True enables auto-reload when code changes and shows detailed error pages
    # In production, debug should be False and a proper WSGI server like Gunicorn should be used
    app.run(host="127.0.0.1", port=5001, debug=True)
