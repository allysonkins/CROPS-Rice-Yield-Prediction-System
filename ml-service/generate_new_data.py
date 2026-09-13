"""
CROPS — Calibrated Synthetic Rice Yield Dataset (FINAL v2)
Calibrated to 26 real farm records from Santiago City CAO.
Real: yield mean=6.06, std=1.28, range=[3.62, 8.40]
Real: temp mean=25.21, rainfall mean=70.32, fertilizer mean=259.57
Sources: IRRI Rice Knowledge Bank, FAO fertilizer curves.
"""
import numpy as np
import pandas as pd

np.random.seed(42)
n = 1800   # more rows → better learning

# ---------- 3 varieties (drop rare Rc486H) ----------
varieties = ['NSIC Rc 222', 'NSIC Rc 402', 'NSIC Rc 456H']
variety_probs = [0.20, 0.55, 0.25]
variety_bonus = {
    'NSIC Rc 222':  0.00,
    'NSIC Rc 402':  0.55,
    'NSIC Rc 456H': 1.35,
}

# ---------- 2 soils (drop rare Sandy) ----------
soil_types  = ['Clay Loam', 'Sandy Loam']
soil_probs  = [0.80, 0.20]
soil_bonus  = {'Clay Loam': 0.40, 'Sandy Loam': -0.20}

# ---------- Season & Seeding ----------
seasons         = ['Wet', 'Dry']
season_probs    = [0.55, 0.45]
season_bonus    = {'Wet': 0.20, 'Dry': 0.00}

seeding_methods = ['Transplanted', 'Direct Seeding']
seeding_probs   = [0.70, 0.30]
seeding_bonus   = {'Transplanted': 0.30, 'Direct Seeding': 0.00}

# ---------- Sample ----------
variety = np.random.choice(varieties, n, p=variety_probs)
soil    = np.random.choice(soil_types, n, p=soil_probs)
season  = np.random.choice(seasons, n, p=season_probs)
seeding = np.random.choice(seeding_methods, n, p=seeding_probs)

# ---------- Weather ----------
temperature = np.clip(np.random.normal(25.50, 2.20, n), 22.00, 31.00)
humidity    = np.clip(np.random.normal(80.50, 3.50, n), 70.00, 90.00)

rainfall = np.where(
    season == 'Wet',
    np.random.gamma(2.5, 25.00, n) + 30.00,
    np.random.gamma(2.0, 15.00, n) + 25.00,
)
rainfall = np.clip(rainfall, 20.00, 150.00)

fertilizer = np.clip(np.random.gamma(5.0, 42.00, n) + 50.00, 50.00, 400.00)

hist_base = np.array([0.35 * variety_bonus[v] for v in variety]) + 5.40
historical_yield = np.clip(np.random.normal(hist_base, 0.70, n), 3.50, 7.80)

# ============================================================
# Target yield — STRONGER signal (this is the fix)
# ============================================================
intercept = 3.60

coef_variety = np.array([variety_bonus[v] for v in variety])
coef_soil    = np.array([soil_bonus[s]    for s in soil])
coef_season  = np.array([season_bonus[s]  for s in season])
coef_seeding = np.array([seeding_bonus[s] for s in seeding])

# Fertilizer — STRONGER coefficient (1.60 → 2.20)
fert_effect = 2.20 * (1.00 - np.exp(-(fertilizer - 50.00) / 95.00))

# Temperature — sharper band
temp_effect = np.where(temperature < 25.00, -0.30 * (25.00 - temperature),
              np.where(temperature > 29.00, -0.30 * (temperature - 29.00),
                       0.40))

# Rainfall — sharper band
rain_effect = np.where(rainfall < 80.00, -0.020 * (80.00 - rainfall),
              np.where(rainfall > 120.00, -0.018 * (rainfall - 120.00),
                       0.60))

# Humidity — sharper band
humid_effect = np.where(humidity < 75.00, -0.040 * (75.00 - humidity),
               np.where(humidity > 85.00, -0.040 * (humidity - 85.00),
                        0.00))

# Historical yield — STRONGER (0.50 → 0.70)
hist_effect = 0.70 * (historical_yield - 5.40)

y_signal = (intercept + coef_variety + coef_soil + coef_season + coef_seeding
            + fert_effect + temp_effect + rain_effect + humid_effect + hist_effect)

# LOWER noise — 0.32 → 0.22
noise = np.random.normal(0.00, 0.22, n)
y = y_signal + noise
y = y + (6.06 - y.mean())
y = np.clip(y, 3.00, 9.00)

df = pd.DataFrame({
    'variety': variety, 'soil_type': soil, 'season': season,
    'seeding_method': seeding,
    'temperature_avg':          np.round(temperature, 2),
    'rainfall_mm':              np.round(rainfall, 2),
    'humidity_avg':             np.round(humidity, 2),
    'fertilizer_kg_ha':         np.round(fertilizer, 2),
    'historical_yield_tons_ha': np.round(historical_yield, 2),
    'yield_tons_ha':            np.round(y, 2),
})
df.to_csv('rice_yield_dataset.csv', index=False)

print(f"✅ Synthetic dataset saved: {len(df)} rows")
print(f"   Yield       : mean={y.mean():.2f}  std={y.std():.2f}  "
      f"range=[{y.min():.2f}, {y.max():.2f}]")
print(f"   Historical  : mean={historical_yield.mean():.2f}")
print(f"   Temperature : mean={temperature.mean():.2f}°C")
print(f"   Rainfall    : mean={rainfall.mean():.2f} mm")
print(f"   Fertilizer  : mean={fertilizer.mean():.2f} kg/ha")