import pandas as pd

# Real data from dataset.xlsx — this is your ground truth
real_data = [
    # (farmer, variety, soil, season, seeding, temp, rain, humid, fert, hist_yield, bags, kg_cavan, area_ha)
    ('eduardo_cabonot_baluarte',  'NSIC Rc 222',  'Clay Loam',  'Wet', 'Transplanted',   28.33, 115.82, 76.10,  50,    6.70, 54,  65,    0.50),
    ('marygean_barrientos_rizal', 'NSIC Rc 222',  'Clay Loam',  'Dry', 'Transplanted',   23.83,  90.12, 84.33,  50,    7.02, 55,  65,    0.50),
    ('eduardo_cabonot_baluarte',  'NSIC Rc 402',  'Sandy Loam', 'Dry', 'Direct Seeding', 23.83,  90.12, 84.33, 400,    5.50, 110, 54,    1.00),
    ('lourdes_bernardez_rizal',   'NSIC Rc 402',  'Sandy Loam', 'Wet', 'Direct Seeding', 28.70,  95.37, 75.70, 400,    5.94, 112, 55,    1.00),
    ('lourdes_bernardez_rizal',   'NSIC Rc 402',  'Clay Loam',  'Dry', 'Transplanted',   23.21,  73.25, 83.93, 350,    3.50, 125, 58,    2.00),
    ('angelito_simbri_sagana',    'NSIC Rc 402',  'Clay Loam',  'Wet', 'Transplanted',   28.33, 115.82, 76.10, 350,    3.63, 140, 58,    2.00),
    ('edna_palabay_sagana',       'NSIC Rc 402',  'Clay Loam',  'Dry', 'Transplanted',   23.61,  31.63, 81.53, 200,    6.03, 60,  56,    0.50),
    ('edna_palabay_sagana',       'NSIC Rc 402',  'Clay Loam',  'Wet', 'Transplanted',   28.70,  95.37, 75.70, 200,    6.72, 63,  56,    0.50),
    ('nataniel_dayang_baluarte',  'NSIC Rc 402',  'Clay Loam',  'Dry', 'Direct Seeding', 23.61,  31.63, 81.53, 300,    7.00, 137, 57,    1.00),
    ('nataniel_dayang_baluarte',  'NSIC Rc 402',  'Clay Loam',  'Wet', 'Direct Seeding', 28.70,  95.37, 75.70, 300,    7.81, 128, 57,    1.00),
    ('jowel_castillo_rizal',      'NSIC Rc 402',  'Clay Loam',  'Dry', 'Transplanted',   23.61,  31.63, 81.53, 200,    5.50, 140, 59,    1.50),
    ('arorasyon_delava_rizal',    'NSIC Rc 402',  'Clay Loam',  'Wet', 'Transplanted',   29.16, 100.49, 75.77, 200,    5.70, 142, 59,    1.50),
    ('andy_iglesia_baluarte',     'NSIC Rc 402',  'Clay Loam',  'Dry', 'Transplanted',   23.61,  31.63, 81.53, 300,    5.20, 136, 56.75, 1.50),
    ('aileen_baydid_raniag',      'NSIC Rc 402',  'Clay Loam',  'Wet', 'Transplanted',   28.70,  95.37, 75.70, 200,    6.20, 227, 58,    2.00),
    ('jocelyn_fernandez_rizal',   'NSIC Rc 402',  'Clay Loam',  'Dry', 'Transplanted',   23.83,  90.12, 84.33, 400,    7.00, 120, 61.5,  1.00),
    ('rolando_jose_villa_marcos', 'NSIC Rc 402',  'Clay Loam',  'Wet', 'Transplanted',   23.83,  90.12, 84.33, 400,    7.38, 115, 61.5,  1.00),
    ('angelito_simbri_sagana',    'NSIC Rc 456H', 'Clay Loam',  'Dry', 'Transplanted',   23.83,  90.12, 84.33, 400,    6.70, 120, 58,    1.00),
    ('juan_carlo_gatiwan_rizal',  'NSIC Rc 456H', 'Clay Loam',  'Dry', 'Transplanted',   23.21,  73.25, 83.93, 363.64, 4.70, 263, 56,    3.30),
    ('lucena_pascua_salvador',    'NSIC Rc 456H', 'Clay Loam',  'Dry', 'Transplanted',   24.66,  26.53, 79.49, 200,    4.04, 50,  47,    0.50),
    ('virgie_vicente_san_isidro', 'NSIC Rc 456H', 'Clay Loam',  'Dry', 'Direct Seeding', 23.61,  31.63, 81.53, 235.29, 6.28, 210, 53,    1.70),
    ('rafael_dominador_rizal',    'NSIC Rc 456H', 'Clay Loam',  'Dry', 'Direct Seeding', 23.61,  31.63, 81.53, 250,    5.02, 50,  57,    0.50),
    ('rafael_dominador_rizal',    'NSIC Rc 456H', 'Clay Loam',  'Dry', 'Transplanted',   23.61,  31.63, 81.53, 300,    4.30, 125, 39,    1.194),
    ('hermones_buidid_rizal',     'NSIC Rc 456H', 'Clay Loam',  'Dry', 'Transplanted',   23.21,  73.25, 83.93, 100,    5.80, 270, 54.5,  2.40),
    ('juan_carlo_gatiwan_rizal',  'NSIC Rc 486H', 'Sandy',      'Dry', 'Direct Seeding', 23.21,  73.25, 83.93, 200,    7.31, 75,  56,    0.50),
    ('virgelita_vilardo_sagana',  'NSIC Rc 456H', 'Clay Loam',  'Wet', 'Transplanted',   28.06,  79.35, 82.14, 200,    4.40, 80,  53,    1.00),
    ('mario_cataina_sagana',      'NSIC Rc 402',  'Clay Loam',  'Dry', 'Transplanted',   24.92,  43.88, 84.25, 200,    4.40, 67,  56,    0.60),
]

df = pd.DataFrame(real_data, columns=[
    'farmer', 'variety', 'soil_type', 'season', 'seeding_method',
    'temperature_avg', 'rainfall_mm', 'humidity_avg', 'fertilizer_kg_ha',
    'historical_yield_tons_ha', 'harvest_bags', 'kg_per_cavan', 'farm_area_ha'
])
df['yield_tons_ha'] = (df['harvest_bags'] * df['kg_per_cavan'] / 1000) / df['farm_area_ha']

# Keep only ML-relevant columns
df_ml = df[['variety', 'soil_type', 'season', 'seeding_method',
            'temperature_avg', 'rainfall_mm', 'humidity_avg',
            'fertilizer_kg_ha', 'historical_yield_tons_ha', 'yield_tons_ha']]

df_ml.to_csv('rice_yield_real.csv', index=False)

print(f"✅ Saved {len(df_ml)} real farm records to rice_yield_real.csv")
print(f"   Yield: mean={df_ml['yield_tons_ha'].mean():.2f}, "
      f"std={df_ml['yield_tons_ha'].std():.2f}, "
      f"range=[{df_ml['yield_tons_ha'].min():.2f}, {df_ml['yield_tons_ha'].max():.2f}]")
print(f"   Historical: mean={df_ml['historical_yield_tons_ha'].mean():.2f}, "
      f"max={df_ml['historical_yield_tons_ha'].max():.2f}")
print()
print(df_ml.describe().round(2))