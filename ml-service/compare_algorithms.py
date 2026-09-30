"""
Compare multiple ML algorithms on the CROPS dataset.
Run once, save output as thesis evidence.
"""

import pandas as pd
import numpy as np
import json
from sklearn.model_selection import train_test_split, cross_val_score, StratifiedKFold
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.metrics import (accuracy_score, precision_score, recall_score,
                             f1_score, r2_score, mean_absolute_error,
                             mean_squared_error)

from sklearn.linear_model import LogisticRegression, LinearRegression, Ridge
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import (RandomForestClassifier, GradientBoostingClassifier,
                              RandomForestRegressor, GradientBoostingRegressor)
from sklearn.neighbors import KNeighborsClassifier
from xgboost import XGBClassifier, XGBRegressor

# ── Load data ──
df = pd.read_csv('rice_yield_dataset.csv')

DROP = ['yield_tons_ha', 'yield_class']
feature_names = [c for c in df.columns if c not in DROP]

X_raw = df[feature_names].copy()
y_cls = df['yield_class']
y_reg = df['yield_tons_ha']

NUM_COLS = ['variety_maturity_days', 'variety_max_yield', 'land_area_ha',
            'fertilizer_kg_ha', 'historical_yield_tons_ha',
            'temperature_avg', 'rainfall_mm', 'humidity_avg']

scaler = StandardScaler()
X = X_raw.copy()
X[NUM_COLS] = scaler.fit_transform(X_raw[NUM_COLS])

target_encoder = LabelEncoder()
y_cls_enc = target_encoder.fit_transform(y_cls)

Xtr, Xte, ytr_cls, yte_cls = train_test_split(
    X, y_cls_enc, test_size=0.20, random_state=42, stratify=y_cls_enc
)
_, _, ytr_reg, yte_reg = train_test_split(
    X, y_reg, test_size=0.20, random_state=42
)

# ══════════════════════════════════════════════════════════
# CLASSIFICATION
# ══════════════════════════════════════════════════════════
print("=" * 90)
print(" CLASSIFICATION ALGORITHM COMPARISON")
print("=" * 90)
print(f"{'Algorithm':<26} {'Accuracy':<10} {'Precision':<10} {'Recall':<10} {'F1':<10}")
print("-" * 90)

classifiers = {
    'Logistic Regression':  LogisticRegression(max_iter=1000, random_state=42),
    'Decision Tree':        DecisionTreeClassifier(max_depth=10, random_state=42),
    'K-Nearest Neighbors':  KNeighborsClassifier(n_neighbors=5),
    'Gradient Boosting':    GradientBoostingClassifier(n_estimators=200, random_state=42),
    'XGBoost':              XGBClassifier(
                                n_estimators=800, max_depth=6, learning_rate=0.1,
                                subsample=0.8, colsample_bytree=0.8,
                                random_state=42, n_jobs=-1,
                                eval_metric='mlogloss', verbosity=0),
    'Random Forest':        RandomForestClassifier(
                                n_estimators=800, max_depth=30,
                                min_samples_split=3, min_samples_leaf=1,
                                max_features='sqrt', class_weight='balanced',
                                random_state=42, n_jobs=-1),
}

for name, clf in classifiers.items():
    clf.fit(Xtr, ytr_cls)
    pred = clf.predict(Xte)
    acc  = accuracy_score(yte_cls, pred)
    prec = precision_score(yte_cls, pred, average='weighted', zero_division=0)
    rec  = recall_score(yte_cls, pred, average='weighted', zero_division=0)
    f1   = f1_score(yte_cls, pred, average='weighted', zero_division=0)
    print(f"{name:<26} {acc:<10.4f} {prec:<10.4f} {rec:<10.4f} {f1:<10.4f}")

print("=" * 90)

# ══════════════════════════════════════════════════════════
# 5-FOLD CROSS-VALIDATION
# ══════════════════════════════════════════════════════════
print()
print("=" * 90)
print(" 5-FOLD CROSS-VALIDATION (Classification)")
print("=" * 90)

cv = StratifiedKFold(5, shuffle=True, random_state=42)
for name, clf in classifiers.items():
    scores = cross_val_score(clf, X, y_cls_enc, cv=cv, scoring='accuracy', n_jobs=-1)
    print(f"{name:<26} {scores.mean():.4f} ± {scores.std():.4f}")

print("=" * 90)

# ══════════════════════════════════════════════════════════
# REGRESSION
# ══════════════════════════════════════════════════════════
print()
print("=" * 90)
print(" REGRESSION ALGORITHM COMPARISON")
print("=" * 90)
print(f"{'Algorithm':<26} {'R²':<10} {'MAE':<10} {'RMSE':<10}")
print("-" * 90)

regressors = {
    'Linear Regression':       LinearRegression(),
    'Ridge Regression':        Ridge(alpha=1.0),
    'Gradient Boosting':       GradientBoostingRegressor(n_estimators=200, random_state=42),
    'XGBoost':                 XGBRegressor(
                                   n_estimators=800, max_depth=6, learning_rate=0.1,
                                   subsample=0.8, colsample_bytree=0.8,
                                   random_state=42, n_jobs=-1,
                                   verbosity=0),
    'Random Forest Regressor': RandomForestRegressor(
                                   n_estimators=800, max_depth=30,
                                   random_state=42, n_jobs=-1),
}

for name, reg in regressors.items():
    reg.fit(Xtr, ytr_reg)
    pred = reg.predict(Xte)
    r2   = r2_score(yte_reg, pred)
    mae  = mean_absolute_error(yte_reg, pred)
    rmse = np.sqrt(mean_squared_error(yte_reg, pred))
    print(f"{name:<26} {r2:<10.4f} {mae:<10.4f} {rmse:<10.4f}")

print("=" * 90)
print()
print("Done.")