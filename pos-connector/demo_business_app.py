#!/usr/bin/env python3
"""
Demo Business Application - Simulates ANY business software that the universal connector should detect
This could be a restaurant POS, retail store system, inventory management, billing software, etc.
"""

import sqlite3
import json
import csv
import time
import os
from datetime import datetime
from pathlib import Path

class DemoBusinessApp:
    """Simulates any business application with transaction data"""

    def __init__(self, app_name="Demo Business Manager"):
        self.app_name = app_name
        self.data_dir = Path(__file__).parent / "business_data"
        self.data_dir.mkdir(exist_ok=True)

        # Create various data files that a business app might have
        self.create_database()
        self.create_csv_exports()
        self.create_json_logs()

        print(f"🏪 {self.app_name} started!")
        print(f"📁 Data stored in: {self.data_dir}")

    def create_database(self):
        """Create SQLite database like any business app would"""
        db_path = self.data_dir / "business_transactions.db"

        conn = sqlite3.connect(db_path)
        cursor = conn.cursor()

        # Create typical business tables
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS sales (
                id INTEGER PRIMARY KEY,
                transaction_date TEXT,
                customer_name TEXT,
                item_description TEXT,
                quantity INTEGER,
                unit_price REAL,
                total_amount REAL,
                payment_type TEXT
            )
        ''')

        cursor.execute('''
            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY,
                name TEXT,
                email TEXT,
                phone TEXT,
                total_purchases REAL
            )
        ''')

        # Insert sample business data
        sample_sales = [
            (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), 'John Smith', 'Widget A', 2, 15.99, 31.98, 'Credit Card'),
            (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), 'Jane Doe', 'Service B', 1, 49.99, 49.99, 'Cash'),
            (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), 'Bob Wilson', 'Product C', 3, 12.50, 37.50, 'Check'),
        ]

        cursor.executemany('''
            INSERT INTO sales (transaction_date, customer_name, item_description, quantity, unit_price, total_amount, payment_type)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ''', sample_sales)

        conn.commit()
        conn.close()
        print(f"✅ Created database: business_transactions.db")

    def create_csv_exports(self):
        """Create CSV files like business apps export"""
        csv_path = self.data_dir / "daily_sales_export.csv"

        with open(csv_path, 'w', newline='') as f:
            writer = csv.writer(f)
            writer.writerow(['Date', 'Time', 'Customer', 'Product', 'Amount', 'Payment'])
            writer.writerow([datetime.now().strftime('%Y-%m-%d'), datetime.now().strftime('%H:%M:%S'), 'Alice Johnson', 'Consulting Service', '150.00', 'Invoice'])
            writer.writerow([datetime.now().strftime('%Y-%m-%d'), datetime.now().strftime('%H:%M:%S'), 'Charlie Brown', 'Software License', '299.99', 'Wire Transfer'])

        print(f"✅ Created CSV export: daily_sales_export.csv")

    def create_json_logs(self):
        """Create JSON logs like modern business apps"""
        json_path = self.data_dir / "transaction_log.json"

        transactions = [
            {
                "id": "TXN_001",
                "timestamp": datetime.now().isoformat(),
                "type": "sale",
                "customer": "David Lee",
                "items": [{"name": "Monthly Subscription", "price": 29.99}],
                "total": 29.99,
                "status": "completed"
            },
            {
                "id": "TXN_002",
                "timestamp": datetime.now().isoformat(),
                "type": "refund",
                "customer": "Emma Davis",
                "items": [{"name": "Returned Item", "price": -45.00}],
                "total": -45.00,
                "status": "processed"
            }
        ]

        with open(json_path, 'w') as f:
            json.dump(transactions, f, indent=2)

        print(f"✅ Created JSON log: transaction_log.json")

    def run(self):
        """Simulate running business application"""
        print(f"🔄 {self.app_name} is running...")
        print("📊 Processing business transactions...")
        print("💾 Storing data in multiple formats...")
        print("🌐 Ready for universal POS connector to detect!")

        # Keep running to simulate a real business app
        try:
            while True:
                time.sleep(5)
                print(f"⏰ {datetime.now().strftime('%H:%M:%S')} - {self.app_name} active")
        except KeyboardInterrupt:
            print(f"\n👋 {self.app_name} shutting down...")

if __name__ == "__main__":
    # This simulates ANY business application
    app = DemoBusinessApp("Universal Business Manager Pro")
    app.run()
