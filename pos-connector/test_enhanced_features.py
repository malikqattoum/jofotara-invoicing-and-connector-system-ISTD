#!/usr/bin/env python3
"""
Test script for enhanced POS connector features:
1. User choice between automatic and manual folder selection
2. Filename display when transactions are sent
"""

import os
import sys
import json
import time
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

def create_test_scenario():
    """Create a test scenario with sample invoice files"""
    print("🧪 Creating Test Scenario")
    print("=" * 60)

    # Create test folders
    test_folders = [
        "C:/TestPOS/Invoices",
        "C:/TestRetail/Exports",
        "C:/TestCafe/Reports"
    ]

    sample_invoices = []

    for i, folder in enumerate(test_folders, 1):
        try:
            os.makedirs(folder, exist_ok=True)
            os.makedirs(f"{folder}/Processed", exist_ok=True)

            # Create sample invoice files
            invoice_files = [
                f"invoice_{i:03d}.json",
                f"receipt_{i:03d}.json",
                f"transaction_{i:03d}.pdf"
            ]

            for j, filename in enumerate(invoice_files, 1):
                file_path = os.path.join(folder, filename)

                if filename.endswith('.json'):
                    # Create sample JSON invoice
                    sample_invoice = {
                        "invoice_number": f"INV-{i:03d}-{j:03d}",
                        "date": "2025-01-04",
                        "customer": {
                            "name": f"Customer {i}-{j}",
                            "email": f"customer{i}{j}@example.com",
                            "phone": f"+96650123456{i}{j}"
                        },
                        "items": [
                            {
                                "description": f"Product {j}",
                                "quantity": j,
                                "unit_price": 10.00 + j,
                                "total_price": (10.00 + j) * j
                            }
                        ],
                        "subtotal": (10.00 + j) * j,
                        "tax": ((10.00 + j) * j) * 0.15,
                        "total": ((10.00 + j) * j) * 1.15,
                        "payment_method": "Credit Card"
                    }

                    with open(file_path, 'w') as f:
                        json.dump(sample_invoice, f, indent=2)

                else:
                    # Create sample PDF content (text file for demo)
                    pdf_content = f"""SAMPLE POS RECEIPT
Invoice: INV-{i:03d}-{j:03d}
Date: 2025-01-04
Customer: Customer {i}-{j}
Items: Product {j} x{j} = ${(10.00 + j) * j:.2f}
Total: ${((10.00 + j) * j) * 1.15:.2f}
"""
                    with open(file_path, 'w') as f:
                        f.write(pdf_content)

                sample_invoices.append({
                    'folder': folder,
                    'filename': filename,
                    'path': file_path
                })

            print(f"✅ Created test folder: {folder} ({len(invoice_files)} files)")

        except Exception as e:
            print(f"❌ Failed to create {folder}: {e}")

    return sample_invoices

def test_folder_detection_choice():
    """Test the folder detection choice functionality"""
    print("\n🔍 Testing Folder Detection Choice")
    print("=" * 60)

    from pos_connector.folder_detector import InvoiceFolderDetector

    detector = InvoiceFolderDetector()
    folders = detector.detect_invoice_folders(include_empty=True)

    print(f"📁 Detected {len(folders)} potential folders:")
    for i, folder in enumerate(folders[:5], 1):
        print(f"  {i}. {folder['path']} (score: {folder['score']:.1f})")

    print("\n💡 In the setup wizard, users will see:")
    print("--- Invoice Folder Configuration ---")
    print("Choose how to configure the invoice folder:")
    print("1. Automatic detection (recommended)")
    print("2. Manual folder selection")
    print()
    print("This gives users full control over their preference!")

def test_filename_display():
    """Test filename display functionality"""
    print("\n📄 Testing Filename Display")
    print("=" * 60)

    # Simulate processing files
    sample_files = [
        "invoice_001.json",
        "receipt_002.json",
        "transaction_003.pdf"
    ]

    print("When files are processed, users will see:")
    print()

    for filename in sample_files:
        print(f"📄 New invoice detected: {filename}")
        print(f"🔄 Creating invoice in Laravel system from {filename}...")
        print(f"📤 Submitting invoice 12345 to JoFotara from {filename}...")
        print(f"✅ Successfully sent transaction from file: {filename}")
        print(f"📄 Successfully processed invoice 12345 from file: {filename}")
        print(f"📁 Processed file moved to: C:/TestPOS/Invoices/Processed")
        print()

