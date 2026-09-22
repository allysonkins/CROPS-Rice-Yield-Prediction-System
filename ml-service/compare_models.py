"""
CROPS — Random Forest Regressor vs XGBoost Regressor
Regression evaluation: R², MAE, RMSE, MAPE.

Run: python compare_models.py
Output: compare_models_output.txt + comparison_*.png
"""

import time
import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns

from sklearn.ensemble import RandomForestRegressor
from xgboost import XGBRegressor
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import (
    train_test_split, cross_val_score, KFold
)
from sklearn.metrics import (
    r2_score, mean_absolute_error, mean_squared_error
)

# ═════════════════════════════════════════════════════════════════
# 1. Load & prepare
# ═════════════════════════════════════════════════════════════════
print("Loading dataset...")
df = pd.read_csv('rice_yield_dataset.csv')
print(f"Dataset: {df.shape[0]} rows × {df.shape[1]} columns")

DROP = ['yield_tons_ha', 'yield_class']
feature_names = [c for c in df.columns if c not in DROP]
X_raw = df[feature_names].copy()

NUM_COLS = [
    'variety_maturity_days', 'variety_max_yield', 'land_area_ha',
    'fertilizer_kg_ha', 'historical_yield_tons_ha',
    'temperature_avg', 'rainfall_mm', 'humidity_avg',
]

scaler = StandardScaler()
X_scaled = X_raw.copy()
X_scaled[NUM_COLS] = scaler.fit_transform(X_raw[NUM_COLS])

# ── Target: continuous yield (regression) ──
y = df['yield_tons_ha']

Xtr, Xte, ytr, yte = train_test_split(
    X_scaled, y, test_size=0.20, random_state=42
)

print(f"Training: {len(Xtr)} samples | Testing: {len(Xte)} samples")
print(f"Target: yield_tons_ha (continuous)\n")


# ═════════════════════════════════════════════════════════════════
# 2. Define both regressors
# ═════════════════════════════════════════════════════════════════
models = {
    'Random Forest': RandomForestRegressor(
        n_estimators=800,
        max_depth=30,
        min_samples_split=3,
        min_samples_leaf=1,
        max_features='sqrt',
        random_state=42,
        n_jobs=-1,
    ),
    'XGBoost': XGBRegressor(
        n_estimators=500,
        max_depth=6,
        learning_rate=0.1,
        subsample=0.8,
        colsample_bytree=0.8,
        random_state=42,
        verbosity=0,
    ),
}


# ═════════════════════════════════════════════════════════════════
# 3. Train + evaluate with regression metrics
# ═════════════════════════════════════════════════════════════════
results = {}
kf = KFold(n_splits=5, shuffle=True, random_state=42)

def mape(y_true, y_pred):
    """Mean Absolute Percentage Error, ignoring zero targets."""
    mask = y_true != 0
    return np.mean(np.abs((y_true[mask] - y_pred[mask]) / y_true[mask])) * 100

for name, model in models.items():
    print("=" * 62)
    print(f" {name}")
    print("=" * 62)

    # ── Train ──
    t0 = time.time()
    model.fit(Xtr, ytr)
    train_time = time.time() - t0

    # ── Predict ──
    t0 = time.time()
    y_pred = model.predict(Xte)
    infer_time = time.time() - t0

    # ── Regression metrics ──
    r2   = r2_score(yte, y_pred)
    mae  = mean_absolute_error(yte, y_pred)
    rmse = np.sqrt(mean_squared_error(yte, y_pred))
    mape_v = mape(yte.values, y_pred)

    # ── Cross-validation (R² scoring) ──
    cv_r2 = cross_val_score(model, X_scaled, y, cv=kf, scoring='r2', n_jobs=-1)
    cv_mae = -cross_val_score(model, X_scaled, y, cv=kf, scoring='neg_mean_absolute_error', n_jobs=-1)

    results[name] = {
        'r2':         r2,
        'mae':        mae,
        'rmse':       rmse,
        'mape':       mape_v,
        'cv_r2_mean': cv_r2.mean(),
        'cv_r2_std':  cv_r2.std(),
        'cv_mae_mean': cv_mae.mean(),
        'train_time': train_time,
        'infer_time': infer_time,
        'y_pred':     y_pred,
        'model':      model,
    }

    print(f"  R²              : {r2:.4f}")
    print(f"  MAE             : {mae:.4f} t/ha")
    print(f"  RMSE            : {rmse:.4f} t/ha")
    print(f"  MAPE            : {mape_v:.2f}%")
    print(f"  CV R²           : {cv_r2.mean():.4f} ± {cv_r2.std():.4f}")
    print(f"  CV MAE          : {cv_mae.mean():.4f} t/ha")
    print(f"  Train time      : {train_time:.2f}s")
    print(f"  Inference time  : {infer_time:.4f}s")
    print()


# ═════════════════════════════════════════════════════════════════
# 4. Head-to-head summary
# ═════════════════════════════════════════════════════════════════
print("=" * 62)
print(" HEAD-TO-HEAD SUMMARY (REGRESSION)")
print("=" * 62)

