# Prop Flow AI

Prop Flow AI is a PHP and Python property management prototype with an AI-assisted price prediction workflow.

## Features

- Public property listings and inquiry capture
- Agent registration, login, dashboard, profile, and owner management
- Property CRUD flows with follow-up tracking
- Python prediction API for property price estimates
- MySQL schema and migration scripts for local setup

## Project Structure

```text
public/      PHP web entry points, assets, and shared includes
backend/     PHP config, middleware, utilities, and actions
database/    MySQL schema and migration SQL
ai-model/    Python model API, training script, model artifacts, and notebooks
```

## Local Setup

1. Import `database/schema.sql` into MySQL and apply any files in `database/` that begin with `migration_`.
2. Update `backend/config/db.php` if your local MySQL credentials differ from the default XAMPP setup.
3. Serve the project from XAMPP with the `public/` directory as the web entry point.
4. Install the Python prediction dependencies:

```bash
cd ai-model
pip install -r requirements.txt
```

5. Start the prediction API:

```bash
python app.py
```

The PHP app defaults to `http://127.0.0.1:5001/predict` for prediction requests. Set `PROP_FLOW_AI_PREDICT_URL` to override it.

## Data

The raw training data in `ai-model/data/` is intentionally ignored because it is large. Keep the data locally or publish it separately with a dataset storage option if needed.
