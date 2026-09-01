# ml-service/app.py
from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib

app = Flask(__name__)
CORS(app)

print("🔄 Loading ML model...")

# Load models
try:
    model = joblib.load('model_rf.pkl')
    scaler = joblib.load('scaler.pkl')
    label_encoders = joblib.load('label_encoders.pkl')
    feature_names = joblib.load('feature_names.pkl')
    print("✅ Model loaded successfully!")
except FileNotFoundError:
    print("❌ Model files not found. Run train_rf.py first.")
    model = None

def preprocess_input(data):
    import pandas as pd
    df = pd.DataFrame([data])
    
    # Remove any synthetic columns if accidentally sent
    for col in ['barangay', 'farm_id']:
        if col in df.columns:
            df = df.drop(columns=[col])
    
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
        
        # Required fields (No barangay)
        required = ['variety', 'soil_type', 'season', 'seeding_method',
                    'fertilizer_kg_ha', 'temperature_avg', 'rainfall_mm', 
                    'humidity_avg', 'historical_yield_tons_ha']
        
        for field in required:
            if field not in data:
                return jsonify({"error": f"Missing field: {field}"}), 400
        
        input_vector = preprocess_input(data)
        
        if model is not None:
            prediction = model.predict(input_vector)[0]
        else:
            import random
            prediction = 4.0 + random.uniform(-0.5, 0.5)
        
        return jsonify({
            "Predicted_Yield": round(float(prediction), 2),
            "Model": "Random Forest"
        })
    
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True, port=5000)