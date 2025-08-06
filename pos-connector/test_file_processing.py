#!/usr/bin/env python3

import json
import sys
import os
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.pos_data_mapping import map_pos_to_laravel
from pos_connector.pdf_parser import PDFInvoiceParser
from pos_connector.laravel_api import LaravelAPI

def test_file_processing():
    """Test processing of files in the invoice directory"""

    invoice_dir = Path("C:/TestPOS/Invoices")
    if not invoice_dir.exists():
        print(f"❌ Invoice directory not found: {invoice_dir}")
        return False

    # Initialize components
    pdf_parser = PDFInvoiceParser()

    # Get all invoice files
    json_files = list(invoice_dir.glob("*.json"))
    pdf_files = list(invoice_dir.glob("*.pdf"))

    print(f"📁 Found {len(json_files)} JSON files and {len(pdf_files)} PDF files")

    success_count = 0
    total_count = 0

    # Process JSON files
    for json_file in json_files:
        total_count += 1
        print(f"\n📄 Processing JSON: {json_file.name}")
        print("-" * 40)

        try:
            with open(json_file, 'r', encoding='utf-8') as f:
                pos_invoice = json.load(f)

            print("✅ JSON loaded successfully")

            # Map to Laravel format
            invoice_data = map_pos_to_laravel(pos_invoice)
            print("✅ Mapped to Laravel format")
            print(f"   Customer: {invoice_data.get('customer_name', 'N/A')}")
            print(f"   Items: {len(invoice_data.get('items', []))}")
            print(f"   Invoice #: {invoice_data.get('invoice_number', 'N/A')}")

            success_count += 1

        except json.JSONDecodeError as e:
            print(f"❌ JSON decode error: {e}")
        except Exception as e:
            print(f"❌ Processing error: {e}")

    # Process PDF files
    for pdf_file in pdf_files:
        total_count += 1
        print(f"\n📄 Processing PDF: {pdf_file.name}")
        print("-" * 40)

        try:
            pos_invoice = pdf_parser.parse_pdf_invoice(str(pdf_file))

            if pos_invoice:
                print("✅ PDF parsed successfully")

                # Map to Laravel format
                invoice_data = map_pos_to_laravel(pos_invoice)
                print("✅ Mapped to Laravel format")
                print(f"   Customer: {invoice_data.get('customer_name', 'N/A')}")
                print(f"   Items: {len(invoice_data.get('items', []))}")
                print(f"   Invoice #: {invoice_data.get('invoice_number', 'N/A')}")

                success_count += 1
            else:
                print("❌ PDF parsing returned None")

        except Exception as e:
            print(f"❌ Processing error: {e}")

    print(f"\n" + "="*50)
    print(f"📊 Processing Results: {success_count}/{total_count} successful")
    print("="*50)

    return success_count == total_count

def test_api_connection():
    """Test connection to Laravel API"""
    print("\n🔗 Testing Laravel API connection...")

    try:
        # Load config
        config_path = Path("config.json")
        if not config_path.exists():
            print("❌ Config file not found")
            return False

        with open(config_path, 'r') as f:
            config = json.load(f)

        # Initialize API with config
        api = LaravelAPI(
            base_url=config.get('base_url', 'http://127.0.0.1:8000'),
            email=config.get('email', ''),
            password='',  # Password not stored in config for security
            vendor_id=config.get('vendor_id', 2)
        )

        print("✅ Laravel API initialized successfully")
        print(f"   Base URL: {config.get('base_url')}")
        print(f"   Email: {config.get('email')}")
        print(f"   Vendor ID: {config.get('vendor_id')}")

        return True
    except Exception as e:
        print(f"❌ Laravel API connection failed: {e}")
        return False

if __name__ == "__main__":
    print("🧪 Testing File Processing...")
    print("="*50)

    # Test file processing
    processing_success = test_file_processing()

    # Test API connection
    api_success = test_api_connection()

    if processing_success and api_success:
        print("\n🎉 All tests passed! The POS connector should work correctly.")
    else:
        print("\n⚠️  Some tests failed. Check the output above for details.")
