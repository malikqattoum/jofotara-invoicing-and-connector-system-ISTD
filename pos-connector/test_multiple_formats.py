#!/usr/bin/env python3

import json
import sys
import os
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.pos_data_mapping import map_pos_to_laravel

def create_test_cases():
    """Create various test cases for different POS formats"""

    test_cases = {
        "generic_pos.json": {
            "invoice_number": "INV-GEN-001",
            "customer": {
                "name": "Generic Customer",
                "email": "generic@example.com",
                "phone": "+962791234567",
                "address": "123 Main St, Amman, Jordan"
            },
            "invoice_date": "2025-01-11",
            "currency": "JOD",
            "items": [
                {
                    "description": "Generic Product 1",
                    "quantity": 2,
                    "unit_price": 15.5,
                    "tax": 16
                },
                {
                    "description": "Generic Product 2",
                    "quantity": 1,
                    "unit_price": 30.0,
                    "tax": 16
                }
            ],
            "notes": "Test invoice for generic POS system"
        },

        "quickbooks_format.json": {
            "TxnDate": "2025-01-11",
            "CustomerRef": {
                "name": "QuickBooks Customer"
            },
            "CustomerMemo": {
                "value": "123456789"
            },
            "CurrencyRef": {
                "value": "JOD"
            },
            "Line": [
                {
                    "DetailType": "SalesItemLineDetail",
                    "Description": "QB Product 1",
                    "Amount": 31.0,
                    "SalesItemLineDetail": {
                        "Qty": 2
                    }
                },
                {
                    "DetailType": "SalesItemLineDetail",
                    "Description": "QB Product 2",
                    "Amount": 45.0,
                    "SalesItemLineDetail": {
                        "Qty": 1
                    }
                }
            ]
        },

        "minimal_format.json": {
            "customerName": "Minimal Customer",
            "date": "2025-01-11",
            "products": [
                {
                    "name": "Simple Product",
                    "quantity": 1,
                    "price": 25.0
                }
            ]
        }
    }

    # Create test directory
    test_dir = Path("test_data")
    test_dir.mkdir(exist_ok=True)

    # Write test files
    for filename, data in test_cases.items():
        filepath = test_dir / filename
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2)
        print(f"✅ Created test case: {filepath}")

    return test_dir

def test_all_formats():
    """Test mapping for all different POS formats"""

    print("🧪 Creating test cases...")
    test_dir = create_test_cases()

    print("\n" + "="*60)
    print("🧪 Testing multiple POS formats...")
    print("="*60)

    success_count = 0
    total_count = 0

    for json_file in test_dir.glob("*.json"):
        total_count += 1
        print(f"\n📁 Testing: {json_file.name}")
        print("-" * 40)

        try:
            with open(json_file, 'r', encoding='utf-8') as f:
                json_data = json.load(f)

            print("📄 Original data:")
            print(json.dumps(json_data, indent=2)[:300] + "..." if len(json.dumps(json_data)) > 300 else json.dumps(json_data, indent=2))

            # Test the mapping
            mapped_data = map_pos_to_laravel(json_data)

            print("\n📋 Mapped Laravel data:")
            print(json.dumps(mapped_data, indent=2))

            # Validate required fields
            required_fields = ['invoice_number', 'customer_name', 'items']
            missing_fields = [field for field in required_fields if not mapped_data.get(field)]

            if missing_fields:
                print(f"⚠️  Warning: Missing required fields: {missing_fields}")
            else:
                print("✅ All required fields present")
                success_count += 1

        except Exception as e:
            print(f"❌ Error testing {json_file.name}: {e}")

    print(f"\n" + "="*60)
    print(f"📊 Test Results: {success_count}/{total_count} successful")
    print("="*60)

    return success_count == total_count

if __name__ == "__main__":
    success = test_all_formats()

    if success:
        print("\n🎉 All format tests completed successfully!")
    else:
        print("\n⚠️  Some tests had issues - check the output above")
