import pandas as pd
import numpy as np
import joblib
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import OrdinalEncoder
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score
from xgboost import XGBRegressor


# CONFIG: set data/model paths here
import os
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_FOLDER = os.path.join(SCRIPT_DIR, "data/hpm_la_2024")
MODEL_PATH  = os.path.join(SCRIPT_DIR, "price_predictor.pkl")

# -----------------------------------------------

import glob
import os

print("Step 1: Loading all CSV files from folder...")

# QUICK TEST MODE: set to True to only use a small number of files for fast debugging
# Set QUICK_TEST = False to use all data for your final run
QUICK_TEST = os.environ.get('QUICK_TEST', '0') == '1'
N_FILES = 3  # Number of CSVs to load in quick test mode


# **************************************************
# STEP 1: LOAD AND COMBINE ALL CSV FILES
# **************************************************
import glob
import os
all_files = sorted(glob.glob(os.path.join(DATA_FOLDER, "*.csv")))
if QUICK_TEST:
    all_files = all_files[:N_FILES]
if not all_files:
    raise RuntimeError("No CSV files found in {}".format(DATA_FOLDER))
df = pd.concat([pd.read_csv(f, low_memory=False) for f in all_files], ignore_index=True)
print(f"Loaded {len(df):,} rows from {len(all_files)} files.")
# -----------------------------------------------
# STEP 2: Select and clean columns
#
# This dataset already has price + floor area
# in one file (Land Registry merged with EPC).
# We only need 7 columns for a good model.
# -----------------------------------------------
print("\nStep 2: Selecting columns and cleaning...")

df = df[[
    "price",            # how much the house sold for  (TARGET)
    "tfarea",           # size of the house in sqm
    "numberrooms",      # number of rooms
    "propertytype",     # D=Detached S=Semi T=Terraced F=Flat
    "duration",         # F=Freehold  L=Leasehold
    "postcode",         # location
    "dateoftransfer"    # sale date - we extract year from this
]].copy()

# rename columns to be simpler
df.columns = ["price", "floor_area", "rooms",
              "property_type", "freehold", "postcode", "date"]

# convert to numbers where needed
df["price"]      = pd.to_numeric(df["price"],      errors="coerce")
df["floor_area"] = pd.to_numeric(df["floor_area"], errors="coerce")
df["rooms"]      = pd.to_numeric(df["rooms"],      errors="coerce")

# drop any rows with missing values
df = df.dropna()

# get the year - house prices change a lot year to year
df["year"] = pd.to_datetime(df["date"]).dt.year
df = df.drop(columns=["date"])

# only use data from 2010 onwards - more relevant for today
df = df[df["year"] >= 2010]

# clean up text
df["postcode"]      = df["postcode"].str.strip().str.upper()
df["property_type"] = df["property_type"].str.strip().str.upper()
df["freehold"]      = df["freehold"].str.strip().str.upper()

# remove extreme price outliers - likely data errors
p01 = df["price"].quantile(0.01)
p99 = df["price"].quantile(0.99)
df  = df[(df["price"] >= p01) & (df["price"] <= p99)]

# remove unrealistic floor areas
df = df[(df["floor_area"] >= 10) & (df["floor_area"] <= 1000)]

# postcode district is "SW1A" from "SW1A 1AA" - good level for location
df["postcode_district"] = df["postcode"].str.split().str[0]

print(f"  Rows after cleaning: {len(df):,}")
print(f"  Price range: £{df['price'].min():,.0f} to £{df['price'].max():,.0f}")
print(f"  Avg floor area: {df['floor_area'].mean():.0f} sqm")
print(f"  Avg rooms: {df['rooms'].mean():.1f}")
print(f"  Year range: {df['year'].min()} to {df['year'].max()}")
print(f"  Property types: {df['property_type'].value_counts().to_dict()}")


# -----------------------------------------------
# STEP 3: Train / test split
#
# Split BEFORE encoding so the test set never
# influences the training calculations
# -----------------------------------------------
print("\nStep 3: Splitting data (64% train, 16% validation, 20% test)...")
train_val_df, test_df = train_test_split(df, test_size=0.2, random_state=42)
train_df, val_df = train_test_split(train_val_df, test_size=0.2, random_state=42)
train_df = train_df.copy()
val_df = val_df.copy()
test_df = test_df.copy()

# we predict log(price) 
# log makes it more normal
y_train = np.log(train_df["price"])
y_val = np.log(val_df["price"])
y_test  = np.log(test_df["price"])

print(f"  Train: {len(train_df):,} rows")
print(f"  Validation: {len(val_df):,} rows")
print(f"  Test:  {len(test_df):,} rows")


# -----------------------------------------------
# STEP 4: Encode the location feature
#
# We replace each postcode district (e.g. "SW1A")
# with the average log price for that area.
# This is called target encoding.
# We calculate averages on TRAINING data only.
# -----------------------------------------------
print("\nStep 4: Encoding location feature...")

global_mean = y_train.mean()

# calculate average price per district from training set only
district_stats = (
    pd.DataFrame({"dist": train_df["postcode_district"].values,
                  "y":    y_train.values})
    .groupby("dist")["y"]
    .agg(["mean", "count"])
)

