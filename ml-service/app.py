# -*- coding: utf-8 -*-
import sys, io, os

# Force UTF-8 stdout/stderr so Windows cp1252 (and any other narrow encoding)
# doesn't crash on non-ASCII output.
if hasattr(sys.stdout, 'buffer'):
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib
import json
import pandas as pd
import numpy as np

app = Flask(__name__)
CORS(app)

# ─────────────────────────────────────────────────────────────
# Paths
# ─────────────────────────────────────────────────────────────
SERVICE_DIR   = os.path.dirname(os.path.abspath(__file__))
CURRENT_MODEL = os.path.join(SERVICE_DIR, 'current_model.json')

LEGACY_MODEL   = os.path.join(SERVICE_DIR, 'model_rf.pkl')
LEGACY_SCALER  = os.path.join(SERVICE_DIR, 'scaler.pkl')
LEGACY_ENCODER = os.path.join(SERVICE_DIR, 'target_encoder.pkl')
LEGACY_FEATS   = os.path.join(SERVICE_DIR, 'feature_names.pkl')
LEGEND_FILE    = os.path.join(SERVICE_DIR, 'feature_legend.json')

# ─────────────────────────────────────────────────────────────
# Feature schema (must match generate_dataset.py / retrain.py)
# ─────────────────────────────────────────────────────────────
NUM_COLS_SCALED = [
    'variety_maturity_days',
    'variety_max_yield',
    'land_area_ha',
    'fertilizer_kg_ha',
    'historical_yield_tons_ha',
    'temperature_avg',
    'rainfall_mm',
    'humidity_avg',
]
NUM_COLS_RAW = ['variety_avg_yield']
NUM_COLS     = NUM_COLS_SCALED + NUM_COLS_RAW

CAT_PREFIXES = {
    'variety':        'variety',
    'classification': 'classification',
    'soil_type':      'soil_type',
    'season':         'season',
    'seeding_method': 'seeding_method',
}

CLASS_RATIO = {'Low': 0.75, 'Medium': 1.00, 'High': 1.25}

SEASON_MAP = {
    'dry season': 'Dry', 'wet season': 'Wet',
    'dry': 'Dry', 'wet': 'Wet',
}
SEEDING_MAP = {
    'transplanted':  'Transplanted',
    'direct-seeded': 'Direct Seeded',
    'direct seeded': 'Direct Seeded',
    'directseeded':  'Direct Seeded',
}


# ─────────────────────────────────────────────────────────────
# Hot-reloadable model state
# ─────────────────────────────────────────────────────────────
class ModelState:
    def __init__(self):
        self.model = None
        self.scaler = None
        self.target_encoder = None
        self.feature_names = []
        self.legend = {}
        self.version = None
        self._mtime = None

    def _pointer_mtime(self):
        try:
            return os.path.getmtime(CURRENT_MODEL)
        except OSError:
            return None

    def _resolve_paths(self):
        if os.path.isfile(CURRENT_MODEL):
            try:
                with open(CURRENT_MODEL, encoding='utf-8') as f:
                    meta = json.load(f)
                return (
                    meta.get('model_path'),
                    meta.get('scaler_path'),
                    meta.get('encoder_path'),
                    meta.get('feature_names_path'),
                    meta.get('version'),
                )
            except Exception as e:
                print('[WARN] Could not read current_model.json: {}'.format(e))

        return (LEGACY_MODEL, LEGACY_SCALER, LEGACY_ENCODER, LEGACY_FEATS, 'baseline')

    def ensure_loaded(self):
        mtime = self._pointer_mtime()
        if self.model is not None and mtime == self._mtime:
            return

        model_p, scaler_p, enc_p, feat_p, version = self._resolve_paths()

        if not model_p or not os.path.isfile(model_p):
            print('[ERR] Model file not found at: {}'.format(model_p))
            return

        try:
            print('[LOAD] Loading model version: {}'.format(version))
            self.model = joblib.load(model_p)
            self.scaler = joblib.load(scaler_p) if scaler_p and os.path.isfile(scaler_p) else None
            self.target_encoder = joblib.load(enc_p) if enc_p and os.path.isfile(enc_p) else None
            self.feature_names = list(joblib.load(feat_p)) if feat_p and os.path.isfile(feat_p) else []
            self.version = version

            if os.path.isfile(LEGEND_FILE):
                try:
                    with open(LEGEND_FILE, encoding='utf-8') as f:
                        self.legend = json.load(f)
                except Exception:
                    self.legend = {}

            self._mtime = mtime
            print('[OK] Model loaded - {} features'.format(len(self.feature_names)))
            if self.target_encoder is not None:
                print('     Classes: {}'.format(list(self.target_encoder.classes_)))
        except Exception as e:
            print('[ERR] Failed to load model: {}'.format(e))


