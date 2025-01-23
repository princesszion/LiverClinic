import os
import time
import pandas as pd
import mysql.connector
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler

# Database connection details
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "test",
}

# Function to connect to the MySQL databasepyt
def connect_to_db():
    return mysql.connector.connect(**DB_CONFIG)

# Function to insert data into the MySQL database
def insert_into_table(table_name, file_path, db_connection):
    try:
        # Read CSV file
        data = pd.read_csv(file_path)
        cursor = db_connection.cursor()

        # Get table schema
        cursor.execute(f"DESCRIBE {table_name}")
        table_schema = [column[0] for column in cursor.fetchall()]

        # Check if CSV columns match table schema
        if set(data.columns) != set(table_schema):
            print(f"Schema mismatch for table '{table_name}' and file '{file_path}'. Skipping.")
            return

        # Handle missing values by replacing NaN with None
        data = data.where(pd.notnull(data), None)

        # Create Insert Query
        placeholders = ', '.join(['%s'] * len(table_schema))
        insert_query = f"INSERT INTO {table_name} ({', '.join(table_schema)}) VALUES ({placeholders})"

        # Insert data row by row
        for row in data.itertuples(index=False, name=None):
            cursor.execute(insert_query, row)

        db_connection.commit()
        print(f"Successfully inserted data from '{file_path}' into '{table_name}'.")
    except Exception as e:
        print(f"Error inserting data from '{file_path}': {e}")

# Event handler for file system changes
class FolderEventHandler(FileSystemEventHandler):
    def __init__(self, folder_to_monitor, db_connection):
        self.folder_to_monitor = folder_to_monitor
        self.db_connection = db_connection
        self.processed_files = set()

    def process_file(self, file_path):
        if file_path not in self.processed_files and file_path.endswith(".csv"):
            # Add to processed files to avoid re-processing
            self.processed_files.add(file_path)
            table_name = os.path.splitext(os.path.basename(file_path))[0]
            print(f"Processing file: {file_path}")
            insert_into_table(table_name, file_path, self.db_connection)

    def on_created(self, event):
        if not event.is_directory:
            time.sleep(1)  # Ensure file is fully written
            self.process_file(event.src_path)

    def on_modified(self, event):
        if not event.is_directory:
            time.sleep(1)  # Ensure file is fully written
            self.process_file(event.src_path)

# Main function to monitor the folder
def monitor_folder(folder_to_monitor):
    db_connection = connect_to_db()
    event_handler = FolderEventHandler(folder_to_monitor, db_connection)
    observer = Observer()
    observer.schedule(event_handler, folder_to_monitor, recursive=False)
    observer.start()
    print(f"Monitoring folder: {folder_to_monitor}")

    try:
        while True:
            time.sleep(1)  # Keep the script running
    except KeyboardInterrupt:
        observer.stop()
        print("Stopping folder monitoring...")
    observer.join()
    db_connection.close()

# Replace with the folder path you want to monitor
FOLDER_TO_MONITOR = "C:\\Users\\Hp\OneDrive\\Desktop\\Projects\\LiverClinic"

if __name__ == "__main__":
    monitor_folder(FOLDER_TO_MONITOR)
