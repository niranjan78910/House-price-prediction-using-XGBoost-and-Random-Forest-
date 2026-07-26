UK House Price Prediction – BSc Dissertation Project

Overview
--------
This project predicts residential property prices in England and Wales using machine learning (Random Forest and XGBoost). It combines large-scale data processing, model training, a backend for predictions, and a web-based frontend for user interaction.

Folder Structure
----------------
- ai-model/ : Python code for data cleaning, feature engineering, model training, and prediction.
  - model_training.py: Main script for preparing data and training models.
  - predictor.py, app.py: Scripts for making predictions (API or CLI).
  - requirements.txt: Python dependencies.
  - notebooks/: Jupyter notebooks for results, analysis, and visualizations.
  - data/: All CSV data files, including per-local-authority files in hpm_la_2024/.

- backend/ : PHP backend for handling user actions, authentication, and saving predictions.
  - actions/: PHP scripts for each backend action (e.g., predict_action.php).
  - config/: Database and app configuration.
  - middleware/: Authentication and access control.
  - utils/: Helper functions.

- database/ : SQL schema for the MySQL database.
  - schema.sql: Defines tables for properties, users, predictions, etc.

- public/ : Frontend and public-facing PHP files.
  - Main pages: index.php, dashboard.php, login.php, etc.
  - Property/user management: property_add.php, profile.php, etc.
  - assets/: CSS and JS for styling and interactivity.
  - includes/: Shared page components (headers, footers, sidebar).
  - uploads/: For property images.

How to Run (Python Model)
-------------------------
1. Install dependencies:
   pip install -r ai-model/requirements.txt
2. Run model training:
   python ai-model/model_training.py
3. (Optional) Start Flask API (if included):
   python ai-model/app.py

How to Run (PHP Backend & Frontend)
-----------------------------------
1. Place the backend/ and public/ folders in your XAMPP htdocs directory.
2. Start Apache and MySQL from the XAMPP control panel.
3. Import the database schema from database/schema.sql using phpMyAdmin.
4. Access the web app at http://localhost/Prop-Flow-Ai/public/.

