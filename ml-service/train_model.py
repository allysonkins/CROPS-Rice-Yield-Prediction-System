"""
CROPS — XGBoost Regression with quantile-based confidence
+ post-hoc interval calibration on a held-out calibration split.

Three-way split:
  • train  (60%)  → fit the three XGBoost models
  • calib  (20%)  → measure the interval-widening factor
  • test   (20%)  → report final metrics and coverage

Run: python train_model.py
"""
import numpy as np
import pandas as pd
import joblib
import json
import matplotlib.pyplot as plt
from sklearn.linear_model import LinearRegression
from xgboost import XGBRegressor
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split, cross_val_score, KFold
from sklearn.metrics import (r2_score, mean_absolute_error,
                              mean_squared_error, mean_absolute_percentage_error)

df = pd.read_csv('rice_yield_dataset.csv')
legend = json.load(open('feature_legend.json'))

print(f"Dataset : {df.shape[0]} rows × {df.shape[1]} cols")
print(f"Target  : yield_tons_ha  (range {df['yield_tons_ha'].min():.2f} – {df['yield_tons_ha'].max():.2f})\n")

DROP = ['yield_tons_ha']
feature_names = [c for c in df.columns if c not in DROP]
X_raw = df[feature_names].copy()
y = df['yield_tons_ha'].values

NUM_COLS = ['variety_maturity_days', 'variety_max_yield', 'land_area_ha',
            'fertilizer_kg_ha', 'historical_yield_tons_ha',
            'temperature_avg', 'rainfall_mm', 'humidity_avg']

scaler = StandardScaler()
X_scaled = X_raw.copy()
X_scaled[NUM_COLS] = scaler.fit_transform(X_raw[NUM_COLS])

# ── Three-way split: train / calibration / test ─────────────
X_tr_full, X_te, y_tr_full, y_te = train_test_split(
    X_scaled, y, test_size=0.20, random_state=42
)
X_tr, X_cal, y_tr, y_cal = train_test_split(
    X_tr_full, y_tr_full, test_size=0.25, random_state=123
)

print(f"Splits  : train {len(X_tr)} | calib {len(X_cal)} | test {len(X_te)}\n")


# ─────────────────────────────────────────────────────────────
def build_xgb(**overrides):
    base = dict(
        n_estimators      = 1200,
        max_depth         = 8,
        learning_rate     = 0.04,
        subsample         = 0.90,
        colsample_bytree  = 0.90,
        min_child_weight  = 1,
        gamma             = 0,
        reg_alpha         = 0,
        reg_lambda        = 1.0,
        tree_method       = 'hist',
        random_state      = 42,
        n_jobs            = -1,
    )
    base.update(overrides)
    return XGBRegressor(**base)


# ═════════════════════════════════════════════════════════════
# PART 1 — LINEAR REGRESSION BASELINE
# ═════════════════════════════════════════════════════════════
print("=" * 62)
print(" PART 1 — LINEAR REGRESSION BASELINE")
print("=" * 62)

lr = LinearRegression().fit(X_tr, y_tr)
lr_pred = lr.predict(X_te)

lr_r2   = r2_score(y_te, lr_pred)
lr_mae  = mean_absolute_error(y_te, lr_pred)
lr_rmse = np.sqrt(mean_squared_error(y_te, lr_pred))
lr_mape = mean_absolute_percentage_error(y_te, lr_pred) * 100

print(f"  R²   : {lr_r2:.4f}")
print(f"  MAE  : {lr_mae:.4f} t/ha")
print(f"  RMSE : {lr_rmse:.4f} t/ha")
print(f"  MAPE : {lr_mape:.2f} %")

# ═════════════════════════════════════════════════════════════
# PART 2 — XGBOOST: POINT + QUANTILE MODELS
# ═════════════════════════════════════════════════════════════
print("\n" + "=" * 62)
print(" PART 2 — XGBOOST REGRESSOR (point + 10/90 quantile)")
print("=" * 62)

xgb_main = build_xgb(objective='reg:squarederror')
xgb_low  = build_xgb(objective='reg:quantileerror', quantile_alpha=0.10)
xgb_high = build_xgb(objective='reg:quantileerror', quantile_alpha=0.90)

cv = cross_val_score(
    xgb_main, X_tr_full, y_tr_full,
    cv=KFold(5, shuffle=True, random_state=42),
    scoring='r2'
)
print(f"  CV R² : {cv.mean():.4f} ± {cv.std():.4f}\n")

xgb_main.fit(X_tr, y_tr)
xgb_low.fit(X_tr, y_tr)
xgb_high.fit(X_tr, y_tr)

y_pred = xgb_main.predict(X_te)
r2   = r2_score(y_te, y_pred)
mae  = mean_absolute_error(y_te, y_pred)
rmse = np.sqrt(mean_squared_error(y_te, y_pred))
mape = mean_absolute_percentage_error(y_te, y_pred) * 100

print(f"  R²   : {r2:.4f}   {'✅' if r2 >= 0.85 else '⚠️'}")
print(f"  MAE  : {mae:.4f} t/ha")
print(f"  RMSE : {rmse:.4f} t/ha")
print(f"  MAPE : {mape:.2f} %")

# ═════════════════════════════════════════════════════════════
# PART 3 — INTERVAL CALIBRATION ON HELD-OUT SPLIT
# ═════════════════════════════════════════════════════════════
print("\n" + "=" * 62)
print(" PART 3 — INTERVAL CALIBRATION (held-out calibration split)")
print("=" * 62)

