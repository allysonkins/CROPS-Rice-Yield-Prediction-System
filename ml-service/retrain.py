#!/usr/bin/env python3
"""
CROPS - Retrain the RandomForest classifier from real farm records.

Called by Laravel's ModelTrainingService.

Merges:
  - Real harvested farm records (exported by Laravel, raw categoricals)
  - Synthetic baseline dataset (one-hot encoded by generate_dataset.py)

The synthetic data is decoded back to raw categoricals first so both
frames can be re-encoded together with a single get_dummies call.
"""

import argparse
import json
import os
import sys
import traceback
from datetime import datetime

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (accuracy_score, confusion_matrix,
                             precision_recall_fscore_support)
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, StandardScaler


# ─────────────────────────────────────────────────────────────
# Variety metadata lookup - mirrors generate_dataset.py
# ─────────────────────────────────────────────────────────────
VARIETIES = {
    'Angelica (NSIC Rc122)':           {'cls': 'Inbred', 'gp': (121, 121), 'avg': (4.70, 4.70), 'max': (5.00, 5.00)},
    'NSIC Rc216 (Tubigan 17)':         {'cls': 'Inbred', 'gp': (112, 104), 'avg': (6.00, 5.70), 'max': (9.70, 9.30)},
    'NSIC Rc 512 (Tubigan 44)':        {'cls': 'Inbred', 'gp': (113, 105), 'avg': (5.60, 5.60), 'max': (10.20, 10.10)},
    'NSIC RC 402 (Tubigan 36)':        {'cls': 'Inbred', 'gp': (114, 107), 'avg': (5.50, 5.50), 'max': (14.00, 14.00)},
    'NSIC Rc 534 (Salinas 29)':        {'cls': 'Inbred', 'gp': (131, 130), 'avg': (3.10, 3.10), 'max': (6.70, 6.70)},
    'NSIC Rc222 (Tubigan 18)':         {'cls': 'Inbred', 'gp': (114, 106), 'avg': (6.10, 5.70), 'max': (10.00, 7.90)},
    'NSIC Rc 480':                     {'cls': 'Inbred', 'gp': (107, 107), 'avg': (3.20, 3.20), 'max': (4.40, 4.40)},
    'NSIC Rc160 (Tubigan 14)':         {'cls': 'Inbred', 'gp': (122, 107), 'avg': (5.60, 5.60), 'max': (8.20, 8.20)},
    'NSIC Rc440(Tubigan 39)':          {'cls': 'Inbred', 'gp': (109, 109), 'avg': (5.50, 5.50), 'max': (10.80, 10.80)},
    'PSB Rc18 (Ala)':                  {'cls': 'Inbred', 'gp': (123, 123), 'avg': (5.10, 5.10), 'max': (8.10, 8.10)},
    'NSIC 2016 Rc 456H (Mestiso 78)':  {'cls': 'Hybrid', 'gp': (112, 112), 'avg': (6.70, 6.70), 'max': (11.70, 11.70)},
    'NSIC Rc234H (MESTISO 27)':        {'cls': 'Hybrid', 'gp': (115, 115), 'avg': (6.50, 6.50), 'max': (9.80, 9.80)},
    'NSIC Rc 486 (Mestiso 80)':        {'cls': 'Hybrid', 'gp': (113, 113), 'avg': (6.50, 6.50), 'max': (13.90, 13.90)},
    'NSIC Rc124H (MESTISO 4)':         {'cls': 'Hybrid', 'gp': (118, 118), 'avg': (5.70, 5.70), 'max': (9.10, 9.10)},
    'NSIC Rc132H (MESTISO 6)':         {'cls': 'Hybrid', 'gp': (113, 113), 'avg': (5.90, 5.90), 'max': (8.70, 8.70)},
    'NSIC Rc 666H':                    {'cls': 'Hybrid', 'gp': (110, 110), 'avg': (5.22, 5.22), 'max': (6.79, 6.79)},
    'PSB Rc72H (Mestiso)':             {'cls': 'Hybrid', 'gp': (123, 123), 'avg': (5.40, 5.40), 'max': (9.90, 9.90)},
    'NSIC Rc204H (Mestiso 20)':        {'cls': 'Hybrid', 'gp': (111, 111), 'avg': (6.40, 6.40), 'max': (11.70, 11.70)},
}

