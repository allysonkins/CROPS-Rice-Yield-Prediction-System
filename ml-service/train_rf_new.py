"""
CROPS — Random Forest with regularization
- max_depth=8 (was 12) — prevents memorizing 96 categorical combos
- min_samples_leaf=5 (was 2) — forces generalization
- n_estimators=300 — more averaging
"""
import numpy as np
import pandas as pd
import joblib
from sklearn.ensemble import RandomForestRegressor
from sklearn.tree import DecisionTreeRegressor
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.model_selection import train_test_split, KFold, cross_val_score
from sklearn.metrics import (r2_score, mean_absolute_error,
                              mean_squared_error, mean_absolute_percentage_error)

df = pd.read_csv('rice_yield_dataset.csv').dropna(subset=['yield_tons_ha'])
print(f"Dataset shape : {df.shape[0]} rows × {df.shape[1]} columns")
print(f"Avg yield     : {df['yield_tons_ha'].mean():.2f} t/ha\n")

CAT_COLS = ['variety', 'soil_type', 'season', 'seeding_method']
NUM_COLS = ['temperature_avg', 'rainfall_mm', 'humidity_avg',
            'fertilizer_kg_ha', 'historical_yield_tons_ha']

X = df.drop('yield_tons_ha', axis=1)
y = df['yield_tons_ha']

label_encoders = {}
for col in CAT_COLS:
    le = LabelEncoder()
    X[col] = le.fit_transform(X[col])
    label_encoders[col] = le

scaler = StandardScaler()
X[NUM_COLS] = scaler.fit_transform(X[NUM_COLS])
feature_names = NUM_COLS + CAT_COLS
X = X[feature_names]

X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.20, random_state=42
)
print(f"Train rows : {X_train.shape[0]}")
print(f"Test rows  : {X_test.shape[0]}\n")

# ---------- Regularized RF hyperparameters ----------
RF_PARAMS = dict(
    n_estimators=400,
    max_depth=10,           # was 6 — deeper trees capture signal
    min_samples_split=8,    # was 20
    min_samples_leaf=4,     # was 10
    max_features=0.7,       # was 0.6
    random_state=42,
    n_jobs=-1,
)

# ---------- Cross-validation ----------
print("Running 5-fold cross-validation ...")
cv_r2 = cross_val_score(
    RandomForestRegressor(**RF_PARAMS),
    X, y, cv=KFold(n_splits=5, shuffle=True, random_state=42),
    scoring='r2'
)
print(f"CV R² : {cv_r2.mean():.4f} ± {cv_r2.std():.4f}")
print(f"Folds : {[round(float(s), 4) for s in cv_r2]}\n")

# ---------- Baselines ----------
print("Baseline comparison on same train/test split:")
print(f"{'Model':<22}{'R²':>10}{'MAE':>12}{'RMSE':>12}")
print("-" * 56)

baselines = {
    'Linear Regression': LinearRegression(),
    'Decision Tree': DecisionTreeRegressor(
    max_depth=10, min_samples_leaf=4, random_state=42
),
    'Random Forest':     RandomForestRegressor(**RF_PARAMS),
}
trained = {}
for name, model in baselines.items():
    model.fit(X_train, y_train)
    pred = model.predict(X_test)
    print(f"{name:<22}{r2_score(y_test, pred):>10.4f}"
          f"{mean_absolute_error(y_test, pred):>12.4f}"
          f"{np.sqrt(mean_squared_error(y_test, pred)):>12.4f}")
    trained[name] = model
print()

# ---------- Final evaluation ----------
model = trained['Random Forest']
y_train_pred = model.predict(X_train)
y_test_pred  = model.predict(X_test)

r2_train = r2_score(y_train, y_train_pred)
r2_test  = r2_score(y_test, y_test_pred)
mae      = mean_absolute_error(y_test, y_test_pred)
rmse     = np.sqrt(mean_squared_error(y_test, y_test_pred))
mape     = mean_absolute_percentage_error(y_test, y_test_pred) * 100
nrmse    = (rmse / y_test.mean()) * 100
gap      = r2_train - r2_test

print("=" * 60)
print("FINAL RANDOM FOREST EVALUATION")
print("=" * 60)
print(f"  Train R²  : {r2_train:.4f}")
print(f"  Test R²   : {r2_test:.4f}       (target > 0.85)   "
      f"{'✅' if r2_test > 0.85 else '❌'}")
print(f"  Overfit   : {gap:.4f}       (gap < 0.08)     "
      f"{'✅' if gap < 0.08 else '❌'}")
print(f"  CV R²     : {cv_r2.mean():.4f} ± {cv_r2.std():.4f}")
print(f"  MAE       : {mae:.4f} t/ha  (target < 0.40)   "
      f"{'✅' if mae < 0.40 else '❌'}")
print(f"  RMSE      : {rmse:.4f} t/ha")
print(f"  MAPE      : {mape:.4f}%      (target < 8.00)   "
      f"{'✅' if mape < 8.00 else '❌'}")
print(f"  NRMSE     : {nrmse:.4f}%      (target < 10.00)  "
      f"{'✅' if nrmse < 10.00 else '❌'}")
print("=" * 60)

importances = model.feature_importances_
indices = np.argsort(importances)[::-1]
print("\nFeature importance ranking:")
for i in indices:
    print(f"  {feature_names[i]:<28} {importances[i]:.4f}")

joblib.dump(model, 'model_rf.pkl')
joblib.dump(scaler, 'scaler.pkl')
joblib.dump(label_encoders, 'label_encoders.pkl')
joblib.dump(feature_names, 'feature_names.pkl')
print("\n✅ Model files saved")