# smoothing: rare postcodes get pulled towards the overall average
# this stops the model from overfitting on districts with very few sales
district_stats["enc"] = (
    (district_stats["mean"] * district_stats["count"] + global_mean * 10)
    / (district_stats["count"] + 10)
)

train_df["district_te"] = (train_df["postcode_district"]
                           .map(district_stats["enc"]).fillna(global_mean))
val_df["district_te"] = (val_df["postcode_district"]
                          .map(district_stats["enc"]).fillna(global_mean))
test_df["district_te"]  = (test_df["postcode_district"]
                           .map(district_stats["enc"]).fillna(global_mean))

print(f"  Encoded {df['postcode_district'].nunique():,} unique postcode districts")

# ordinal encode property type and freehold
# just turns "D" into 0, "F" into 1, etc.
cat_cols  = ["property_type", "freehold"]
enc       = OrdinalEncoder(handle_unknown="use_encoded_value", unknown_value=-1)
train_cat = pd.DataFrame(enc.fit_transform(train_df[cat_cols]),
                         columns=cat_cols, index=train_df.index)
val_cat = pd.DataFrame(enc.transform(val_df[cat_cols]),
                       columns=cat_cols, index=val_df.index)
test_cat  = pd.DataFrame(enc.transform(test_df[cat_cols]),
                         columns=cat_cols, index=test_df.index)

# final feature table - six features total
num_cols = ["floor_area", "rooms", "year", "district_te"]

X_train = pd.concat([
    train_df[num_cols].reset_index(drop=True),
    train_cat.reset_index(drop=True)
], axis=1)

X_val = pd.concat([
    val_df[num_cols].reset_index(drop=True),
    val_cat.reset_index(drop=True)
], axis=1)

X_test = pd.concat([
    test_df[num_cols].reset_index(drop=True),
    test_cat.reset_index(drop=True)
], axis=1)

# fill any leftover missing values with median
X_train = X_train.fillna(X_train.median())
X_val = X_val.fillna(X_train.median())
X_test  = X_test.fillna(X_train.median())

print(f"  Features ({X_train.shape[1]}): {X_train.columns.tolist()}")


# -----------------------------------------------
# STEP 5: Train both models
# -----------------------------------------------
print("\nStep 5: Training models...")

print("  Training Random Forest...")
rf = RandomForestRegressor(
    n_estimators     = 300,
    min_samples_leaf = 2,
    max_features     = "sqrt",
    random_state     = 42,
    n_jobs           = -1
)
rf.fit(X_train, y_train)
print("  Random Forest done.")

print("  Training XGBoost...")
xgb = XGBRegressor(
    n_estimators          = 1000,
    learning_rate         = 0.03,
    max_depth             = 7,
    subsample             = 0.8,
    colsample_bytree      = 0.8,
    random_state          = 42,
    n_jobs                = -1,
    verbosity             = 0,
    early_stopping_rounds = 50
)
xgb.fit(X_train, y_train, eval_set=[(X_val, y_val)], verbose=False)
print("  XGBoost done.")


# -----------------------------------------------
# STEP 6: Evaluate both models
# -----------------------------------------------
print("\nStep 6: Evaluating models...")

def evaluate(model, name, X, y):
    pred_log = model.predict(X)
    pred     = np.exp(pred_log)
    y_real   = np.exp(y)
    mae      = mean_absolute_error(y_real, pred)
    rmse     = np.sqrt(((y_real - pred) ** 2).mean())
    r2       = r2_score(y, pred_log)
    status   = "PASS" if r2 >= 0.80 else "needs improvement"
    print(f"\n  {name}")
    print(f"    MAE  : £{mae:,.0f}")
    print(f"    RMSE : £{rmse:,.0f}")
    print(f"    R2   : {r2:.4f}  [{status}]")
    return r2

print("Validation results (for model selection):")
r2_rf_val = evaluate(rf, "Random Forest", X_val, y_val)
r2_xgb_val = evaluate(xgb, "XGBoost", X_val, y_val)
best_model = rf if r2_rf_val > r2_xgb_val else xgb
best_name = "Random Forest" if r2_rf_val > r2_xgb_val else "XGBoost"
print("\nHeld-out test results (report these):")
test_r2 = evaluate(best_model, best_name, X_test, y_test)

print("\n  Feature importance (XGBoost):")
fi = pd.Series(xgb.feature_importances_, index=X_train.columns)
for feat, imp in fi.sort_values(ascending=False).items():
    bar = "*" * int(imp * 400)
    print(f"    {feat:<22} {imp:.4f}  {bar}")


# -----------------------------------------------
# STEP 7: Save the best model
# -----------------------------------------------
print("\nStep 7: Saving best model...")


joblib.dump(best_model, MODEL_PATH)
print(f"  Selected model: {best_name} (held-out log-price R2 = {test_r2:.4f})")


# Save encoders as before
joblib.dump(enc, os.path.join(SCRIPT_DIR, "ordinal_encoder.pkl"))
joblib.dump(district_stats["enc"], os.path.join(SCRIPT_DIR, "district_encode_map.pkl"))

print(f"  Saved to: price_predictor.pkl")
print("\nDONE")