STATE = ModelState()
STATE.ensure_loaded()


# ─────────────────────────────────────────────────────────────
# Helpers
# ─────────────────────────────────────────────────────────────
def normalize_categoricals(data):
    out = dict(data)
    if 'season' in out:
        key = str(out['season']).strip().lower()
        out['season'] = SEASON_MAP.get(key, out['season'])
    if 'seeding_method' in out:
        key = str(out['seeding_method']).strip().lower()
        out['seeding_method'] = SEEDING_MAP.get(key, out['seeding_method'])
    return out


def preprocess_input(data, feature_names):
    row = {}

    for raw_field, prefix in CAT_PREFIXES.items():
        raw_val = str(data.get(raw_field, '')).strip()
        for feat in feature_names:
            if feat.startswith('{}_'.format(prefix)):
                onehot_val = feat[len(prefix) + 1:]
                row[feat] = 1 if onehot_val == raw_val else 0

    for col in NUM_COLS:
        try:
            row[col] = float(data.get(col, 0) or 0)
        except (TypeError, ValueError):
            row[col] = 0.0

    for feat in feature_names:
        row.setdefault(feat, 0)

    df = pd.DataFrame([row])[feature_names]

    if STATE.scaler is not None:
        scaled_cols = [c for c in NUM_COLS_SCALED if c in df.columns]
        if scaled_cols:
            df[scaled_cols] = STATE.scaler.transform(df[scaled_cols])

    return df


# ─────────────────────────────────────────────────────────────
# Routes
# ─────────────────────────────────────────────────────────────
@app.route('/health', methods=['GET'])
def health():
    STATE.ensure_loaded()
    return jsonify({
        "status":  "CROPS ML Service is running!",
        "version": STATE.version,
    })


@app.route('/legend', methods=['GET'])
def get_legend():
    return jsonify(STATE.legend)


@app.route('/reload', methods=['POST'])
def reload_model():
    STATE._mtime = None
    STATE.ensure_loaded()
    return jsonify({
        "success": True,
        "message": "Model reloaded",
        "version": STATE.version,
        "features": len(STATE.feature_names),
    })


@app.route('/predict', methods=['POST'])
def predict():
    try:
        STATE.ensure_loaded()

        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON body provided"}), 400

        data = normalize_categoricals(data)
        vec = preprocess_input(data, STATE.feature_names)

        if STATE.model is not None and STATE.target_encoder is not None:
            proba = STATE.model.predict_proba(vec)[0]
            idx = int(np.argmax(proba))
            cls = STATE.target_encoder.inverse_transform([idx])[0]
            conf = float(proba[idx])
            probabilities = {
                STATE.target_encoder.inverse_transform([i])[0]: round(float(p), 4)
                for i, p in enumerate(proba)
            }
        else:
            cls, conf = 'Medium', 0.5
            probabilities = {'Low': 0.33, 'Medium': 0.34, 'High': 0.33}

        raw_avg = data.get('variety_avg_yield')
        avg_y = float(raw_avg) if raw_avg not in (None, '') else 5.0
        ratio = CLASS_RATIO.get(cls, 1.0)
        yield_estimate = round(avg_y * ratio, 2)

        return jsonify({
            "Predicted_Yield": yield_estimate,
            "Predicted_Class": cls,
            "Confidence":      round(conf, 4),
            "Probabilities":   probabilities,
            "Model":           "Random Forest Classifier",
            "Model_Version":   STATE.version,
            "success":         True,
        })

    except Exception as e:
        print('[ERR] {}'.format(e))
        import traceback
        traceback.print_exc()
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    import os
    port = int(os.environ.get('PORT', 5000))
    print(f'[START] CROPS ML Service on http://0.0.0.0:{port}')
    app.run(debug=False, host='0.0.0.0', port=port, use_reloader=False)