# ── Raw coverage BEFORE calibration (on the calibration set) ──
y_pred_cal = xgb_main.predict(X_cal)
raw_low_cal  = xgb_low.predict(X_cal)
raw_high_cal = xgb_high.predict(X_cal)

raw_half_cal = np.abs(raw_high_cal - raw_low_cal) / 2.0

raw_low_bound  = y_pred_cal - raw_half_cal
raw_high_bound = y_pred_cal + raw_half_cal
raw_coverage_cal = np.mean(
    (y_cal >= raw_low_bound) & (y_cal <= raw_high_bound)
) * 100

print(f"  Raw interval coverage (calib split) : {raw_coverage_cal:.2f}%")
print(f"  Raw mean width (calib split)        : {(raw_high_bound - raw_low_bound).mean():.3f} t/ha")

# ── Compute the widening factor ─────────────────────────────
residuals = np.abs(y_cal - y_pred_cal)
ratios    = residuals / np.maximum(raw_half_cal, 1e-6)

# Factor that makes 80% of calibration residuals fall inside the interval
calibration_factor = float(np.quantile(ratios, 0.80))

# Floor at 1.0 — never shrink the interval, only widen
calibration_factor = max(calibration_factor, 1.0)

print(f"\n  Calibration factor : {calibration_factor:.4f}")

# ── Apply and check coverage on the TEST set ────────────────
raw_low_te   = xgb_low.predict(X_te)
raw_high_te  = xgb_high.predict(X_te)
half_width_te = np.abs(raw_high_te - raw_low_te) / 2.0 * calibration_factor

cal_low_te  = y_pred - half_width_te
cal_high_te = y_pred + half_width_te

coverage   = np.mean((y_te >= cal_low_te) & (y_te <= cal_high_te)) * 100
mean_width = (cal_high_te - cal_low_te).mean()

print(f"\n  Raw coverage on TEST               : "
      f"{np.mean((y_te >= (y_pred - np.abs(raw_high_te - raw_low_te)/2)) & (y_te <= (y_pred + np.abs(raw_high_te - raw_low_te)/2))) * 100:.2f}%")
print(f"  Calibrated 80% interval coverage   : {coverage:.2f}%   (target: ~80%)")
print(f"  Mean interval width                : {mean_width:.3f} t/ha")

# ── Residual plot ───────────────────────────────────────────
plt.figure(figsize=(8, 5))
plt.scatter(y_pred, y_te - y_pred, alpha=0.35, s=12, color='#0f4c2b')
plt.axhline(0, color='#b8860b', linestyle='--', linewidth=1.5)
plt.xlabel('Predicted yield (t/ha)')
plt.ylabel('Residual (actual − predicted)')
plt.title(f'XGBoost Residuals — R² = {r2:.4f}, RMSE = {rmse:.3f} t/ha')
plt.tight_layout()
plt.savefig('residuals.png', dpi=150)
print("\n📊 Saved residuals.png")

# ── Predicted vs Actual ─────────────────────────────────────
plt.figure(figsize=(7, 6))
plt.scatter(y_te, y_pred, alpha=0.35, s=12, color='#0f4c2b')
lims = [min(y_te.min(), y_pred.min()), max(y_te.max(), y_pred.max())]
plt.plot(lims, lims, '--', color='#b8860b', linewidth=1.5)
plt.xlabel('Actual yield (t/ha)')
plt.ylabel('Predicted yield (t/ha)')
plt.title(f'Predicted vs Actual — R² = {r2:.4f}')
plt.tight_layout()
plt.savefig('predicted_vs_actual.png', dpi=150)
print("📊 Saved predicted_vs_actual.png")

# ── Feature importance ──────────────────────────────────────
imp = pd.Series(xgb_main.feature_importances_, index=feature_names)\
        .sort_values(ascending=False)
print("\nTop 10 features:")
for name, score in imp.head(10).items():
    print(f"  {name:<42} {score:.4f}   → {legend.get(name, 'numeric')}")

# ── Save artifacts ──────────────────────────────────────────
joblib.dump(xgb_main, 'model_xgb.pkl')
joblib.dump(xgb_low,  'model_xgb_low.pkl')
joblib.dump(xgb_high, 'model_xgb_high.pkl')
joblib.dump(scaler,   'scaler.pkl')
joblib.dump(feature_names, 'feature_names.pkl')
joblib.dump(calibration_factor, 'interval_scale.pkl')

print("\n✅ Artifacts saved:")
print("   model_xgb.pkl, model_xgb_low.pkl, model_xgb_high.pkl,")
print("   scaler.pkl, feature_names.pkl, interval_scale.pkl")

# ── Summary ─────────────────────────────────────────────────
print("\n" + "=" * 62)
print(" SUMMARY FOR DEFENSE")
print("=" * 62)
print(f"  Linear Regression R²             : {lr_r2:.4f}")
print(f"  XGBoost R²                       : {r2:.4f}")
print(f"  XGBoost RMSE                     : {rmse:.4f} t/ha")
print(f"  XGBoost MAPE                     : {mape:.2f} %")
print(f"  Calibration factor               : {calibration_factor:.4f}")
print(f"  Calibrated 80% interval coverage : {coverage:.2f}%")
print(f"  Features                         : {len(feature_names)}")
print(f"  Splits (train/calib/test)        : {len(X_tr)} / {len(X_cal)} / {len(X_te)}")
print("=" * 62)