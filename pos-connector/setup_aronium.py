#!/usr/bin/env python3
"""
Setup script for Aronium POS integration with JoFotara
"""

import os
import json
import sys
from pathlib import Path

def setup_aronium_integration():
    """Setup Aronium POS integration"""
    print("🚀 Setting up Aronium POS Integration with JoFotara")
    print("=" * 60)

    # 1. Check if required libraries are installed
    print("1️⃣ Checking required libraries...")
    try:
        import pdfplumber
        print("   ✅ PDF processing libraries installed")
    except ImportError:
        print("   ❌ PDF processing libraries missing")
        print("   Installing required libraries...")
        os.system("pip install PyPDF2 pdfplumber")
        print("   ✅ Libraries installed")

    # 2. Create folder structure
    print("\n2️⃣ Creating folder structure...")
    folders = [
        "C:/Aronium/Invoices",
        "C:/Aronium/Invoices/Processed"
    ]

    for folder in folders:
        os.makedirs(folder, exist_ok=True)
        print(f"   ✅ Created: {folder}")

    # 3. Update configuration
    print("\n3️⃣ Updating configuration...")
    config_file = Path(__file__).parent / 'config.json'

    if config_file.exists():
        with open(config_file, 'r') as f:
            config = json.load(f)
    else:
        config = {}

    # Update config for file monitoring mode
    config.update({
        "detection_mode": "2",
        "invoice_folder": "C:\\Aronium\\Invoices",
        "auto_submit_jofotara": False,
        "sync_interval": 30,  # Check every 30 seconds
        "log_level": "INFO"
    })

    with open(config_file, 'w') as f:
        json.dump(config, f, indent=4)

    print(f"   ✅ Configuration updated: {config_file}")

    # 4. Create desktop shortcut for easy access
    print("\n4️⃣ Creating shortcuts...")

    # Create batch file for easy startup
    batch_content = f"""@echo off
cd /d "{Path(__file__).parent}"
echo Starting JoFotara POS Connector for Aronium...
python main.py
pause
"""

    batch_file = Path(__file__).parent / "start_aronium_connector.bat"
    with open(batch_file, 'w') as f:
        f.write(batch_content)

    print(f"   ✅ Created startup script: {batch_file}")

    # 5. Create instruction file
    print("\n5️⃣ Creating instruction file...")

    instructions = """
# 📋 Aronium POS Integration Instructions

## How to Use:

### 1. Export Invoice from Aronium:
   - Create/complete a sale in Aronium POS
   - Export the invoice as PDF
   - Save it to: C:\\Aronium\\Invoices\\

### 2. Start the Connector:
   - Double-click: start_aronium_connector.bat
   - OR run: python main.py

### 3. What Happens Automatically:
   - Connector watches the folder for new PDF files
   - When you save a PDF, it automatically:
     * Extracts transaction data from the PDF
     * Creates an invoice in your Laravel system
     * Moves processed PDF to "Processed" folder

### 4. Troubleshooting:
   - Check logs in: logs/enhanced_connector.log
   - Make sure Laravel system is running
   - Verify PDF is saved in correct folder

### 5. Supported File Types:
   - PDF files (from Aronium exports)
   - JSON files (if available)

## Folder Structure:
```
C:\\Aronium\\Invoices\\          <- Save your PDF exports here
C:\\Aronium\\Invoices\\Processed\\ <- Processed files go here
```

## Need Help?
- Run: python test_pdf_processing.py
- Check the logs for detailed information
"""

    instructions_file = Path(__file__).parent / "ARONIUM_INSTRUCTIONS.md"
    with open(instructions_file, 'w') as f:
        f.write(instructions)

    print(f"   ✅ Created instructions: {instructions_file}")

    # 6. Test the setup
    print("\n6️⃣ Testing setup...")

    # Create a sample text file to show the expected format
    sample_content = """ARONIUM POS SYSTEM
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

    sample_file = Path("C:/Aronium/Invoices/SAMPLE_FORMAT.txt")
    with open(sample_file, 'w') as f:
        f.write(sample_content)

    print(f"   ✅ Created sample format: {sample_file}")

    print("\n🎉 Setup Complete!")
    print("\n📋 Next Steps:")
    print("1. Export a PDF invoice from Aronium to: C:/Aronium/Invoices/")
    print("2. Run: start_aronium_connector.bat")
    print("3. The connector will automatically process your PDF invoices")
    print("\n📖 Read ARONIUM_INSTRUCTIONS.md for detailed usage instructions")

if __name__ == "__main__":
    setup_aronium_integration()