summary = pd.DataFrame({
    name: {
        'R² (test)':       f"{r['r2']:.4f}",
        'MAE (t/ha)':      f"{r['mae']:.4f}",
        'RMSE (t/ha)':     f"{r['rmse']:.4f}",
        'MAPE (%)':        f"{r['mape']:.2f}",
        'CV R² (mean)':    f"{r['cv_r2_mean']:.4f}",
        'CV R² (std)':     f"± {r['cv_r2_std']:.4f}",
        'CV MAE (t/ha)':   f"{r['cv_mae_mean']:.4f}",
        'Train Time (s)':  f"{r['train_time']:.2f}",
        'Inference (ms)':  f"{r['infer_time']*1000:.2f}",
    }
    for name, r in results.items()
})

print(summary.to_string())
print()

rf  = results['Random Forest']
xgb = results['XGBoost']
r2_delta   = xgb['r2'] - rf['r2']
mae_delta  = xgb['mae'] - rf['mae']    # negative = XGBoost better
rmse_delta = xgb['rmse'] - rf['rmse']

print(f"Δ R² (XGB − RF)   : {r2_delta:+.4f}")
print(f"Δ MAE (XGB − RF)  : {mae_delta:+.4f} t/ha   ({'XGB better' if mae_delta < 0 else 'RF better'})")
print(f"Δ RMSE (XGB − RF) : {rmse_delta:+.4f} t/ha  ({'XGB better' if rmse_delta < 0 else 'RF better'})")
print()


# ═════════════════════════════════════════════════════════════════
# 5. Predicted vs Actual scatter (both models side by side)
# ═════════════════════════════════════════════════════════════════
fig, axes = plt.subplots(1, 2, figsize=(13, 5.5))
for ax, (name, r) in zip(axes, results.items()):
    ax.scatter(yte, r['y_pred'], alpha=0.4, s=18, color='#165b33')
    lims = [min(yte.min(), r['y_pred'].min()), max(yte.max(), r['y_pred'].max())]
    ax.plot(lims, lims, 'r--', linewidth=1.5, label='Perfect prediction')
    ax.set_xlabel('Actual yield (t/ha)')
    ax.set_ylabel('Predicted yield (t/ha)')
    ax.set_title(f"{name}\nR² = {r['r2']:.4f} | MAE = {r['mae']:.3f}")
    ax.legend()
    ax.grid(alpha=0.3)

plt.tight_layout()
plt.savefig('comparison_predicted_vs_actual.png', dpi=150, bbox_inches='tight')
print("Saved: comparison_predicted_vs_actual.png")


# ═════════════════════════════════════════════════════════════════
# 6. Residual distributions
# ═════════════════════════════════════════════════════════════════
fig, ax = plt.subplots(figsize=(11, 5))
for name, r in results.items():
    residuals = yte - r['y_pred']
    sns.kdeplot(residuals, ax=ax, label=name, fill=True, alpha=0.35)
ax.axvline(0, color='red', linestyle='--', linewidth=1.2)
ax.set_xlabel('Residual (actual − predicted, t/ha)')
ax.set_ylabel('Density')
ax.set_title('Residual Distribution — Error Spread Comparison')
ax.legend()
ax.grid(alpha=0.3)
plt.tight_layout()
plt.savefig('comparison_residuals.png', dpi=150, bbox_inches='tight')
print("Saved: comparison_residuals.png")


# ═════════════════════════════════════════════════════════════════
# 7. Feature importance comparison
# ═════════════════════════════════════════════════════════════════
rf_imp  = pd.Series(rf['model'].feature_importances_,  index=feature_names)
xgb_imp = pd.Series(xgb['model'].feature_importances_, index=feature_names)

top15 = rf_imp.nlargest(15).index
comparison = pd.DataFrame({
    'Random Forest': rf_imp[top15],
    'XGBoost':       xgb_imp[top15],
}).sort_values('Random Forest', ascending=True)

fig, ax = plt.subplots(figsize=(11, 8))
comparison.plot(kind='barh', ax=ax, color=['#165b33', '#d97706'], width=0.75)
ax.set_xlabel('Feature importance')
ax.set_title('Top 15 Features — Random Forest vs XGBoost (Regression)')
ax.grid(axis='x', alpha=0.3)
plt.tight_layout()
plt.savefig('comparison_feature_importance.png', dpi=150, bbox_inches='tight')
print("Saved: comparison_feature_importance.png")

rank_corr = rf_imp.rank().corr(xgb_imp.rank(), method='spearman')
print(f"\nFeature importance rank correlation (Spearman): {rank_corr:.4f}")


# ═════════════════════════════════════════════════════════════════
# 8. Save text report for thesis appendix
# ═════════════════════════════════════════════════════════════════
with open('compare_models_output.txt', 'w', encoding='utf-8') as f:
    f.write("CROPS — Model Comparison Report (Regression)\n")
    f.write("=" * 62 + "\n\n")
    f.write(f"Dataset: {df.shape[0]} rows × {df.shape[1]} columns\n")
    f.write(f"Target: yield_tons_ha (continuous)\n")
    f.write(f"Training: {len(Xtr)} samples | Testing: {len(Xte)} samples\n\n")

    f.write("Head-to-head regression metrics:\n")
    f.write(summary.to_string() + "\n\n")

    f.write(f"Delta R² (XGB − RF)   : {r2_delta:+.4f}\n")
    f.write(f"Delta MAE (XGB − RF)  : {mae_delta:+.4f} t/ha\n")
    f.write(f"Delta RMSE (XGB − RF) : {rmse_delta:+.4f} t/ha\n")
    f.write(f"Feature importance rank correlation: {rank_corr:.4f}\n\n")

    f.write("Conclusion: Random Forest selected for deployment.\n")

print("\nSaved: compare_models_output.txt")
print("Done.")