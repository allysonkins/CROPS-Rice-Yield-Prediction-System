# ml-service/generate_data.py
import pandas as pd
import numpy as np
import os

np.random.seed(42)

NUM_RECORDS = 5000

# Categories
varieties = ['NSIC Rc 222', 'NSIC Rc 160', 'Dinorado', 'IR64', 'NSIC 218']
variety_bonus = {'NSIC Rc 222': 1.5, 'NSIC Rc 160': 0.3, 'Dinorado': 0.6, 'IR64': 0.2, 'NSIC 218': 1.2}
soil_types = ['Clay Loam', 'Silty Clay', 'Sandy Loam', 'Clay']
soil_multiplier = {'Clay Loam': 1.0, 'Silty Clay': 0.95, 'Sandy Loam': 0.85, 'Clay': 0.90}
seeding_methods = ['Transplanted', 'Direct Seeded']
seasons = ['Wet', 'Dry']

# Generate data (NO barangay, NO farm_id)
data = {
    'variety': np.random.choice(varieties, NUM_RECORDS),
    'soil_type': np.random.choice(soil_types, NUM_RECORDS),
    'season': np.random.choice(seasons, NUM_RECORDS),
    'seeding_method': np.random.choice(seeding_methods, NUM_RECORDS),
}
df = pd.DataFrame(data)

# Continuous features
df['fertilizer_kg_ha'] = np.random.uniform(50, 200, NUM_RECORDS)
df['temperature_avg'] = np.random.uniform(22, 32, NUM_RECORDS)
df['rainfall_mm'] = np.random.uniform(10, 400, NUM_RECORDS)
df.loc[df['season'] == 'Wet', 'rainfall_mm'] = np.random.uniform(150, 400, sum(df['season'] == 'Wet'))
df.loc[df['season'] == 'Dry', 'rainfall_mm'] = np.random.uniform(10, 150, sum(df['season'] == 'Dry'))
df['humidity_avg'] = np.random.uniform(60, 90, NUM_RECORDS)
df['historical_yield_tons_ha'] = np.random.uniform(2.5, 5.5, NUM_RECORDS)

# Calculate target yield
def calculate_yield(row):
    y = 3.5
    y += variety_bonus.get(row['variety'], 0)
    y += soil_multiplier.get(row['soil_type'], 1.0) * 0.3
    fert_effect = (row['fertilizer_kg_ha'] / 120) * 0.5
    y += min(fert_effect, 1.0)
    temp_diff = abs(row['temperature_avg'] - 27)
    y -= temp_diff * 0.08
    rain_diff = abs(row['rainfall_mm'] - 200)
    rain_penalty = (rain_diff / 200) * 0.4
    y -= min(rain_penalty, 0.6)
    if row['seeding_method'] == 'Transplanted':
        y += 0.2
    y += (row['historical_yield_tons_ha'] - 3.5) * 0.15
    y += np.random.normal(0, 0.25)
    return max(2.0, min(7.5, round(y, 2)))

df['yield_tons_ha'] = df.apply(calculate_yield, axis=1)

# Save
df.to_csv('synthetic_rice_data.csv', index=False)
print(f"✅ Generated {len(df)} records (No barangay, No farm_id)")
print(f"   Features: {df.columns.tolist()}")
print(f"   Average Yield: {df['yield_tons_ha'].mean():.2f} t/ha")