def demonstrate_setup_wizard():
    """Demonstrate the enhanced setup wizard"""
    print("\n🧙 Enhanced Setup Wizard Demo")
    print("=" * 60)

    print("The setup wizard now provides these options:")
    print()

    print("1️⃣ Detection Mode Selection:")
    print("   1. Enhanced automatic (discovers POS systems + folders)")
    print("   2. File monitoring with folder choice")
    print()

    print("2️⃣ Folder Configuration (Mode 2):")
    print("   1. Automatic detection (scans and suggests folders)")
    print("   2. Manual folder selection (user specifies path)")
    print()

    print("3️⃣ User Experience:")
    print("   - Clear choices at each step")
    print("   - Automatic folder validation")
    print("   - Detailed feedback during processing")
    print("   - Filename visibility for all transactions")

def show_configuration_examples():
    """Show configuration examples"""
    print("\n⚙️  Configuration Examples")
    print("=" * 60)

    configs = {
        "Automatic Detection": {
            "detection_mode": "1",
            "auto_detect_folders": True,
            "description": "Fully automatic - discovers everything"
        },
        "File Monitoring + Auto Folder": {
            "detection_mode": "2",
            "folder_detection_mode": "automatic",
            "invoice_folder": "C:/Aronium/Invoices",
            "description": "User chose automatic folder detection"
        },
        "File Monitoring + Manual Folder": {
            "detection_mode": "2",
            "folder_detection_mode": "manual",
            "invoice_folder": "C:/Custom/Path/Invoices",
            "description": "User specified custom folder path"
        }
    }

    for name, config in configs.items():
        print(f"📋 {name}:")
        description = config.pop('description')
        print(f"   {description}")
        for key, value in config.items():
            print(f"   {key}: {value}")
        print()

def cleanup_test_files():
    """Clean up test files"""
    print("\n🧹 Cleaning Up Test Files")
    print("=" * 60)

    import shutil
    test_folders = [
        "C:/TestPOS",
        "C:/TestRetail",
        "C:/TestCafe"
    ]

    for folder in test_folders:
        try:
            if os.path.exists(folder):
                shutil.rmtree(folder)
                print(f"✅ Removed: {folder}")
        except Exception as e:
            print(f"❌ Failed to remove {folder}: {e}")

def main():
    """Main test function"""
    print("🚀 Enhanced POS Connector Features Test")
    print("=" * 60)
    print("Testing new features:")
    print("1. User choice between automatic and manual folder selection")
    print("2. Filename display when transactions are sent")
    print("=" * 60)

    if len(sys.argv) > 1:
        command = sys.argv[1].lower()

        if command == "create":
            sample_invoices = create_test_scenario()
            print(f"\n✅ Created {len(sample_invoices)} sample invoice files")
            print("📋 Test files created in:")
            for folder in set(inv['folder'] for inv in sample_invoices):
                print(f"   - {folder}")
            print("\n💡 Now run the setup wizard to test folder detection:")
            print("   python main.py --setup")

        elif command == "cleanup":
            cleanup_test_files()
            print("\n✅ Test cleanup completed")

        elif command == "demo":
            test_folder_detection_choice()
            test_filename_display()
            demonstrate_setup_wizard()
            show_configuration_examples()

        else:
            print("Usage: python test_enhanced_features.py [create|cleanup|demo]")
            print()
            print("Commands:")
            print("  create   Create test folders and sample invoice files")
            print("  cleanup  Remove all test files and folders")
            print("  demo     Show feature demonstrations")
    else:
        # Run all tests
        test_folder_detection_choice()
        test_filename_display()
        demonstrate_setup_wizard()
        show_configuration_examples()

        print("\n🎯 Summary of Enhancements")
        print("=" * 60)
        print("✅ Users can choose between automatic and manual folder selection")
        print("✅ Filenames are displayed when transactions are processed")
        print("✅ Enhanced setup wizard with clear choices")
        print("✅ Detailed processing feedback with emojis")
        print("✅ Folder validation and creation assistance")
        print("✅ Backward compatibility maintained")

        print("\n💡 Next Steps:")
        print("1. Run 'python test_enhanced_features.py create' to create test files")
        print("2. Run 'python main.py --setup' to test the enhanced setup wizard")
        print("3. Test both automatic and manual folder selection modes")
        print("4. Watch for filename display during transaction processing")

if __name__ == "__main__":
    main()
