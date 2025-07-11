#!/usr/bin/env python3
"""
Quick test to verify the POS connector setup
"""

import os
import sys
import json
import time
import threading
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

def test_configuration():
    """Test if configuration is correct"""
    print("🔧 Testing Configuration...")

    config_file = Path(__file__).parent / 'config.json'
    if not config_file.exists():
        print("❌ config.json not found")
        return False

    with open(config_file, 'r') as f:
        config = json.load(f)

    required_keys = ['base_url', 'email', 'detection_mode', 'invoice_folder']
    for key in required_keys:
        if key not in config:
            print(f"❌ Missing config key: {key}")
            return False

    if config['detection_mode'] != "2":
        print("❌ Detection mode should be '2' for file monitoring")
        return False

    print("✅ Configuration is correct")
    return True

def test_folders():
    """Test if required folders exist"""
    print("📁 Testing Folders...")

    folders = [
        "C:/Aronium/Invoices",
        "C:/Aronium/Invoices/Processed"
    ]

    for folder in folders:
        if not os.path.exists(folder):
            print(f"❌ Folder missing: {folder}")
            return False
        print(f"✅ Folder exists: {folder}")

    return True

def test_imports():
    """Test if all required modules can be imported"""
    print("📦 Testing Imports...")

    try:
        from pos_connector.pdf_parser import PDFInvoiceParser
        print("✅ PDF parser imported")

        from pos_connector.watcher import InvoiceHandler
        print("✅ File watcher imported")

        from pos_connector.laravel_api import LaravelAPI
        print("✅ Laravel API imported")

        import pdfplumber
        print("✅ PDF processing library imported")

        return True
    except ImportError as e:
        print(f"❌ Import error: {e}")
        return False

def test_api_connection():
    """Test connection to Laravel API"""
    print("🌐 Testing API Connection...")

    try:
        from pos_connector.laravel_api import LaravelAPI

        config_file = Path(__file__).parent / 'config.json'
        with open(config_file, 'r') as f:
            config = json.load(f)

        api = LaravelAPI(
            base_url=config['base_url'],
            email=config['email'],
            password=config.get('password', '')
        )

        # Test authentication
        if api.authenticate():
            print("✅ API connection successful")
            return True
        else:
            print("❌ API authentication failed")
            return False

    except Exception as e:
        print(f"❌ API connection error: {e}")
        return False

def run_file_watcher_test():
    """Test the file watcher in a separate thread"""
    print("👀 Testing File Watcher...")

    try:
        from pos_connector.watcher import start_watcher
        from pos_connector.laravel_api import LaravelAPI

        config_file = Path(__file__).parent / 'config.json'
        with open(config_file, 'r') as f:
            config = json.load(f)

        api = LaravelAPI(
            base_url=config['base_url'],
            email=config['email'],
            password=config.get('password', '')
        )

        if not api.authenticate():
            print("❌ Cannot test file watcher - API authentication failed")
            return False

        # Start watcher in a separate thread
        watcher_thread = threading.Thread(
            target=start_watcher,
            args=(api, config['invoice_folder']),
            daemon=True
        )
        watcher_thread.start()

        print("✅ File watcher started successfully")
        print("📂 Watching folder:", config['invoice_folder'])

        # Let it run for a few seconds
        time.sleep(3)

        print("✅ File watcher test completed")
        return True

    except Exception as e:
        print(f"❌ File watcher test failed: {e}")
        return False

def main():
    """Run all tests"""
    print("🧪 Quick Test for Aronium POS Connector")
    print("=" * 50)

    tests = [
        ("Configuration", test_configuration),
        ("Folders", test_folders),
        ("Imports", test_imports),
        ("API Connection", test_api_connection),
        ("File Watcher", run_file_watcher_test),
    ]

    passed = 0
    total = len(tests)

    for test_name, test_func in tests:
        print(f"\n🔍 Running {test_name} test...")
        if test_func():
            passed += 1
        else:
            print(f"❌ {test_name} test failed")

    print(f"\n📊 Test Results: {passed}/{total} tests passed")

    if passed == total:
        print("\n🎉 All tests passed! Your system is ready.")
        print("\n📋 Next steps:")
        print("1. Export a PDF invoice from Aronium")
        print("2. Save it to: C:/Aronium/Invoices/")
        print("3. Run: python main.py")
        print("4. The connector will automatically process your PDF")
    else:
        print(f"\n⚠️  {total - passed} test(s) failed. Please fix the issues above.")

if __name__ == "__main__":
    main()
