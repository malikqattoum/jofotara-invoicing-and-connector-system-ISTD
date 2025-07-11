# Aronium POS Integration Instructions

## How to Use:

### 1. Export Invoice from Aronium:
   - Create/complete a sale in Aronium POS
   - Export the invoice as PDF
   - Save it to: C:\Aronium\Invoices\

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
C:\Aronium\Invoices\          <- Save your PDF exports here
C:\Aronium\Invoices\Processed\ <- Processed files go here
```

## Need Help?
- Run: python test_pdf_processing.py
- Check the logs for detailed information
