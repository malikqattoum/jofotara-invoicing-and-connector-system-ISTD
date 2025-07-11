#!/usr/bin/env python3
"""
Test script for PDF processing functionality
"""

import os
import sys
import json
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.pdf_parser import PDFInvoiceParser
from pos_connector.pos_data_mapping import map_pos_to_laravel

def test_pdf_parser():
    """Test the PDF parser with a sample PDF"""
    parser = PDFInvoiceParser()

    # Test with a sample PDF (you'll need to provide one)
    test_pdf_path = "C:/Aronium/Invoices/test_invoice.pdf"

    if not os.path.exists(test_pdf_path):
        print(f"❌ Test PDF not found at: {test_pdf_path}")
        print("Please export a PDF invoice from Aronium to this location to test.")
        return False

    print(f"🔍 Testing PDF parser with: {test_pdf_path}")

    # Parse the PDF
    transaction_data = parser.parse_pdf_invoice(test_pdf_path, "Aronium POS")

    if transaction_data:
        print("✅ PDF parsing successful!")
        print("📄 Extracted data:")
        print(json.dumps(transaction_data, indent=2, default=str))

        # Test mapping to Laravel format
        try:
            invoice_data = map_pos_to_laravel(transaction_data)
            print("\n✅ Laravel mapping successful!")
            print("📋 Invoice data:")
            print(json.dumps(invoice_data, indent=2, default=str))
            return True
        except Exception as e:
            print(f"❌ Laravel mapping failed: {e}")
            return False
    else:
        print("❌ PDF parsing failed!")
        return False

def create_sample_text_invoice():
    """Create a sample text-based invoice for testing"""
    sample_invoice = """
    ARONIUM POS SYSTEM
    Invoice #12345
    Date: 2025-01-04

    Customer: John Doe
    Phone: +966501234567
    Email: john.doe@example.com

    Items:
    Coffee                  2    15.00    30.00
    Sandwich               1    25.00    25.00

    Subtotal:                           55.00
    Tax (15%):                           8.25
    Total:                              63.25

    Payment: Credit Card
    """

    # Save as text file for testing
    test_file = "C:/Aronium/Invoices/sample_invoice.txt"
    with open(test_file, 'w') as f:
        f.write(sample_invoice)

    print(f"📝 Created sample invoice text at: {test_file}")
    return test_file

if __name__ == "__main__":
    print("🧪 Testing PDF Processing System")
    print("=" * 50)

    # Create sample invoice for reference
    create_sample_text_invoice()

    # Test PDF parser
    success = test_pdf_parser()

    if success:
        print("\n🎉 All tests passed!")
        print("\n📋 Next steps:")
        print("1. Export a PDF invoice from Aronium to: C:/Aronium/Invoices/")
        print("2. Run the POS connector: python main.py")
        print("3. The connector will automatically process the PDF and create an invoice")
    else:
        print("\n⚠️  Tests incomplete - need a real PDF to test with")
        print("\n📋 To test:")
        print("1. Export a PDF invoice from Aronium to: C:/Aronium/Invoices/test_invoice.pdf")
        print("2. Run this test again: python test_pdf_processing.py")
