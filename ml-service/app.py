# ml-service/app.py
from flask import Flask, request, jsonify
from flask_cors import CORS
import pandas as pd
import numpy as np
import joblib
import os

app = Flask(__name__)
CORS(app)

print("🔄 Loading ML models...")

# Load models and preprocessors
try:
    rf_model = joblib.load('model_rf.pkl')
    xgb_model = joblib.load('model_xgb.pkl')
    scaler = joblib.load('scaler.pkl')
    label_encoders = joblib.load('label_encoders.pkl')
    feature_names = joblib.load('feature_names.pkl')
    print("✅ Models loaded successfully!")
except FileNotFoundError:
    print("❌ Model files not found. Run train_models.py first.")
    rf_model = None
    xgb_model = None

def preprocess_input(data):
    """Convert JSON to model-ready DataFrame"""
    df = pd.DataFrame([data])
    
    # Encode categorical variables
    for col, le in label_encoders.items():
        if col in df.columns:
            try:
                df[col] = le.transform(df[col])
            except ValueError:
                df[col] = 0
    
    # Ensure all feature columns exist
    for col in feature_names:
        if col not in df.columns:
            df[col] = 0
    
    df = df[feature_names]
    df_scaled = scaler.transform(df)
    return df_scaled

@app.route('/health', methods=['GET'])
def health():
    return jsonify({"status": "CROPS ML Service is running!"})

@app.route('/predict', methods=['POST'])
def predict():
    try:
        data = request.get_json()
        
        # Check required fields
        required = ['barangay', 'variety', 'soil_type', 'season', 'seeding_method',
                    'fertilizer_kg_ha', 'temperature_avg', 'rainfall_mm', 
                    'humidity_avg', 'historical_yield_tons_ha']
        
        for field in required:
            if field not in data:
                return jsonify({"error": f"Missing field: {field}"}), 400
        
        # Preprocess and predict
        input_vector = preprocess_input(data)
        
        if rf_model is not None and xgb_model is not None:
            rf_pred = rf_model.predict(input_vector)[0]
            xgb_pred = xgb_model.predict(input_vector)[0]
            ensemble_pred = (rf_pred + xgb_pred) / 2
        else:
            # Fallback dummy
            rf_pred = 4.0 + np.random.uniform(-0.5, 0.5)
            xgb_pred = 4.0 + np.random.uniform(-0.5, 0.5)
            ensemble_pred = (rf_pred + xgb_pred) / 2
        
        return jsonify({
            "RandomForest": round(float(rf_pred), 2),
            "XGBoost": round(float(xgb_pred), 2),
            "Ensemble": round(float(ensemble_pred), 2)
        })
    
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True, port=5000)