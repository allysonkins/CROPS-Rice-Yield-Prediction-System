# -*- coding: utf-8 -*-
import sys, io, os

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

SERVICE_DIR   = os.path.dirname(os.path.abspath(__file__))
CURRENT_MODEL = os.path.join(SERVICE_DIR, 'current_model.json')

LEGACY_MODEL      = os.path.join(SERVICE_DIR, 'model_xgb.pkl')
LEGACY_SCALER     = os.path.join(SERVICE_DIR, 'scaler.pkl')
LEGACY_FEATS      = os.path.join(SERVICE_DIR, 'feature_names.pkl')
LEGACY_INTERVAL   = os.path.join(SERVICE_DIR, 'interval_scale.pkl')
LEGEND_FILE       = os.path.join(SERVICE_DIR, 'feature_legend.json')

NUM_COLS_SCALED = [
    'variety_maturity_days', 'variety_max_yield', 'land_area_ha',
    'fertilizer_kg_ha', 'historical_yield_tons_ha',
    'temperature_avg', 'rainfall_mm', 'humidity_avg',
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

SEASON_MAP  = {'dry season':'Dry','wet season':'Wet','dry':'Dry','wet':'Wet'}
SEEDING_MAP = {
    'transplanted':'Transplanted',
    'direct-seeded':'Direct Seeded',
    'direct seeded':'Direct Seeded',
    'directseeded':'Direct Seeded',
}


def _derive_sibling(main_path, suffix):
    base, ext = os.path.splitext(main_path)
    return f"{base}{suffix}{ext}"


class ModelState:
    def __init__(self):
        self.model = None
        self.model_low = None
        self.model_high = None
        self.scaler = None
        self.feature_names = []
        self.legend = {}
        self.interval_scale = 1.0
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
                    meta.get('feature_names_path'),
                    meta.get('interval_scale_path'),
                    meta.get('version'),
                )
            except Exception as e:
                print('[WARN] Could not read current_model.json: {}'.format(e))
        return (LEGACY_MODEL, LEGACY_SCALER, LEGACY_FEATS, LEGACY_INTERVAL, 'baseline')

    def ensure_loaded(self):
        mtime = self._pointer_mtime()
        if self.model is not None and mtime == self._mtime:
            return

        model_p, scaler_p, feat_p, interval_p, version = self._resolve_paths()

        if not model_p or not os.path.isfile(model_p):
            print('[ERR] Model file not found at: {}'.format(model_p))
            return

        try:
            print('[LOAD] Loading model version: {}'.format(version))
            self.model = joblib.load(model_p)
            self.scaler = joblib.load(scaler_p) if scaler_p and os.path.isfile(scaler_p) else None
            self.feature_names = list(joblib.load(feat_p)) if feat_p and os.path.isfile(feat_p) else []
            self.version = version

            low_p  = _derive_sibling(model_p, '_low')
            high_p = _derive_sibling(model_p, '_high')
            self.model_low  = joblib.load(low_p)  if os.path.isfile(low_p)  else None
            self.model_high = joblib.load(high_p) if os.path.isfile(high_p) else None

            if interval_p and os.path.isfile(interval_p):
                try:
                    self.interval_scale = float(joblib.load(interval_p))
                except Exception:
                    self.interval_scale = 1.0
            else:
                self.interval_scale = 1.0

            if self.model_low is None or self.model_high is None:
                print('[WARN] Quantile models missing — confidence will be unavailable')
            else:
                print('[OK] Quantile models loaded (10th / 90th percentile)')
                print('[OK] Interval calibration factor: {:.4f}'.format(self.interval_scale))

            if os.path.isfile(LEGEND_FILE):
                try:
                    with open(LEGEND_FILE, encoding='utf-8') as f:
                        self.legend = json.load(f)
                except Exception:
                    self.legend = {}

            self._mtime = mtime
            print('[OK] Model loaded - {} features'.format(len(self.feature_names)))
        except Exception as e:
            print('[ERR] Failed to load model: {}'.format(e))


STATE = ModelState()
STATE.ensure_loaded()


def normalize_categoricals(data):
    out = dict(data)
    if 'season' in out:
        k = str(out['season']).strip().lower()
        out['season'] = SEASON_MAP.get(k, out['season'])
    if 'seeding_method' in out:
        k = str(out['seeding_method']).strip().lower()
        out['seeding_method'] = SEEDING_MAP.get(k, out['seeding_method'])
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


