#!/usr/bin/env python3

import json
import sys
import os
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.pos_data_mapping import map_pos_to_laravel

def test_json_mapping():
    """Test JSON mapping with our sample file"""

    # Load the JSON file
    json_file = Path("C:/TestPOS/Invoices/test_invoice.json")

    if not json_file.exists():
        print(f"❌ JSON file not found: {json_file}")
        return False

    try:
        with open(json_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # Check for common JSON issues
        if content.count('{') != content.count('}'):
            print(f"❌ JSON syntax error: Mismatched braces in {json_file}")
            return False

        # Try to parse JSON
        try:
            json_data = json.loads(content)
        except json.JSONDecodeError as je:
            print(f"❌ JSON parsing error: {je}")
            print(f"Error at line {je.lineno}, column {je.colno}")
            print(f"Content around error: {content[max(0, je.pos-50):je.pos+50]}")
            return False

        print("📄 Original JSON data:")
        print(json.dumps(json_data, indent=2))
        print("\n" + "="*50 + "\n")

        # Test the mapping
        mapped_data = map_pos_to_laravel(json_data)

        print("📋 Mapped Laravel data:")
        print(json.dumps(mapped_data, indent=2))

        return True

    except Exception as e:
        print(f"❌ Error testing JSON mapping: {e}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == "__main__":
    print("🧪 Testing JSON to Laravel mapping...")
    print("="*50)

    success = test_json_mapping()

    if success:
        print("\n✅ JSON mapping test completed successfully!")
    else:
        print("\n❌ JSON mapping test failed!")
