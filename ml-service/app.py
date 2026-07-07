from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
import random

app = Flask(__name__)
CORS(app)

@app.route('/health', methods=['GET'])
def health():
    return jsonify({"status": "ML Service is running!"})

@app.route('/predict', methods=['POST'])
def predict():
    data = request.get_json()
    land_area = data.get('land_area_ha', 1)
    fertilizer = data.get('fertilizer_kg_ha', 100)
    base = 4.0
    rf_pred = round(base + (fertilizer * 0.005) + random.uniform(-0.2, 0.2), 2)
    xgb_pred = round(base + (land_area * 0.1) + random.uniform(-0.2, 0.2), 2)
    ensemble_pred = round((rf_pred + xgb_pred) / 2, 2)
    return jsonify({
        "RandomForest": rf_pred,
        "XGBoost": xgb_pred,
        "Ensemble": ensemble_pred
    })

if __name__ == '__main__':
    app.run(debug=True, port=5000)