def compute_confidence(point, low, high):
    """
    Confidence = 1 - (half_width / point)

    half_width is the ± uncertainty of the prediction interval.
    Using half-width (not full width) avoids double-counting the
    two-sided nature of the interval.

    Example: point=4.15, range=[3.03, 5.28]
      half_width = 1.125
      confidence = 1 - 1.125/4.15 = 0.729  →  73%
    """
    point = float(point)
    low   = min(float(low),  point)
    high  = max(float(high), point)

    half_width = (high - low) / 2.0
    denom = max(abs(point), 1.0)

    return float(np.clip(1.0 - (half_width / denom), 0.0, 1.0))


def _extract_trained_varieties():
    numeric_fields = {
        'variety_maturity_days',
        'variety_max_yield',
        'variety_avg_yield',
    }
    out = set()
    for feat in STATE.legend.keys():
        if not isinstance(feat, str):
            continue
        if not feat.startswith('variety_'):
            continue
        if feat in numeric_fields:
            continue
        out.add(feat[len('variety_'):])
    return sorted(out)


@app.route('/', methods=['GET'])
def index():
    return jsonify({
        "service":  "CROPS ML Service",
        "status":   "running",
        "version":  STATE.version,
        "endpoints": [
            "GET  /health",
            "GET  /features",
            "GET  /legend",
            "POST /predict",
            "POST /reload",
        ],
    })


@app.route('/health', methods=['GET'])
def health():
    STATE.ensure_loaded()
    return jsonify({
        "status":         "CROPS ML Service is running!",
        "version":        STATE.version,
        "model":          "XGBoost Regressor",
        "quantile":       STATE.model_low is not None and STATE.model_high is not None,
        "interval_scale": STATE.interval_scale,
    })


@app.route('/legend', methods=['GET'])
def get_legend():
    return jsonify(STATE.legend)


@app.route('/features', methods=['GET'])
def features():
    STATE.ensure_loaded()
    return jsonify({
        "features":         STATE.legend,
        "feature_legend":   STATE.legend,
        "feature_names":    list(STATE.feature_names),
        "varieties":        _extract_trained_varieties(),
        "model_version":    STATE.version,
        "feature_count":    len(STATE.feature_names),
        "quantile_enabled": STATE.model_low is not None and STATE.model_high is not None,
        "interval_scale":   STATE.interval_scale,
    })


@app.route('/reload', methods=['POST'])
def reload_model():
    STATE._mtime = None
    STATE.ensure_loaded()
    return jsonify({
        "success":        True,
        "message":        "Model reloaded",
        "version":        STATE.version,
        "features":       len(STATE.feature_names),
        "interval_scale": STATE.interval_scale,
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

        if STATE.model is None:
            return jsonify({"error": "Model not loaded"}), 503

        point = float(STATE.model.predict(vec)[0])

        confidence = None
        y_low = None
        y_high = None

        if STATE.model_low is not None and STATE.model_high is not None:
            raw_low  = float(STATE.model_low.predict(vec)[0])
            raw_high = float(STATE.model_high.predict(vec)[0])

            if raw_low > raw_high:
                raw_low, raw_high = raw_high, raw_low

            half_width = (raw_high - raw_low) / 2.0
            half_width *= STATE.interval_scale

            y_low  = point - half_width
            y_high = point + half_width

            confidence = round(compute_confidence(point, y_low, y_high), 4)
            y_low  = round(max(0.0, y_low), 2)
            y_high = round(max(0.0, y_high), 2)

        return jsonify({
            "Predicted_Yield": round(max(0.0, point), 2),
            "Confidence":      confidence,
            "Yield_Lower":     y_low,
            "Yield_Upper":     y_high,
            "Model":           "XGBoost Regressor",
            "Model_Version":   STATE.version,
            "success":         True,
        })

    except Exception as e:
        print('[ERR] {}'.format(e))
        import traceback
        traceback.print_exc()
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    print(f'[START] CROPS ML Service on http://0.0.0.0:{port}')
    app.run(debug=False, host='0.0.0.0', port=port, use_reloader=False)