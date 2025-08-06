#!/usr/bin/env python3

import sys
import os
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.pdf_parser import PDFInvoiceParser

def test_pdf_parser():
    """Test PDF parser with the problematic files"""

    parser = PDFInvoiceParser()

    test_files = [
        "C:/TestPOS/Invoices/transaction_001.pdf",
        "C:/TestPOS/Invoices/25-200-000005.pdf"
    ]

    for file_path in test_files:
        if not Path(file_path).exists():
            print(f"❌ File not found: {file_path}")
            continue

        print(f"\n🧪 Testing PDF parser with: {file_path}")
        print("-" * 50)

        try:
            result = parser.parse_pdf_invoice(file_path)

            if result:
                print("✅ Successfully parsed PDF")
                print(f"Transaction ID: {result.get('transaction_id', 'N/A')}")
                print(f"Customer: {result.get('customer_name', 'N/A')}")
                print(f"Total: {result.get('total_amount', 'N/A')}")
                print(f"Items: {len(result.get('items', []))}")
            else:
                print("❌ Failed to parse PDF - returned None")

        except Exception as e:
            print(f"❌ Exception occurred: {e}")
            import traceback
            traceback.print_exc()

if __name__ == "__main__":
    print("🧪 Testing PDF Parser...")
    print("=" * 50)
    test_pdf_parser()
