# ml-service/train_rf.py
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_squared_error, mean_absolute_error, r2_score
import joblib

print("=" * 60)
print("🌾 CROPS - Training Random Forest (Final)")
print("=" * 60)

# 1. Load dataset
df = pd.read_csv('synthetic_rice_data.csv')
print(f"📊 Loaded {len(df)} records")

# 2. Define features and target
X = df.drop(columns=['yield_tons_ha'])
y = df['yield_tons_ha']

print(f"📋 Features: {X.columns.tolist()}")

# 3. Encode categorical variables
categorical_cols = ['variety', 'soil_type', 'season', 'seeding_method']
label_encoders = {}
for col in categorical_cols:
    le = LabelEncoder()
    X[col] = le.fit_transform(X[col])
    label_encoders[col] = le
    print(f"   ✅ Encoded '{col}'")

# 4. Scale features
scaler = StandardScaler()
feature_names = X.columns.tolist()
X_scaled = scaler.fit_transform(X)
X_scaled = pd.DataFrame(X_scaled, columns=feature_names)

# 5. Train/Test split
X_train, X_test, y_train, y_test = train_test_split(X_scaled, y, test_size=0.2, random_state=42)
print(f"📊 Training: {len(X_train)} records | Testing: {len(X_test)} records")

# 6. Train Random Forest
print("\n🌲 Training Random Forest...")
rf = RandomForestRegressor(
    n_estimators=150,
    max_depth=15,
    min_samples_split=10,
    random_state=42,
    n_jobs=-1
)
rf.fit(X_train, y_train)

# 7. Evaluate
train_pred = rf.predict(X_train)
test_pred = rf.predict(X_test)

train_rmse = np.sqrt(mean_squared_error(y_train, train_pred))
test_rmse = np.sqrt(mean_squared_error(y_test, test_pred))
train_mae = mean_absolute_error(y_train, train_pred)
test_mae = mean_absolute_error(y_test, test_pred)
train_r2 = r2_score(y_train, train_pred)
test_r2 = r2_score(y_test, test_pred)
train_mape = np.mean(np.abs((y_train - train_pred) / y_train)) * 100
test_mape = np.mean(np.abs((y_test - test_pred) / y_test)) * 100

print(f"\n📊 Random Forest Results:")
print(f"   Training RMSE: {train_rmse:.4f} | Test RMSE: {test_rmse:.4f}")
print(f"   Training MAE:  {train_mae:.4f} | Test MAE:  {test_mae:.4f}")
print(f"   Training R²:   {train_r2:.4f} | Test R²:   {test_r2:.4f}")
print(f"   Training MAPE: {train_mape:.2f}% | Test MAPE: {test_mape:.2f}%")

# 8. Save models
print("\n💾 Saving models...")
joblib.dump(rf, 'model_rf.pkl')
joblib.dump(scaler, 'scaler.pkl')
joblib.dump(label_encoders, 'label_encoders.pkl')
joblib.dump(feature_names, 'feature_names.pkl')
print("   ✅ model_rf.pkl")
print("   ✅ scaler.pkl")
print("   ✅ label_encoders.pkl")
print("   ✅ feature_names.pkl")

print("\n✅ Training Complete!")