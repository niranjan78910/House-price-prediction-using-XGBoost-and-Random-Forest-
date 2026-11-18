# Prop Flow AI — Property Management and House Price Prediction

A final-year BSc Computer Science project combining a PHP/MySQL property-management prototype with a Python/Flask house-price prediction API.

> **Project status:** Academic prototype for local development. It has not been verified as production-ready. Model performance figures should be reported only after a reproducible full-data evaluation.

## What it does

- Displays public property listings and captures enquiries.
- Supports agent registration/login, dashboards, property management and follow-up tasks.
- Stores application records in MySQL.
- Requests an estimated property price from a Python REST API.

## Technology

| Layer | Technology |
| --- | --- |
| Web application | PHP, HTML/CSS/JavaScript |
| Database | MySQL |
| Prediction service | Python, Flask, joblib |
| Model training | Pandas, NumPy, scikit-learn, XGBoost |
| Models | Random Forest Regressor and XGBoost Regressor |

## Architecture

```text
Browser
  |
  v
PHP application (public/ + backend/)
  |                 |
  v                 v
MySQL database   Flask API (POST /predict)
(database/)          |
                     v
             Saved XGBoost model
             + categorical encoder
             + postcode-district mapping
```

The PHP application uses `http://127.0.0.1:5001/predict` by default; `PROP_FLOW_AI_PREDICT_URL` can override the prediction endpoint.

## Repository layout

```text
public/         Web entry points and assets
backend/        PHP configuration, middleware, utilities and actions
database/       MySQL schema and migration scripts
ai-model/       Flask API, training/inference code and model artefacts
```

## Run locally

**Prerequisites:** PHP/XAMPP or equivalent PHP server, MySQL, Python and pip. This repository is intended for local development; do not expose the prototype to the public internet without a security review.

1. Create a local MySQL database and import `database/schema.sql`; then apply `database/migration_*.sql` files as needed.
2. Configure the local database connection in `backend/config/db.php`. Never commit real passwords or API keys.
3. Configure a local PHP server with `public/` as the web root.
4. Set up the prediction service:

```bash
cd ai-model
python -m venv .venv
# Linux/macOS: source .venv/bin/activate
# Windows: .venv\\Scripts\\activate
pip install -r requirements.txt
python app.py
```

5. Check `http://127.0.0.1:5001/health`. The local API should return `{"status":"ok"}`.

### Example prediction request

```bash
curl -X POST http://127.0.0.1:5001/predict \
  -H "Content-Type: application/json" \
  -d '{"postcode":"SW1A 1AA","property_type":"F","freehold":"L","floor_area":70,"rooms":3,"year":2024}'
```

The API returns a JSON object with a `predicted_price` field when inference succeeds. Example values are illustrative inputs, not an accuracy guarantee.

## Machine-learning approach

`ai-model/model_training.py` loads local CSV files from `ai-model/data/hpm_la_2024/`, selects price and property attributes, filters incomplete/outlying records and builds features including floor area, room count, transfer year, property type, tenure and postcode-district target encoding.

The updated script uses a 64/16/20 train/validation/test split, trains Random Forest and XGBoost on log-transformed prices, selects the model by validation R², and reports held-out MAE and RMSE in pounds and R² on log prices. It saves the selected model and encoding artefacts used by `ai-model/predictor.py`.

**Important evaluation limitations (to address before publishing a headline score):**
- Full training is the default. Set `QUICK_TEST=1` to restrict training to three CSV files for debugging.
- XGBoost early stopping now monitors the validation split rather than the held-out test split.
- The script now selects between Random Forest and XGBoost using validation R². Training-set target encoding remains an area for improvement because each training row contributes to its own district mean; consider out-of-fold target encoding.
- Dataset size and model scores are not independently reproducible from the checked-in repository because the raw training data is excluded.

## API

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/health` | Service health check |
| POST | `/predict` | Estimate a property price from JSON inputs |

The Flask entry point now disables debug mode and validates basic numeric inputs. Additional schema validation, integration tests, exception handling and a production WSGI configuration are needed before deployment.

## Next improvements

- [ ] Separate training, validation and held-out test datasets.
- [ ] Record reproducible evaluation metrics, dataset provenance and sample counts.
- [ ] Add automated API and preprocessing tests.
- [ ] Add screenshots or a short demonstration video.
- [ ] Review PHP authentication, database access and configuration for security.
- [ ] Document deployment and environment configuration.

## Data and licence

The raw training dataset is intentionally excluded from Git. Review source-data usage terms before redistributing it. No open-source licence is declared in this repository; contact the repository owner for reuse permissions.
