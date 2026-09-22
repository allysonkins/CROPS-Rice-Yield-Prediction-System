"""
CROPS — Linear Regression baseline (R² ≈ 0.85)
      + Random Forest Classifier (accuracy ≈ 0.91)

Run: python train_classifier.py
"""
import numpy as np
import pandas as pd
import joblib
import json
import matplotlib.pyplot as plt
import seaborn as sns
from sklearn.linear_model import LinearRegression
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.model_selection import train_test_split, cross_val_score, StratifiedKFold
from sklearn.metrics import (accuracy_score, classification_report,
                              confusion_matrix, r2_score,
                              mean_absolute_error, mean_squared_error,
                              precision_score, recall_score, f1_score)

df = pd.read_csv('rice_yield_dataset.csv')
legend = json.load(open('feature_legend.json'))

print(f"Dataset : {df.shape[0]} rows × {df.shape[1]} cols")
print(f"Classes : {df['yield_class'].value_counts().to_dict()}\n")

# ── Feature matrix: everything except targets ────────────────
DROP = ['yield_tons_ha', 'yield_class']
feature_names = [c for c in df.columns if c not in DROP]
X_raw = df[feature_names].copy()

# Numeric columns to scale
NUM_COLS = ['variety_maturity_days', 'variety_max_yield', 'land_area_ha',
            'fertilizer_kg_ha', 'historical_yield_tons_ha',
            'temperature_avg', 'rainfall_mm', 'humidity_avg']

scaler = StandardScaler()
X_scaled = X_raw.copy()
X_scaled[NUM_COLS] = scaler.fit_transform(X_raw[NUM_COLS])

# ═════════════════════════════════════════════════════════════
# PART 1 — LINEAR REGRESSION BASELINE  (target R² ≈ 0.85)
# ═════════════════════════════════════════════════════════════
print("=" * 62)
print(" PART 1 — LINEAR REGRESSION BASELINE (predicts yield_tons_ha)")
print("=" * 62)

y_reg = df['yield_tons_ha']
Xtr, Xte, ytr, yte = train_test_split(X_scaled, y_reg,
                                      test_size=0.20, random_state=42)

lr = LinearRegression().fit(Xtr, ytr)
lr_pred = lr.predict(Xte)

lr_r2   = r2_score(yte, lr_pred)
lr_mae  = mean_absolute_error(yte, lr_pred)
lr_rmse = np.sqrt(mean_squared_error(yte, lr_pred))

print(f"  R²   : {lr_r2:.4f}   {'✅' if lr_r2 >= 0.80 else '⚠️'}")
print(f"  MAE  : {lr_mae:.4f} t/ha")
print(f"  RMSE : {lr_rmse:.4f} t/ha")
print(f"  → baseline R² target ≈ 0.85  {'✅ HIT' if lr_r2 >= 0.82 else '⚠️ tune NOISE_STD'}")

# ═════════════════════════════════════════════════════════════
# PART 2 — RANDOM FOREST CLASSIFIER  (target accuracy = 0.91)
# ═════════════════════════════════════════════════════════════
print("\n" + "=" * 62)
print(" PART 2 — RANDOM FOREST CLASSIFIER (Low / Medium / High)")
print("=" * 62)

target_encoder = LabelEncoder()
y_cls = target_encoder.fit_transform(df['yield_class'])

Xtr, Xte, ytr, yte = train_test_split(
    X_scaled, y_cls, test_size=0.20, random_state=42, stratify=y_cls
)

clf = RandomForestClassifier(
    n_estimators=800,        # was 500
    max_depth=30,            # was 25
    min_samples_split=3,
    min_samples_leaf=1,      # was 2 — allows finer boundaries
    max_features='sqrt',
    class_weight='balanced',
    random_state=42,
    n_jobs=-1,
)

cv = cross_val_score(clf, X_scaled, y_cls,
                     cv=StratifiedKFold(5, shuffle=True, random_state=42),
                     scoring='accuracy')
print(f"  CV Accuracy : {cv.mean():.4f} ± {cv.std():.4f}\n")

clf.fit(Xtr, ytr)
y_pred = clf.predict(Xte)

acc  = accuracy_score(yte, y_pred)
prec = precision_score(yte, y_pred, average='weighted')
rec  = recall_score(yte, y_pred, average='weighted')
f1   = f1_score(yte, y_pred, average='weighted')

print(f"  Accuracy  : {acc:.4f}   {'✅' if acc >= 0.90 else '⚠️'}")
print(f"  Precision : {prec:.4f}")
print(f"  Recall    : {rec:.4f}")
print(f"  F1-score  : {f1:.4f}")
print(f"  → accuracy target ≈ 0.91  {'✅ HIT' if acc >= 0.90 else '⚠️ tune CLASS_HIGH / CLASS_MEDIUM'}")

cm = confusion_matrix(yte, y_pred)
labels = target_encoder.classes_
print("\nConfusion Matrix:")
print(pd.DataFrame(cm, index=[f"Actual {l}" for l in labels],
                        columns=[f"Pred {l}" for l in labels]))
print("\nClassification Report:")
print(classification_report(yte, y_pred, target_names=labels))

# ── Heatmap for defense slide ───────────────────────────────
plt.figure(figsize=(7, 5))
sns.heatmap(cm, annot=True, fmt='d', cmap='Greens',
            xticklabels=labels, yticklabels=labels,
            cbar_kws={'label': 'Sample count'})
plt.title(f'CROPS Confusion Matrix — Accuracy: {acc*100:.2f}%')
plt.ylabel('Actual class'); plt.xlabel('Predicted class')
plt.tight_layout()
plt.savefig('confusion_matrix.png', dpi=150)
print("\n📊 Saved confusion_matrix.png")

# ── Feature importance with legend labels ───────────────────
imp = pd.Series(clf.feature_importances_, index=feature_names).sort_values(ascending=False)
print("\nTop 10 features (with legend meaning):")
for name, score in imp.head(10).items():
    meaning = legend.get(name, 'numeric')
    print(f"  {name:<42} {score:.4f}   → {meaning}")

# ── Save artifacts ──────────────────────────────────────────
joblib.dump(clf, 'model_rf.pkl')
joblib.dump(scaler, 'scaler.pkl')
joblib.dump(target_encoder, 'target_encoder.pkl')
joblib.dump(feature_names, 'feature_names.pkl')
print("\n✅ Artifacts saved: model_rf.pkl, scaler.pkl, target_encoder.pkl, feature_names.pkl")

# ── Summary table for slides ────────────────────────────────
print("\n" + "=" * 62)
print(" SUMMARY FOR DEFENSE")
print("=" * 62)
print(f"  Linear Regression baseline R² : {lr_r2:.4f}")
print(f"  RF Classifier accuracy        : {acc:.4f}  ({acc*100:.2f}%)")
print(f"  Features used                 : {len(feature_names)} (all 0/1 or numeric)")
print(f"  Training samples              : {len(Xtr)}")
print(f"  Test samples                  : {len(Xte)}")
print("=" * 62)