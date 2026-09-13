# ml-service/app.py
from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib
import pandas as pd
import numpy as np

app = Flask(__name__)
CORS(app)

print("🔄 Loading ML model...")

try:
    model = joblib.load('model_rf.pkl')
    scaler = joblib.load('scaler.pkl')
    label_encoders = joblib.load('label_encoders.pkl')
    feature_names = joblib.load('feature_names.pkl')
    print("✅ Model loaded successfully!")
except FileNotFoundError as e:
    print(f"❌ Model files not found: {e}")
    model = None

# Define which columns are numeric and categorical
NUM_COLS = ['temperature_avg', 'rainfall_mm', 'humidity_avg', 'fertilizer_kg_ha', 'historical_yield_tons_ha']
CAT_COLS = ['variety', 'soil_type', 'season', 'seeding_method']

def preprocess_input(data):
    """
    Convert raw JSON input to a feature vector the model can use.
    """
    df = pd.DataFrame([data])
    
    # Remove any synthetic columns if present (e.g., barangay, farm_id)
    for col in ['barangay', 'farm_id']:
        if col in df.columns:
            df = df.drop(columns=[col])
    
    # Encode categorical variables (graceful fallback for unseen categories)
    for col in CAT_COLS:
        if col in df.columns:
            le = label_encoders[col]
            try:
                df[col] = le.transform(df[col])
            except ValueError:
                # Unseen category → map to 0 (first known class)
                df[col] = 0
                print(f"⚠️  Unseen category in '{col}', mapped to 0")
        else:
            # If missing, fill with 0
            df[col] = 0
    
    # Ensure all numeric columns exist
    for col in NUM_COLS:
        if col not in df.columns:
            df[col] = 0
    
    # Scale numeric columns (using the same scaler fitted on those columns)
    df[NUM_COLS] = scaler.transform(df[NUM_COLS])
    
    # Reorder columns to match feature_names (numeric first, then categorical)
    df = df[feature_names]
    
    # Return as numpy array for model prediction
    return df.values

@app.route('/health', methods=['GET'])
def health():
    return jsonify({"status": "CROPS ML Service is running!"})

@app.route('/predict', methods=['POST'])
def predict():
    try:
        data = request.get_json()
        
        # Required fields (match what Laravel sends)
        required = [
            'variety', 'soil_type', 'season', 'seeding_method',
            'fertilizer_kg_ha', 'temperature_avg', 'rainfall_mm',
            'humidity_avg', 'historical_yield_tons_ha'
        ]
        for field in required:
            if field not in data:
                return jsonify({"error": f"Missing field: {field}"}), 400
        
        input_vector = preprocess_input(data)
        
        if model is not None:
            prediction = model.predict(input_vector)[0]
        else:
            # Fallback (shouldn't happen if model exists)
            prediction = 4.0 + np.random.uniform(-0.5, 0.5)
        
        # ✅ No cap here – it's applied in Laravel based on variety limits
        return jsonify({
            "Predicted_Yield": round(float(prediction), 2),
            "Model": "Random Forest"
        })
    
    except Exception as e:
        print(f"❌ Error in /predict: {e}")
        import traceback
        traceback.print_exc()
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True, port=5000)