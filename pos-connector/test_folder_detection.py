#!/usr/bin/env python3
"""
Test script for dynamic invoice folder detection
"""

import os
import sys
import json
import logging
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.folder_detector import InvoiceFolderDetector

def test_folder_detection():
    """Test the folder detection functionality"""
    print("🧪 Testing Dynamic Invoice Folder Detection")
    print("=" * 60)

    # Setup logging
    logging.basicConfig(level=logging.INFO)

    # Create detector
    detector = InvoiceFolderDetector()

    print("🔍 Scanning for invoice folders...")
    print("This may take a few moments...\n")

    # Detect folders
    folders = detector.detect_invoice_folders(include_empty=True)

    if not folders:
        print("❌ No invoice folders detected")
        return

    print(f"✅ Found {len(folders)} potential invoice folders:")
    print("=" * 60)

    for i, folder in enumerate(folders, 1):
        print(f"{i:2d}. {folder['path']}")
        print(f"    📁 POS System: {folder['pos_system']}")
        print(f"    📊 Files: {folder['file_count']} total, {folder['recent_files']} recent")
        print(f"    📈 Score: {folder['score']:.1f}")
        print(f"    🔍 Discovery: {folder['discovery_method']}")

        if folder['file_types']:
            file_types = [f"{k}: {v}" for k, v in folder['file_types'].items() if v > 0]
            print(f"    📄 File types: {', '.join(file_types)}")

        if folder['newest_file']:
            print(f"    🕒 Latest file: {folder['newest_file']}")

        print()

    # Test best folder selection
    print("\n🎯 Best Folder Recommendation:")
    print("=" * 60)

    best_folder = detector.get_best_folder()
    if best_folder:
        print(f"✅ Recommended folder: {best_folder}")
    else:
        print("❌ No suitable folder found")

    # Test interactive selection (simulation)
    print("\n🎮 Interactive Selection Test:")
    print("=" * 60)
    print("(This would normally prompt for user input)")

    # Show what the interactive selection would display
    if folders:
        print(f"Would show top {min(len(folders), 10)} folders for selection")
        for i, folder in enumerate(folders[:10], 1):
            print(f"  {i}. {folder['path']} (score: {folder['score']:.1f})")

def create_test_folders():
    """Create some test folders to demonstrate detection"""
    print("\n🏗️  Creating Test Folders:")
    print("=" * 60)

    test_folders = [
        "C:/TestPOS/Invoices",
        "C:/TestRetail/Reports",
        "C:/TestCafe/Exports"
    ]

    for folder in test_folders:
        try:
            os.makedirs(folder, exist_ok=True)

            # Create some test files
            test_files = [
                f"{folder}/invoice_001.pdf",
                f"{folder}/receipt_002.json",
                f"{folder}/transaction_003.csv"
            ]

            for file_path in test_files:
                with open(file_path, 'w') as f:
                    f.write(f"Test file created at {file_path}")

            print(f"✅ Created: {folder} (with {len(test_files)} test files)")

        except Exception as e:
            print(f"❌ Failed to create {folder}: {e}")

def cleanup_test_folders():
    """Clean up test folders"""
    print("\n🧹 Cleaning up test folders...")

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

if __name__ == "__main__":
    print("🚀 JoFotara Dynamic Folder Detection Test")
    print("=" * 60)

    if len(sys.argv) > 1:
        command = sys.argv[1].lower()

        if command == "create-test":
            create_test_folders()
            print("\n✅ Test folders created. Run 'python test_folder_detection.py' to test detection.")

        elif command == "cleanup":
            cleanup_test_folders()
            print("\n✅ Test folders cleaned up.")

        else:
            print("Usage: python test_folder_detection.py [create-test|cleanup]")
            print()
            print("Commands:")
            print("  create-test  Create test folders for demonstration")
            print("  cleanup      Remove test folders")
            print()
            print("Run without arguments to test folder detection")
    else:
        test_folder_detection()

        print("\n💡 Tips:")
        print("- Run 'python test_folder_detection.py create-test' to create test folders")
        print("- Run 'python test_folder_detection.py cleanup' to remove test folders")
        print("- The detector looks for common POS folder patterns and recent invoice files")
        print("- Higher scores indicate better folder candidates")
