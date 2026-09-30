"""
CROPS dataset generator — XGBoost regression only (target = yield_tons_ha)
"""
import numpy as np
import pandas as pd
import json

np.random.seed(42)

POOL_SIZE = 12000
NOISE_STD = 0.45

VARIETIES = {
    # INBRED
    'Angelica (NSIC Rc122)':           {'cls':'Inbred','gp':(121,121),'avg':(4.70,4.70),'max':(5.00,5.00)},
    'NSIC Rc216 (Tubigan 17)':         {'cls':'Inbred','gp':(112,104),'avg':(6.00,5.70),'max':(9.70,9.30)},
    'NSIC Rc 512 (Tubigan 44)':        {'cls':'Inbred','gp':(113,105),'avg':(5.60,5.60),'max':(10.20,10.10)},
    'NSIC RC 402 (Tubigan 36)':        {'cls':'Inbred','gp':(114,107),'avg':(5.50,5.50),'max':(14.00,14.00)},
    'NSIC Rc 534 (Salinas 29)':        {'cls':'Inbred','gp':(131,130),'avg':(3.10,3.10),'max':(6.70,6.70)},
    'NSIC Rc222 (Tubigan 18)':         {'cls':'Inbred','gp':(114,106),'avg':(6.10,5.70),'max':(10.00,7.90)},
    'NSIC Rc 480':                     {'cls':'Inbred','gp':(107,107),'avg':(3.20,3.20),'max':(4.40,4.40)},
    'NSIC Rc160 (Tubigan 14)':         {'cls':'Inbred','gp':(122,107),'avg':(5.60,5.60),'max':(8.20,8.20)},
    'NSIC Rc440(Tubigan 39)':          {'cls':'Inbred','gp':(109,109),'avg':(5.50,5.50),'max':(10.80,10.80)},
    'PSB Rc18 (Ala)':                  {'cls':'Inbred','gp':(123,123),'avg':(5.10,5.10),'max':(8.10,8.10)},
    # HYBRID
    'NSIC 2016 Rc 456H (Mestiso 78)':  {'cls':'Hybrid','gp':(112,112),'avg':(6.70,6.70),'max':(11.70,11.70)},
    'NSIC Rc234H (MESTISO 27)':        {'cls':'Hybrid','gp':(115,115),'avg':(6.50,6.50),'max':(9.80,9.80)},
    'NSIC Rc 486 (Mestiso 80)':        {'cls':'Hybrid','gp':(113,113),'avg':(6.50,6.50),'max':(13.90,13.90)},
    'NSIC Rc124H (MESTISO 4)':         {'cls':'Hybrid','gp':(118,118),'avg':(5.70,5.70),'max':(9.10,9.10)},
    'NSIC Rc132H (MESTISO 6)':         {'cls':'Hybrid','gp':(113,113),'avg':(5.90,5.90),'max':(8.70,8.70)},
    'NSIC Rc 666H':                    {'cls':'Hybrid','gp':(110,110),'avg':(5.22,5.22),'max':(6.79,6.79)},
    'PSB Rc72H (Mestiso)':             {'cls':'Hybrid','gp':(123,123),'avg':(5.40,5.40),'max':(9.90,9.90)},
    'NSIC Rc204H (Mestiso 20)':        {'cls':'Hybrid','gp':(111,111),'avg':(6.40,6.40),'max':(11.70,11.70)},
}

SOILS   = ['Clay Loam', 'Silty Clay', 'Sandy Loam', 'Clay']
SEASONS = ['Wet', 'Dry']
SEEDING = ['Transplanted', 'Direct Seeded']


