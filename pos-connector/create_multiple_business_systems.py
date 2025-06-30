#!/usr/bin/env python3
"""
Create Multiple Business Systems to Demonstrate Universal POS Connector Capabilities
This simulates having different types of business software on the same machine
"""

import os
import sqlite3
import json
import csv
from datetime import datetime
from pathlib import Path

def create_restaurant_pos():
    """Simulate a restaurant POS system"""
    print("🍕 Creating Restaurant POS System...")

    folder = Path("restaurant_pos_data")
    folder.mkdir(exist_ok=True)

    # Create restaurant database
    conn = sqlite3.connect(folder / "restaurant_orders.db")
    cursor = conn.cursor()

    cursor.execute('''
        CREATE TABLE IF NOT EXISTS orders (
            order_id TEXT PRIMARY KEY,
            table_number INTEGER,
            order_time TEXT,
            menu_item TEXT,
            quantity INTEGER,
            price REAL,
            server_name TEXT,
            order_status TEXT
        )
    ''')

    restaurant_data = [
        ('ORD001', 5, '2025-06-30 19:30:00', 'Caesar Salad', 1, 12.99, 'Alice', 'completed'),
        ('ORD002', 3, '2025-06-30 20:15:00', 'Grilled Salmon', 1, 24.99, 'Bob', 'completed'),
        ('ORD003', 7, '2025-06-30 20:45:00', 'Margherita Pizza', 2, 16.99, 'Alice', 'preparing'),
    ]

    cursor.executemany('INSERT OR REPLACE INTO orders VALUES (?,?,?,?,?,?,?,?)', restaurant_data)
    conn.commit()
    conn.close()

    print(f"   ✅ Created: {folder}/restaurant_orders.db")

def create_retail_store():
    """Simulate a retail store POS"""
    print("🛍️ Creating Retail Store POS System...")

    folder = Path("retail_store_data")
    folder.mkdir(exist_ok=True)

    # Create retail CSV export
    with open(folder / "store_sales.csv", 'w', newline='') as f:
        writer = csv.writer(f)
        writer.writerow(['Transaction_ID', 'Date', 'Product_SKU', 'Product_Name', 'Quantity', 'Unit_Price', 'Total', 'Payment_Method', 'Cashier'])
        writer.writerow(['TXN001', '2025-06-30', 'SKU001', 'Blue T-Shirt', 2, 19.99, 39.98, 'Credit Card', 'John'])
        writer.writerow(['TXN002', '2025-06-30', 'SKU002', 'Jeans', 1, 49.99, 49.99, 'Cash', 'Mary'])
        writer.writerow(['TXN003', '2025-06-30', 'SKU003', 'Sneakers', 1, 79.99, 79.99, 'Debit Card', 'John'])

    print(f"   ✅ Created: {folder}/store_sales.csv")

def create_auto_shop():
    """Simulate an auto repair shop system"""
    print("🔧 Creating Auto Repair Shop System...")

    folder = Path("auto_shop_data")
    folder.mkdir(exist_ok=True)

    # Create JSON-based invoice system
    invoices = [
        {
            "invoice_id": "INV001",
            "date": "2025-06-30",
            "customer": "Mike Johnson",
            "vehicle": "2020 Toyota Camry",
            "services": [
                {"service": "Oil Change", "cost": 49.99},
                {"service": "Tire Rotation", "cost": 25.00}
            ],
            "total": 74.99,
            "payment_status": "paid"
        },
        {
            "invoice_id": "INV002",
            "date": "2025-06-30",
            "customer": "Sarah Wilson",
            "vehicle": "2018 Honda Civic",
            "services": [
                {"service": "Brake Inspection", "cost": 89.99}
            ],
            "total": 89.99,
            "payment_status": "pending"
        }
    ]

    with open(folder / "service_invoices.json", 'w') as f:
        json.dump(invoices, f, indent=2)

    print(f"   ✅ Created: {folder}/service_invoices.json")

def create_medical_billing():
    """Simulate medical billing software"""
    print("🏥 Creating Medical Billing System...")

    folder = Path("medical_billing_data")
    folder.mkdir(exist_ok=True)

    # Create billing database
    conn = sqlite3.connect(folder / "patient_billing.db")
    cursor = conn.cursor()

    cursor.execute('''
        CREATE TABLE IF NOT EXISTS patient_charges (
            charge_id TEXT PRIMARY KEY,
            patient_name TEXT,
            date_of_service TEXT,
            procedure_code TEXT,
            procedure_description TEXT,
            charge_amount REAL,
            insurance_payment REAL,
            patient_payment REAL,
            status TEXT
        )
    ''')

    medical_data = [
        ('CHG001', 'John Doe', '2025-06-30', '99213', 'Office Visit', 150.00, 120.00, 30.00, 'paid'),
        ('CHG002', 'Jane Smith', '2025-06-30', '99214', 'Extended Visit', 200.00, 160.00, 40.00, 'pending'),
    ]

    cursor.executemany('INSERT OR REPLACE INTO patient_charges VALUES (?,?,?,?,?,?,?,?,?)', medical_data)
    conn.commit()
    conn.close()

    print(f"   ✅ Created: {folder}/patient_billing.db")

def create_salon_booking():
    """Simulate salon booking and payment system"""
    print("💇 Creating Beauty Salon System...")

    folder = Path("salon_booking_data")
    folder.mkdir(exist_ok=True)

    # Create salon appointments with payments
    with open(folder / "salon_transactions.csv", 'w', newline='') as f:
        writer = csv.writer(f)
        writer.writerow(['Appointment_ID', 'Client_Name', 'Service', 'Stylist', 'Date', 'Time', 'Price', 'Payment_Method', 'Status'])
        writer.writerow(['APP001', 'Emma Johnson', 'Haircut & Style', 'Lisa', '2025-06-30', '10:00', 65.00, 'Credit Card', 'completed'])
        writer.writerow(['APP002', 'David Brown', 'Beard Trim', 'Mike', '2025-06-30', '11:30', 25.00, 'Cash', 'completed'])
        writer.writerow(['APP003', 'Anna Davis', 'Hair Color', 'Lisa', '2025-06-30', '14:00', 120.00, 'Debit Card', 'in_progress'])

    print(f"   ✅ Created: {folder}/salon_transactions.csv")

if __name__ == "__main__":
    print("🌍 === CREATING MULTIPLE BUSINESS SYSTEMS ===")
    print("This demonstrates that the Universal POS Connector can detect ANY business software!")
    print()

    # Create different types of business systems
    create_restaurant_pos()
    create_retail_store()
    create_auto_shop()
    create_medical_billing()
    create_salon_booking()

    print()
    print("🎉 CREATED 5 DIFFERENT BUSINESS SYSTEMS:")
    print("  🍕 Restaurant POS (SQLite database)")
    print("  🛍️ Retail Store POS (CSV exports)")
    print("  🔧 Auto Repair Shop (JSON invoices)")
    print("  🏥 Medical Billing (SQLite database)")
    print("  💇 Beauty Salon (CSV transactions)")
    print()
    print("✅ All systems ready for Universal POS Connector detection!")
    print("🚀 Now the connector should detect and extract from ALL of them!")