CAT_COLS = ['variety', 'classification', 'soil_type', 'season', 'seeding_method']


# ─────────────────────────────────────────────────────────────
# Helpers
# ─────────────────────────────────────────────────────────────
def classify_yield(tons_ha):
    """Same thresholds used by the app UI (t/ha)."""
    if tons_ha is None or pd.isna(tons_ha):
        return None
    if tons_ha >= 4.5:
        return 'High'
    if tons_ha >= 3.5:
        return 'Medium'
    return 'Low'


def lookup_variety(name):
    """Return metadata for a variety name, with fuzzy fallback."""
    if name in VARIETIES:
        return VARIETIES[name]

    lower = str(name).lower()
    for key, meta in VARIETIES.items():
        if key.lower().startswith(lower[:12]) or lower.startswith(key.lower()[:12]):
            return meta

    # Unknown variety -> conservative Inbred defaults
    return {'cls': 'Inbred', 'gp': (112, 112), 'avg': (4.20, 4.20), 'max': (6.50, 6.50)}


def decode_one_hot(df, cat_cols):
    """
    Reverse one-hot encoding: turn `cat_Value` 0/1 columns back into a
    single `cat` string column. Safe to call on already-raw data
    (returns it unchanged when no one-hot columns are found).
    """
    decoded = df.copy()

    for cat in cat_cols:
        prefix = f'{cat}_'
        matching = []

        for c in decoded.columns:
            if c == cat or not c.startswith(prefix):
                continue
            # Only treat as a one-hot column if it holds 0/1 values.
            # This excludes numeric features like `variety_max_yield`
            # or `variety_maturity_days` that share the same prefix.
            values = decoded[c].dropna().unique()
            if len(values) == 0:
                continue
            if set(values).issubset({0, 1, 0.0, 1.0}):
                matching.append(c)

        if not matching:
            continue

        # Build the raw category from whichever column is 1
        if cat not in decoded.columns:
            decoded[cat] = 'Unknown'
        for c in matching:
            mask = decoded[c] == 1
            decoded.loc[mask, cat] = c[len(prefix):]

        decoded = decoded.drop(columns=matching)

    return decoded


def parse_args():
    p = argparse.ArgumentParser()
    p.add_argument('--input-csv', required=True, help='Real farm records CSV from Laravel')
    p.add_argument('--synthetic-csv', required=True, help='Synthetic baseline CSV')
    p.add_argument('--artifacts-dir', required=True, help='Where to save versioned .pkl files')
    p.add_argument('--metadata', required=True, help='Where to write metadata JSON')
    p.add_argument('--current-pointer', required=True, help='Where to write current_model.json')
    p.add_argument('--version', required=True, help='Version string')
    return p.parse_args()


def write_error_metadata(metadata_path, message):
    with open(metadata_path, 'w') as f:
        json.dump({'error': message}, f, indent=2)


