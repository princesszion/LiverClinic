from flask import Flask, render_template, request, jsonify
import pandas as pd
import sqlite3
import os
import logging
from datetime import datetime

app = Flask(__name__)
UPLOAD_FOLDER = "uploads"
DB_PATH = "data/liver_clinic.db"

# Set up logging
logging.basicConfig(
    filename="logs/actions.log",
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s"
)

# Ensure upload and data folders exist
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
os.makedirs("data", exist_ok=True)
os.makedirs("logs", exist_ok=True)


# Initialize the database (run once to set up)
def initialize_db():
    schema = """
    CREATE TABLE IF NOT EXISTS user_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        action TEXT,
        details TEXT
    );
    """
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    cursor.executescript(schema)
    conn.commit()
    conn.close()


# Helper function to create tables and insert data
def create_table(table_name, data):
    conn = sqlite3.connect(DB_PATH)
    data.to_sql(table_name, conn, if_exists="replace", index=False)
    conn.close()


# Upload CSV File and Store in Database
@app.route("/upload", methods=["POST"])
def upload_file():
    if "file" not in request.files or request.files["file"].filename == "":
        app.logger.warning("No file uploaded.")
        return jsonify({"error": "No file uploaded."}), 400

    file = request.files["file"]
    file_path = os.path.join(UPLOAD_FOLDER, file.filename)
    table_name = os.path.splitext(file.filename)[0]

    try:
        file.save(file_path)
        app.logger.info(f"File {file.filename} uploaded and saved to {file_path}.")

        data = pd.read_csv(file_path)

        if "date" in data.columns:
            data["date"] = pd.to_datetime(data["date"], errors="coerce").dt.strftime("%Y-%m-%d")
            data = data.dropna(subset=["date"])

        create_table(table_name, data)
        return jsonify({"message": f"Data uploaded and stored in table {table_name}."})

    except Exception as e:
        app.logger.error(f"Error processing file {file.filename}: {e}")
        return jsonify({"error": "Error processing file."}), 500


# List available tables
@app.route("/tables", methods=["GET"])
def list_tables():
    try:
        conn = sqlite3.connect(DB_PATH)
        query = "SELECT name FROM sqlite_master WHERE type='table';"
        tables = pd.read_sql_query(query, conn)
        conn.close()
        return jsonify(tables["name"].tolist())
    except Exception as e:
        app.logger.error(f"Error listing tables: {e}")
        return jsonify({"error": "Failed to list tables."}), 500


# Fetch data for charts
@app.route("/data/<table_name>", methods=["GET"])
def get_data(table_name):
    try:
        conn = sqlite3.connect(DB_PATH)
        query = f"""
            SELECT * FROM {table_name}
            WHERE date >= date('now', '-3 years')
            ORDER BY date;
        """
        data = pd.read_sql_query(query, conn)
        conn.close()
        return jsonify(data.to_dict(orient="records"))
    except Exception as e:
        app.logger.error(f"Error fetching data for table {table_name}: {e}")
        return jsonify({"error": "Failed to fetch data."}), 500


# Log user actions
@app.route("/log", methods=["POST"])
def log_action():
    try:
        action = request.json.get("action")
        details = request.json.get("details", "")
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        cursor.execute("INSERT INTO user_logs (action, details) VALUES (?, ?)", (action, details))
        conn.commit()
        conn.close()
        return jsonify({"message": "Action logged successfully."})
    except Exception as e:
        app.logger.error(f"Error logging action: {e}")
        return jsonify({"error": "Failed to log action."}), 500


# Home Page
@app.route("/")
def index():
    return render_template("index.html")


# Dashboard Page
@app.route("/dashboard")
def dashboard():
    return render_template("dashboard.html")


if __name__ == "__main__":
    initialize_db()
    app.run(debug=True)
