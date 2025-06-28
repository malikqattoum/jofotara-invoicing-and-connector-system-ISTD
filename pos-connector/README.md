# JoFotara Universal POS Connector

🚀 **Enterprise-grade POS connector that automatically detects and integrates with ANY Windows POS system**

This advanced Python application provides seamless integration between Windows POS systems and the JoFotara Laravel invoicing system, featuring automatic POS detection, real-time synchronization, and support for virtually any POS system format.

## Features

- Easy setup with interactive configuration wizard
- Automatic authentication with the Laravel backend
- File watcher for POS integration (monitors a folder for new invoice JSON files)
- Support for multiple POS systems through flexible data mapping
- Error handling and comprehensive logging
- PDF and XML invoice download
- Vendor-specific authentication and invoice management

## Requirements

- Python 3.7 or higher
- Windows operating system
- Internet connection to access the Laravel backend
- POS system capable of exporting invoices as JSON files

## Installation

1. Install Python 3.7 or newer if not already installed
2. Set up a virtual environment (recommended):
   ```bash
   python -m venv venv
   venv\Scripts\activate  # On Windows
   ```
3. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```

## Configuration

Run the setup wizard to configure the connector:

```bash
python main.py --setup
```

The wizard will guide you through the following configuration steps:

1. Laravel API URL - The URL of your Laravel backend (e.g., `http://your-domain.com`)
2. Vendor Email - The email address of the vendor account in the Laravel system
3. Vendor Password - The password for the vendor account
4. POS Invoice Export Folder - The folder where your POS system exports invoice files (default: `C:\POS\Invoices`)

## Usage

### Starting the Connector

To start the connector, run:

```bash
python main.py
```

The connector will:
1. Authenticate with the Laravel backend using the vendor credentials
2. Start monitoring the configured invoice folder
3. Process any new invoice files that appear in the folder
4. Submit the invoices to JoFotara through the Laravel backend
5. Download and save the processed invoice PDFs

### POS System Integration

Configure your POS system to export invoices as JSON files to the folder you specified during setup. The JSON files should follow this structure:

```json
{
  "customer": {
    "name": "Customer Name",
    "tax_number": "123456789",
    "address": "Customer Address"
  },
  "invoice_date": "2023-06-16",
  "currency": "JOD",
  "notes": "Invoice notes",
  "items": [
    {
      "description": "Product 1",
      "quantity": 2,
      "unit_price": 10.5,
      "tax": 16
    },
    {
      "description": "Product 2",
      "quantity": 1,
      "unit_price": 25.0,
      "tax": 16
    }
  ]
}
```

The connector supports various JSON formats from different POS systems. If your POS system exports in a different format, you may need to adjust the mapping in `pos_connector/pos_data_mapping.py`.

### Sample Template

To generate a sample JSON template that works with the connector, you can use the included utility function:

```python
from pos_connector.pos_data_mapping import generate_sample_template
generate_sample_template("sample_invoice.json")
```

## Architecture

The connector consists of several components:

1. **Main Application** (`main.py`) - Handles configuration, authentication, and starts the file watcher
2. **Laravel API Client** (`laravel_api.py`) - Communicates with the Laravel backend
3. **POS Data Mapper** (`pos_data_mapping.py`) - Converts POS data to the format expected by the Laravel API
4. **File Watcher** (`watcher.py`) - Monitors the invoice folder for new files
5. **UBL Generator** (`ubl.py`) - Handles UBL XML generation if needed
6. **Printer** (`printer.py`) - Handles invoice printing if needed

## Troubleshooting

### Logs

The connector creates log files in the `logs` directory:

- `laravel_api.log` - API communication logs
- `pos_mapping.log` - POS data mapping logs

Check these logs for detailed information if you encounter any issues.

### Common Issues

1. **Authentication Failed**: Verify your vendor email and password in the Laravel system
2. **Connection Error**: Ensure the Laravel backend URL is correct and accessible
3. **Invoice Processing Error**: Check the JSON format of your invoice files
4. **Folder Access Error**: Ensure the application has permission to access the invoice folder

## Support

For support, please contact your JoFotara system administrator or the developer of your Laravel backend application.
