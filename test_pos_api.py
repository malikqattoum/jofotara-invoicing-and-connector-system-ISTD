#!/usr/bin/env python3
"""
Test script for JoFotara POS Connector API
This simulates the Universal POS Connector sending transaction data
"""

import requests
import json
from datetime import datetime

# API Configuration
BASE_URL = "http://127.0.0.1:8000/api/pos-connector"
DEMO_API_KEY = "demo_pizza_api_key_123"  # Demo restaurant

def test_connection():
    """Test API connection"""
    print("🧪 Testing API Connection...")

    headers = {"X-API-Key": DEMO_API_KEY}
    response = requests.get(f"{BASE_URL}/test", headers=headers)

    if response.status_code == 200:
        data = response.json()
        print(f"✅ Connection successful!")
        print(f"   Customer: {data['customer']}")
        print(f"   Customer ID: {data['customer_id']}")
        return True
    else:
        print(f"❌ Connection failed: {response.status_code}")
        print(f"   Response: {response.text}")
        return False

def send_sample_transactions():
    """Send sample restaurant transactions"""
    print("\n🍕 Sending Sample Restaurant Transactions...")

    # Sample restaurant transactions
    transactions = [
        {
            "transaction_id": "POS_TXN_001",
            "transaction_date": datetime.now().isoformat(),
            "customer_name": "John Smith",
            "customer_email": "john@example.com",
            "customer_phone": "+1-555-1234",
            "items": [
                {
                    "description": "Margherita Pizza Large",
                    "quantity": 1,
                    "unit_price": 18.99,
                    "total": 18.99
                },
                {
                    "description": "Caesar Salad",
                    "quantity": 1,
                    "unit_price": 8.99,
                    "total": 8.99
                },
                {
                    "description": "Coca-Cola",
                    "quantity": 2,
                    "unit_price": 2.50,
                    "total": 5.00
                }
            ],
            "subtotal": 32.98,
            "tax_amount": 2.97,
            "total_amount": 35.95,
            "tip_amount": 5.00,
            "payment_method": "Credit Card",
            "payment_reference": "CC_12345",
            "location": "Table 5",
            "employee": "Sarah Johnson",
            "source_pos_system": "Restaurant POS v2.1",
            "source_file": "restaurant_orders.db",
            "notes": "Customer requested extra cheese"
        },
        {
            "transaction_id": "POS_TXN_002",
            "transaction_date": datetime.now().isoformat(),
            "customer_name": "Maria Garcia",
            "customer_email": "maria@example.com",
            "items": [
                {
                    "description": "Pepperoni Pizza Medium",
                    "quantity": 1,
                    "unit_price": 15.99,
                    "total": 15.99
                },
                {
                    "description": "Garlic Bread",
                    "quantity": 1,
                    "unit_price": 4.99,
                    "total": 4.99
                }
            ],
            "subtotal": 20.98,
            "tax_amount": 1.89,
            "total_amount": 22.87,
            "payment_method": "Cash",
            "location": "Takeout",
            "employee": "Mike Wilson",
            "source_pos_system": "Restaurant POS v2.1",
            "source_file": "restaurant_orders.db"
        },
        {
            "transaction_id": "POS_TXN_003",
            "transaction_date": datetime.now().isoformat(),
            "customer_name": "David Brown",
            "items": [
                {
                    "description": "Chicken Alfredo",
                    "quantity": 1,
                    "unit_price": 16.99,
                    "total": 16.99
                }
            ],
            "subtotal": 16.99,
            "tax_amount": 1.53,
            "total_amount": 18.52,
            "payment_method": "Debit Card",
            "location": "Table 12",
            "employee": "Sarah Johnson",
            "source_pos_system": "Restaurant POS v2.1",
            "source_file": "restaurant_orders.db"
        }
    ]

    # Send transactions
    headers = {
        "X-API-Key": DEMO_API_KEY,
        "Content-Type": "application/json"
    }

    payload = {"transactions": transactions}

    response = requests.post(f"{BASE_URL}/transactions",
                           headers=headers,
                           json=payload)

    if response.status_code == 200:
        data = response.json()
        print(f"✅ Transactions sent successfully!")
        print(f"   Processed: {data['processed']}")
        print(f"   Skipped: {data['skipped']}")
        print(f"   Errors: {data['errors']}")

        if data['errors'] > 0:
            print(f"   Error details: {data.get('error_details', [])}")

        return True
    else:
        print(f"❌ Failed to send transactions: {response.status_code}")
        print(f"   Response: {response.text}")
        return False

def send_heartbeat():
    """Send connector heartbeat"""
    print("\n💓 Sending Connector Heartbeat...")

    headers = {
        "X-API-Key": DEMO_API_KEY,
        "Content-Type": "application/json"
    }

    heartbeat_data = {
        "version": "2.0.0",
        "pos_systems": ["Restaurant POS v2.1", "Universal Adapter"],
        "transactions_pending": 0,
        "last_sync": datetime.now().isoformat(),
        "system_info": {
            "os": "Windows 11",
            "connector_uptime": "2 hours",
            "memory_usage": "45 MB"
        }
    }

    response = requests.post(f"{BASE_URL}/heartbeat",
                           headers=headers,
                           json=heartbeat_data)

    if response.status_code == 200:
        data = response.json()
        print(f"✅ Heartbeat sent successfully!")
        print(f"   Status: {data['status']}")
        print(f"   Server time: {data.get('server_time', 'N/A')}")
        return True
    else:
        print(f"❌ Heartbeat failed: {response.status_code}")
        print(f"   Response: {response.text}")
        return False

def get_stats():
    """Get customer statistics"""
    print("\n📊 Getting Customer Statistics...")

    headers = {"X-API-Key": DEMO_API_KEY}
    response = requests.get(f"{BASE_URL}/stats", headers=headers)

    if response.status_code == 200:
        data = response.json()
        print(f"✅ Statistics retrieved!")
        print(f"   Total transactions: {data.get('total_transactions', 0)}")
        print(f"   Today's transactions: {data.get('today_transactions', 0)}")
        print(f"   Total revenue: ${data.get('total_revenue', 0):.2f}")
        print(f"   Invoices created: {data.get('invoices_created', 0)}")
        return True
    else:
        print(f"❌ Failed to get stats: {response.status_code}")
        print(f"   Response: {response.text}")
        return False

def main():
    """Run all API tests"""
    print("🌍 === JOFOTARA POS CONNECTOR API TEST ===")
    print("Testing Universal POS Connector API integration\n")

    success_count = 0
    total_tests = 4

    # Test 1: Connection
    if test_connection():
        success_count += 1

    # Test 2: Send transactions
    if send_sample_transactions():
        success_count += 1

    # Test 3: Send heartbeat
    if send_heartbeat():
        success_count += 1

    # Test 4: Get statistics
    if get_stats():
        success_count += 1

    print(f"\n🎯 === TEST RESULTS ===")
    print(f"✅ Passed: {success_count}/{total_tests} tests")

    if success_count == total_tests:
        print("🎉 ALL TESTS PASSED! Universal POS Connector API is working perfectly!")
        print("\n🚀 Next Steps:")
        print("   1. Build the EXE: python pos-connector/build_exe.py")
        print("   2. Create customer packages: python pos-connector/create_customer_package.py")
        print("   3. Distribute to customers!")
    else:
        print("❌ Some tests failed. Check the API configuration and try again.")

if __name__ == "__main__":
    main()