def main():
    args = parse_args()

    try:
        # ── 1. Load real farm records ───────────────────────────
        real_df = pd.read_csv(args.input_csv)
        if real_df.empty:
            raise ValueError('No real training records provided.')

        print('[INFO] Loaded {} real farm records'.format(len(real_df)))

        # ── 2. Enrich real records with variety metadata ────────
        real_rows = []
        for _, r in real_df.iterrows():
            variety_name = str(r.get('variety') or 'Unknown').strip()
            meta = lookup_variety(variety_name)
            seeding = str(r.get('seeding_method') or 'Transplanted').strip()
            is_t = seeding == 'Transplanted'
            idx = 0 if is_t else 1

            season = str(r.get('season') or 'Wet').strip()
            season = 'Dry' if season.lower().startswith('dry') else 'Wet'

            actual = r.get('actual_yield_tons_ha')
            if pd.isna(actual):
                continue
            actual = float(actual)

            real_rows.append({
                'variety':                  variety_name,
                'classification':           meta['cls'],
                'soil_type':                str(r.get('soil_type') or 'Loam'),
                'season':                   season,
                'seeding_method':           seeding,
                'variety_maturity_days':    meta['gp'][idx],
                'variety_max_yield':        meta['max'][idx],
                'variety_avg_yield':        meta['avg'][idx],
                'land_area_ha':             float(r.get('land_area_ha') or 1.0),
                'fertilizer_kg_ha':         float(r.get('fertilizer_kg_ha') or 100),
                'historical_yield_tons_ha': float(r.get('historical_yield_tons_ha') or meta['avg'][idx]),
                'temperature_avg':          float(r.get('temperature_avg') or 27.5),
                'rainfall_mm':              float(r.get('rainfall_mm') or 200),
                'humidity_avg':             float(r.get('humidity_avg') or 78),
                'yield_tons_ha':            actual,
                'yield_class':              classify_yield(actual),
                '_source':                  'real',
            })

        real_df_clean = pd.DataFrame(real_rows)
        if real_df_clean.empty:
            raise ValueError('No usable real records after cleaning.')

        print('[OK] Cleaned real records: {}'.format(len(real_df_clean)))
        print('     Class distribution: {}'.format(
            real_df_clean['yield_class'].value_counts().to_dict()))

        # ── 3. Load synthetic baseline ──────────────────────────
        synth_df = pd.read_csv(args.synthetic_csv)
        print('[INFO] Loaded {} synthetic records'.format(len(synth_df)))

        # The synthetic CSV was saved by generate_dataset.py and is
        # already one-hot encoded. Decode it back to raw categoricals
        # so we can re-encode the merged dataset with a single
        # get_dummies call (avoids duplicate column names).
        synth_df = decode_one_hot(synth_df, CAT_COLS)
        print('[INFO] Decoded synthetic categoricals back to raw form')

        synth_df['_source'] = 'synthetic'
        print('     Synthetic distribution: {}'.format(
            synth_df['yield_class'].value_counts().to_dict()))

        # ── 4. Weight real data ─────────────────────────────────
        REAL_WEIGHT = 5
        weighted_real = pd.concat([real_df_clean] * REAL_WEIGHT, ignore_index=True)
        print('[INFO] Real records multiplied x{} -> {} rows'.format(
            REAL_WEIGHT, len(weighted_real)))

        combined = pd.concat([weighted_real, synth_df], ignore_index=True)
        print('[INFO] Combined dataset: {} rows'.format(len(combined)))

        # ── 5. Normalize categorical columns ────────────────────
        for col in CAT_COLS:
            if col not in combined.columns:
                combined[col] = 'Unknown'
            combined[col] = combined[col].fillna('Unknown').astype(str)

        # ── 6. Single one-hot encode pass ───────────────────────
        combined_encoded = pd.get_dummies(
            combined, columns=CAT_COLS, prefix=CAT_COLS, dtype=int
        )

        # ── 7. Feature matrix ───────────────────────────────────
        DROP = ['yield_tons_ha', 'yield_class', '_source']
        feature_names = [c for c in combined_encoded.columns if c not in DROP]

        X_raw = combined_encoded[feature_names].copy()
        y = combined_encoded['yield_class'].values
        X_raw = X_raw.fillna(0)

        # Drop any rows with missing target
        valid = pd.notna(y)
        X_raw = X_raw[valid].reset_index(drop=True)
        y = y[valid]

        # ── 8. Scale numeric features ───────────────────────────
        NUM_COLS = ['variety_maturity_days', 'variety_max_yield', 'land_area_ha',
                    'fertilizer_kg_ha', 'historical_yield_tons_ha',
                    'temperature_avg', 'rainfall_mm', 'humidity_avg']

        scaler = StandardScaler()
        X_scaled = X_raw.copy()
        X_scaled[NUM_COLS] = scaler.fit_transform(X_raw[NUM_COLS])

        # ── 9. Encode target ────────────────────────────────────
        target_encoder = LabelEncoder()
        y_enc = target_encoder.fit_transform(y)

        # ── 10. Train / test split ──────────────────────────────
        Xtr, Xte, ytr, yte = train_test_split(
            X_scaled, y_enc,
            test_size=0.20, random_state=42, stratify=y_enc
        )

        # ── 11. Train classifier ────────────────────────────────
        print('[INFO] Training RandomForestClassifier...')
        clf = RandomForestClassifier(
            n_estimators=800,
            max_depth=30,
            min_samples_split=3,
            min_samples_leaf=1,
            max_features='sqrt',
            class_weight='balanced',
            random_state=42,
            n_jobs=-1,
        )
        clf.fit(Xtr, ytr)

        y_pred = clf.predict(Xte)
        acc = accuracy_score(yte, y_pred)

        precision, recall, f1, _ = precision_recall_fscore_support(
            yte, y_pred, average=None,
            labels=list(range(len(target_encoder.classes_))),
            zero_division=0,
        )
        cm = confusion_matrix(
            yte, y_pred, labels=list(range(len(target_encoder.classes_)))).tolist()

        classes = target_encoder.classes_.tolist()
        feature_importance = dict(
            zip(feature_names, clf.feature_importances_.tolist()))

        print('[OK] Training complete - accuracy: {:.4f}'.format(acc))
        for i, cls in enumerate(classes):
            print('     {:<10} P={:.3f}  R={:.3f}  F1={:.3f}'.format(
                cls, precision[i], recall[i], f1[i]))

        # ── 12. Save artifacts ──────────────────────────────────
        os.makedirs(args.artifacts_dir, exist_ok=True)

        versioned_prefix = 'model_v{}'.format(args.version)
        model_path  = os.path.join(args.artifacts_dir, '{}_rf.pkl'.format(versioned_prefix))
        scaler_path = os.path.join(args.artifacts_dir, '{}_scaler.pkl'.format(versioned_prefix))
        enc_path    = os.path.join(args.artifacts_dir, '{}_target_encoder.pkl'.format(versioned_prefix))
        feat_path   = os.path.join(args.artifacts_dir, '{}_feature_names.pkl'.format(versioned_prefix))

        joblib.dump(clf, model_path)
        joblib.dump(scaler, scaler_path)
        joblib.dump(target_encoder, enc_path)
        joblib.dump(feature_names, feat_path)

        print('[OK] Saved model: {}'.format(model_path))

        # ── 13. Update current_model.json pointer ───────────────
        pointer = {
            'version':            args.version,
            'model_path':         model_path,
            'scaler_path':        scaler_path,
            'encoder_path':       enc_path,
            'feature_names_path': feat_path,
            'trained_at':         datetime.now().isoformat(),
            'overall_accuracy':   float(acc),
            'training_samples':   int(len(Xtr)),
            'test_samples':       int(len(Xte)),
            'real_samples':       int(len(real_df_clean)),
            'synthetic_samples':  int(len(synth_df)),
        }

        with open(args.current_pointer, 'w') as f:
            json.dump(pointer, f, indent=2)

        print('[OK] Updated pointer: {}'.format(args.current_pointer))

        # ── 14. Write metadata for Laravel ──────────────────────
        metadata = {
            'version':           args.version,
            'model_path':        model_path,
            'training_samples':  int(len(Xtr)),
            'test_samples':      int(len(Xte)),
            'real_samples':      int(len(real_df_clean)),
            'synthetic_samples': int(len(synth_df)),
            'overall_accuracy':  float(acc),
            'metrics': {
                'classes':             classes,
                'precision_per_class': dict(zip(classes, [float(v) for v in precision])),
                'recall_per_class':    dict(zip(classes, [float(v) for v in recall])),
                'f1_per_class':        dict(zip(classes, [float(v) for v in f1])),
                'confusion_matrix':    cm,
                'feature_importance':  feature_importance,
            },
        }

        with open(args.metadata, 'w') as f:
            json.dump(metadata, f, indent=2)

        print('[OK] Wrote metadata: {}'.format(args.metadata))
        sys.exit(0)

    except Exception as e:
        message = '{}: {}'.format(type(e).__name__, e)
        print('[FAIL] Training failed: {}'.format(message), file=sys.stderr)
        traceback.print_exc()
        write_error_metadata(args.metadata, message)
        sys.exit(1)


if __name__ == '__main__':
    main()