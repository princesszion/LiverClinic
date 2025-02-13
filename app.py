from flask import Flask, render_template, request, jsonify
import pandas as pd
import mysql.connector
import os
import logging
from datetime import datetime

app = Flask(__name__)
UPLOAD_FOLDER = "uploads"
app.config["UPLOAD_FOLDER"] = UPLOAD_FOLDER

# MySQL Database Configuration
MYSQL_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "test",
}

def get_db_connection():
    return mysql.connector.connect(**MYSQL_CONFIG)

def initialize_db():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS user_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        action TEXT,
        details TEXT
    )
    """)
    conn.commit()
    conn.close()

@app.route("/upload", methods=["POST"])
def upload_file():
    if "file" not in request.files or request.files["file"].filename == "":
        return jsonify({"error": "No file uploaded."}), 400

    file = request.files["file"]
    file_path = os.path.join(app.config["UPLOAD_FOLDER"], file.filename)
    table_name = os.path.splitext(file.filename)[0]

    try:
        file.save(file_path)
        data = pd.read_csv(file_path)
        if "date" in data.columns:
            data["date"] = pd.to_datetime(data["date"], errors="coerce").dt.strftime("%Y-%m-%d")
            data.dropna(subset=["date"], inplace=True)
        create_table(table_name, data)
        return jsonify({"message": f"Data uploaded and stored in table {table_name}."})
    except Exception as e:
        return jsonify({"error": f"Error processing file: {e}"}), 500

def create_table(table_name, data):
    conn = get_db_connection()
    cursor = conn.cursor()
    columns = ", ".join([f"{col} TEXT" for col in data.columns])
    cursor.execute(f"CREATE TABLE IF NOT EXISTS {table_name} ({columns})")
    placeholders = ", ".join(["%s"] * len(data.columns))
    for _, row in data.iterrows():
        cursor.execute(f"INSERT INTO {table_name} ({', '.join(data.columns)}) VALUES ({placeholders})", tuple(row))
    conn.commit()
    conn.close()

@app.route("/tables", methods=["GET"])
def list_tables():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SHOW TABLES")
    tables = [table[0] for table in cursor.fetchall()]
    conn.close()
    return jsonify(tables)

@app.route("/data/<table_name>", methods=["GET"])
def get_data(table_name):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute(f"SELECT * FROM {table_name}")
    data = cursor.fetchall()
    conn.close()
    return jsonify(data) if data else jsonify({"error": "No data found."}), 404

def get_patient_data(patient_id):
    patient_data = {}

    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=False)  # Disable dictionary mode for SHOW TABLES
        cursor.execute("SHOW TABLES")
        tables = cursor.fetchall()

        if not tables:
            app.logger.error("No tables found in the database.")
            return {"error": "No tables found in the database."}

        tables = [table[0] for table in tables]  # Extract table names
        app.logger.info(f"Tables found: {tables}")  # Log tables for debugging

        cursor = conn.cursor(dictionary=True)  # Enable dictionary mode for other queries
        
        for table_name in tables:
            cursor.execute(f"DESCRIBE {table_name}")
            columns = [col["Field"] for col in cursor.fetchall()]  # Fetch field names
            
            if "PAT_MRN_ID" in columns:
                cursor.execute(f"SELECT * FROM {table_name} WHERE PAT_MRN_ID = %s", (patient_id,))
                records = cursor.fetchall()
                
                if records:
                    patient_data[table_name] = records

        conn.close()
        return patient_data

    except mysql.connector.Error as e:
        app.logger.error(f"MySQL error: {e}")
        return {"error": "Database error occurred."}
    
    except Exception as e:
        app.logger.error(f"Error fetching patient data: {e}")
        return {"error": "Unexpected error occurred."}


@app.route("/patient/<patient_id>", methods=["GET"])
def get_patient_data(patient_id):
    patient_data = get_patient_data(patient_id)
    return jsonify(patient_data)

@app.route("/")
def index():
    return render_template("dashboard.html")

@app.route("/dashboard")
def dashboard():
    return render_template("dashboard.html")

if __name__ == "__main__":
    initialize_db()
    app.run(debug=True)
