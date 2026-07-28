# ml-service/train_models.py
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_squared_error, mean_absolute_error
import xgboost as xgb
import joblib
import os

print("=" * 50)
print("🌾 CROPS - Training ML Models")
print("=" * 50)

# 1. LOAD DATA
df = pd.read_csv('synthetic_rice_data.csv')
print(f"\n📊 Loaded {len(df)} records")

# 2. PREPARE FEATURES
X = df.drop(columns=['yield_tons_ha'])
y = df['yield_tons_ha']

# Encode categorical variables
categorical_cols = ['barangay', 'variety', 'soil_type', 'season', 'seeding_method']
label_encoders = {}
for col in categorical_cols:
    le = LabelEncoder()
    X[col] = le.fit_transform(X[col])
    label_encoders[col] = le

# Scale features
scaler = StandardScaler()
feature_names = X.columns.tolist()
X_scaled = scaler.fit_transform(X)
X_scaled = pd.DataFrame(X_scaled, columns=feature_names)

# 3. TRAIN/TEST SPLIT
X_train, X_test, y_train, y_test = train_test_split(X_scaled, y, test_size=0.2, random_state=42)
print(f"\n📊 Training: {len(X_train)} records | Testing: {len(X_test)} records")

# 4. TRAIN RANDOM FOREST
print("\n🌲 Training Random Forest...")
rf = RandomForestRegressor(n_estimators=150, max_depth=15, min_samples_split=10, random_state=42, n_jobs=-1)
rf.fit(X_train, y_train)
rf_pred = rf.predict(X_test)
rf_rmse = np.sqrt(mean_squared_error(y_test, rf_pred))
rf_mae = mean_absolute_error(y_test, rf_pred)
rf_mape = np.mean(np.abs((y_test - rf_pred) / y_test)) * 100
print(f"   ✅ RMSE: {rf_rmse:.4f} | MAE: {rf_mae:.4f} | MAPE: {rf_mape:.2f}%")

# 5. TRAIN XGBOOST
print("\n⚡ Training XGBoost...")
xgb_model = xgb.XGBRegressor(n_estimators=150, learning_rate=0.1, max_depth=6, subsample=0.8, random_state=42)
xgb_model.fit(X_train, y_train)
xgb_pred = xgb_model.predict(X_test)
xgb_rmse = np.sqrt(mean_squared_error(y_test, xgb_pred))
xgb_mae = mean_absolute_error(y_test, xgb_pred)
xgb_mape = np.mean(np.abs((y_test - xgb_pred) / y_test)) * 100
print(f"   ✅ RMSE: {xgb_rmse:.4f} | MAE: {xgb_mae:.4f} | MAPE: {xgb_mape:.2f}%")

# 6. ENSEMBLE
ensemble_pred = (rf_pred + xgb_pred) / 2
ens_rmse = np.sqrt(mean_squared_error(y_test, ensemble_pred))
ens_mae = mean_absolute_error(y_test, ensemble_pred)
ens_mape = np.mean(np.abs((y_test - ensemble_pred) / y_test)) * 100
print(f"\n🧩 Ensemble (RF + XGB) - RMSE: {ens_rmse:.4f} | MAE: {ens_mae:.4f} | MAPE: {ens_mape:.2f}%")

# 7. SAVE MODELS
print("\n💾 Saving models...")
joblib.dump(rf, 'model_rf.pkl')
joblib.dump(xgb_model, 'model_xgb.pkl')
joblib.dump(scaler, 'scaler.pkl')
joblib.dump(label_encoders, 'label_encoders.pkl')
joblib.dump(feature_names, 'feature_names.pkl')
print("   ✅ model_rf.pkl")
print("   ✅ model_xgb.pkl")
print("   ✅ scaler.pkl")
print("   ✅ label_encoders.pkl")
print("   ✅ feature_names.pkl")

print("\n" + "=" * 50)
print("✅ Training Complete!")
print("=" * 50)