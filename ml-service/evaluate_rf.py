# ml-service/evaluate_rf.py
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns
import joblib
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error, mean_absolute_error, r2_score
import warnings
warnings.filterwarnings('ignore')

plt.style.use('seaborn-v0_8-darkgrid')

print("=" * 60)
print("🌾 CROPS - Random Forest Evaluation")
print("=" * 60)

# 1. Load data
df = pd.read_csv('synthetic_rice_data.csv')
print(f"📊 Loaded {len(df)} records")

# 2. Load model
model = joblib.load('model_rf.pkl')
scaler = joblib.load('scaler.pkl')
label_encoders = joblib.load('label_encoders.pkl')
feature_names = joblib.load('feature_names.pkl')
print("✅ Model loaded")

# 3. Prepare data
X = df.drop(columns=['yield_tons_ha'])
y = df['yield_tons_ha']

categorical_cols = ['variety', 'soil_type', 'season', 'seeding_method']
for col in categorical_cols:
    X[col] = label_encoders[col].transform(X[col])

X_scaled = scaler.transform(X)
X = pd.DataFrame(X_scaled, columns=X.columns)

X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# 4. Predict
train_pred = model.predict(X_train)
test_pred = model.predict(X_test)

# 5. Metrics
def metrics(y_true, y_pred, name):
    rmse = np.sqrt(mean_squared_error(y_true, y_pred))
    mae = mean_absolute_error(y_true, y_pred)
    r2 = r2_score(y_true, y_pred)
    mape = np.mean(np.abs((y_true - y_pred) / y_true)) * 100
    return {'Set': name, 'RMSE': rmse, 'MAE': mae, 'R²': r2, 'MAPE (%)': mape}

results = [
    metrics(y_train, train_pred, 'Training'),
    metrics(y_test, test_pred, 'Test')
]
df_results = pd.DataFrame(results).round(4)

print("\n📊 MODEL PERFORMANCE")
print(df_results.to_string(index=False))

# 6. Overfitting check
gap = df_results[df_results['Set']=='Training']['R²'].values[0] - df_results[df_results['Set']=='Test']['R²'].values[0]
print(f"\n🔍 Overfitting Gap: {gap:.4f}")
print("✅ Minimal overfitting" if gap < 0.1 else "⚠️ Some overfitting detected")

# 7. Feature Importance
importance = pd.DataFrame({
    'Feature': X.columns,
    'Importance': model.feature_importances_
}).sort_values('Importance', ascending=False)

print("\n🌲 Top Features:")
print(importance.head(10).to_string(index=False))

# Plot
plt.figure(figsize=(10,6))
plt.barh(importance['Feature'][:10], importance['Importance'][:10], color='#0f4c2b')
plt.xlabel('Importance')
plt.title('Top 10 Features - Random Forest')
plt.gca().invert_yaxis()
plt.tight_layout()
plt.show()

# 8. Actual vs Predicted
fig, axes = plt.subplots(1,2, figsize=(12,5))
axes[0].scatter(y_train, train_pred, alpha=0.5, color='#0f4c2b')
axes[0].plot([y.min(), y.max()], [y.min(), y.max()], 'r--')
axes[0].set_title('Training Set')
axes[0].set_xlabel('Actual')
axes[0].set_ylabel('Predicted')
axes[0].grid(alpha=0.3)

axes[1].scatter(y_test, test_pred, alpha=0.5, color='#c9932f')
axes[1].plot([y.min(), y.max()], [y.min(), y.max()], 'r--')
axes[1].set_title('Test Set')
axes[1].set_xlabel('Actual')
axes[1].set_ylabel('Predicted')
axes[1].grid(alpha=0.3)
plt.suptitle('Random Forest: Actual vs Predicted')
plt.tight_layout()
plt.show()

print("\n✅ Evaluation Complete!")