def compute_clean_yield(avg_y, max_y, soil, season, seeding,
                        fertilizer, temp, rain, humid, land, historical):
    y = avg_y

    # ── Linear terms (LR baseline captures these) ──
    y += 0.60 * (fertilizer - 110) / 70
    y += 0.60 * (historical - avg_y) / 0.70
    y += 0.40 if season == 'Dry' else -0.25
    y += 0.30 if seeding == 'Transplanted' else 0.0
    y += {'Clay Loam': 0.30, 'Silty Clay': 0.20,
          'Sandy Loam': -0.35, 'Clay': 0.10}[soil]

    # ── Nonlinear / interaction terms (only trees capture these) ──

    # 1. Temperature quadratic: optimum ~27.5°C
    temp_dev = (temp - 27.5) ** 2
    y -= min(0.030 * temp_dev, 1.20)

    # 2. Rainfall asymmetric optimum around 200mm
    if rain < 150:
        y -= (150 - rain) * 0.008
    elif rain > 280:
        y -= (rain - 280) * 0.006

    # 3. Fertilizer diminishing returns above 150 kg/ha
    if fertilizer > 150:
        y -= (fertilizer - 150) * 0.005

    # 4. Dry season + low humidity is doubly punishing
    if season == 'Dry' and humid < 70:
        y -= 0.35

    # 5. High humidity in wet season encourages disease
    if season == 'Wet' and humid > 90:
        y -= 0.30

    # 6. Sandy Loam suffers harder in dry season (soil × season)
    if soil == 'Sandy Loam' and season == 'Dry':
        y -= 0.30

    # 7. Transplanted responds better to generous fertilizer
    if seeding == 'Transplanted' and fertilizer > 130:
        y += 0.20

    # 8. Historical yield amplified by variety's own ceiling
    y += 0.15 * (historical - avg_y) * (max_y / 10.0)

    # 9. Small plots managed more intensively (decreasing returns)
    y += 0.15 * float(np.exp(-land / 2.0))

    return float(np.clip(y, 2.0, max_y))


# ─────────────────────────────────────────────────────────────
print(f"🔄 Generating {POOL_SIZE} samples...\n")

rows = []
for _ in range(POOL_SIZE):
    variety_name = np.random.choice(list(VARIETIES.keys()))
    v = VARIETIES[variety_name]
    seeding = np.random.choice(SEEDING)
    is_t = seeding == 'Transplanted'

    avg_y    = v['avg'][0 if is_t else 1]
    max_y    = v['max'][0 if is_t else 1]
    maturity = v['gp'][0 if is_t else 1]

    soil       = np.random.choice(SOILS)
    season     = np.random.choice(SEASONS)
    land       = np.random.uniform(0.3, 6.0)
    fertilizer = np.random.uniform(40, 180)
    temp       = np.random.normal(27.5, 2.5)
    rain       = np.random.normal(200, 80)
    humid      = np.random.normal(78, 10)
    historical = np.random.normal(avg_y, 0.70)

    clean_y = compute_clean_yield(
        avg_y, max_y, soil, season, seeding,
        fertilizer, temp, rain, humid, land, historical
    )

    rows.append({
        'variety':                  variety_name,
        'classification':           v['cls'],
        'soil_type':                soil,
        'season':                   season,
        'seeding_method':           seeding,
        'variety_maturity_days':    maturity,
        'variety_max_yield':        max_y,
        'variety_avg_yield':        avg_y,
        'land_area_ha':             round(land, 2),
        'fertilizer_kg_ha':         round(fertilizer, 1),
        'historical_yield_tons_ha': round(historical, 2),
        'temperature_avg':          round(temp, 2),
        'rainfall_mm':              round(rain, 1),
        'humidity_avg':             round(humid, 1),
        '_clean_y':                 clean_y,
    })

df = pd.DataFrame(rows)

noise = np.random.normal(0, NOISE_STD, len(df))
df['yield_tons_ha'] = np.clip(
    df['_clean_y'].values + noise, 2.0, df['variety_max_yield'].values
).round(3)

df = df.drop(columns=['_clean_y'])

CAT_COLS = ['variety', 'classification', 'soil_type', 'season', 'seeding_method']
df_encoded = pd.get_dummies(df, columns=CAT_COLS, prefix=CAT_COLS, dtype=int)

legend = {}
for col in df_encoded.columns:
    if col == 'yield_tons_ha':
        continue
    matched = False
    for c in CAT_COLS:
        if col.startswith(f"{c}_"):
            legend[col] = f"{c} == '{col[len(c)+1:]}' (1 = yes, 0 = no)"
            matched = True
            break
    if not matched:
        legend[col] = f"numeric value ({col})"

df_encoded.to_csv('rice_yield_dataset.csv', index=False)
json.dump(legend, open('feature_legend.json', 'w'), indent=2)
pd.DataFrame([{'feature': k, 'meaning': v} for k, v in legend.items()]
).to_csv('feature_legend.csv', index=False)

print(f"✅ Created rice_yield_dataset.csv")
print(f"   Rows    : {len(df_encoded)}")
print(f"   Columns : {len(df_encoded.columns)}")
print(f"   Yield range : {df['yield_tons_ha'].min():.2f} – {df['yield_tons_ha'].max():.2f} t/ha")
print(f"   Avg yield   : {df['yield_tons_ha'].mean():.2f} t/ha")