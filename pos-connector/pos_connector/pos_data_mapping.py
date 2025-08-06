# pos_data_mapping.py
# This module handles mapping from various POS system data formats to the Laravel API format

import datetime
import json
import os
import logging

# Set up logging
log_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'logs')
os.makedirs(log_dir, exist_ok=True)
logging.basicConfig(
    filename=os.path.join(log_dir, 'pos_mapping.log'),
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

# Common field mappings for different POS systems
POS_MAPPINGS = {
    # Default mapping (generic)
    'default': {
        'customer_name': ['customer.name', 'customerName', 'client_name', 'buyer.name'],
        'customer_tax_number': ['customer.tax_number', 'customerTaxNumber', 'tax_id', 'buyer.tax_id'],
        'customer_address': ['customer.address', 'customerAddress', 'address', 'buyer.address'],
        'customer_email': ['customer.email', 'customerEmail', 'email', 'buyer.email'],
        'customer_phone': ['customer.phone', 'customerPhone', 'phone', 'buyer.phone'],
        'invoice_date': ['invoice_date', 'date', 'invoiceDate', 'created_at'],
        'currency': ['currency', 'currencyCode'],
        'notes': ['notes', 'comment', 'remarks', 'description'],
        'items': ['items', 'lines', 'products', 'invoiceItems']
    },
    # Add mappings for specific POS systems as needed
    'quickbooks': {
        'customer_name': ['CustomerRef.name'],
        'customer_tax_number': ['CustomerMemo.value'],  # May need custom extraction
        'customer_address': ['BillAddr.Line1', 'ShipAddr.Line1', 'CustomerRef.address'],  # QuickBooks address fields
        'customer_email': ['BillEmail.Address', 'CustomerRef.email'],
        'customer_phone': ['CustomerRef.phone', 'BillAddr.phone'],
        'invoice_date': ['TxnDate'],
        'currency': ['CurrencyRef.value'],
        'invoice_number': ['DocNumber', 'Id'],
        'items': ['Line']
    },
    # Minimal format mapping
    'minimal': {
        'customer_name': ['customerName', 'customer_name'],
        'customer_email': ['customerEmail', 'customer_email'],
        'customer_phone': ['customerPhone', 'customer_phone'],
        'customer_address': ['customerAddress', 'customer_address'],
        'invoice_date': ['date', 'invoice_date'],
        'currency': ['currency'],
        'items': ['products', 'items']
    },
    # Add more POS systems as needed
}

def get_nested_value(data, path_options):
    """Try to get a value from nested dictionary using multiple possible paths"""
    for path in path_options:
        try:
            value = data
            for key in path.split('.'):
                value = value[key]
            return value
        except (KeyError, TypeError):
            continue
    return None

def detect_pos_system(pos_invoice):
    """Attempt to detect which POS system the data is from"""
    # Simple detection based on known fields
    if 'Line' in pos_invoice and 'TxnDate' in pos_invoice:
        return 'quickbooks'
    elif 'customerName' in pos_invoice and 'products' in pos_invoice:
        return 'minimal'
    # Add more detection logic as needed
    return 'default'

def map_pos_to_laravel(pos_invoice):
    """Map POS invoice data to Laravel API format"""
    try:
        # Detect POS system type
        pos_type = detect_pos_system(pos_invoice)
        mapping = POS_MAPPINGS.get(pos_type, POS_MAPPINGS['default'])

        # Log the detected POS system
        logging.info(f"Detected POS system type: {pos_type}")

        # Extract customer information
        customer_name = get_nested_value(pos_invoice, mapping.get('customer_name', [])) or "Unknown Customer"
        customer_tax_number = get_nested_value(pos_invoice, mapping.get('customer_tax_number', [])) or ""
        customer_address = get_nested_value(pos_invoice, mapping.get('customer_address', [])) or ""

        # Extract invoice date
        invoice_date = get_nested_value(pos_invoice, mapping.get('invoice_date', []))
        if not invoice_date:
            invoice_date = datetime.datetime.now().strftime("%Y-%m-%d")

        # Extract other invoice details
        currency = get_nested_value(pos_invoice, mapping.get('currency', [])) or "JOD"
        notes = get_nested_value(pos_invoice, mapping.get('notes', [])) or ""

        # Extract items
        raw_items = get_nested_value(pos_invoice, mapping.get('items', [])) or []

        # Map items based on POS system type
        items = []
        if pos_type == 'quickbooks':
            for item in raw_items:
                if item.get('DetailType') == 'SalesItemLineDetail':
                    qty = float(item.get('SalesItemLineDetail', {}).get('Qty', 1))
                    amount = float(item.get('Amount', 0))
                    items.append({
                        "description": item.get('Description', 'Item'),
                        "quantity": qty,
                        "unit_price": amount / qty if qty > 0 else amount,
                        "tax": 0  # QuickBooks handles tax differently, may need custom logic
                    })
        elif pos_type == 'minimal':
            for item in raw_items:
                items.append({
                    "description": item.get('name', item.get('description', 'Item')),
                    "quantity": float(item.get('quantity', 1)),
                    "unit_price": float(item.get('price', item.get('unit_price', 0))),
                    "tax": float(item.get('tax', item.get('tax_rate', 0)))
                })
        else:  # default mapping
            for item in raw_items:
                items.append({
                    "description": item.get('description', item.get('name', 'Item')),
                    "quantity": float(item.get('quantity', 1)),
                    "unit_price": float(item.get('unit_price', item.get('price', 0))),
                    "tax": float(item.get('tax', item.get('tax_rate', 0)))
                })

        # Generate invoice number if not present
        invoice_number = get_nested_value(pos_invoice, mapping.get('invoice_number', ['invoice_number', 'number', 'id']))
        if not invoice_number:
            # Generate unique invoice number
            import uuid
            invoice_number = f"INV-{datetime.datetime.now().strftime('%Y%m%d')}-{str(uuid.uuid4())[:8].upper()}"

        # Extract customer contact info
        customer_email = get_nested_value(pos_invoice, mapping.get('customer_email', ['customer_email', 'email'])) or ""
        customer_phone = get_nested_value(pos_invoice, mapping.get('customer_phone', ['customer_phone', 'phone'])) or ""

        # Fix items format for Laravel API
        laravel_items = []
        for item in items:
            laravel_items.append({
                "description": item.get('description', 'Item'),
                "quantity": int(item.get('quantity', 1)),
                "price": float(item.get('unit_price', 0))
            })

        # Create the mapped invoice in Laravel format
        mapped_invoice = {
            "invoice_number": invoice_number,
            "customer_name": customer_name,
            "customer_email": customer_email,
            "customer_phone": customer_phone,
            "items": laravel_items
        }

        # Log successful mapping
        logging.info(f"Successfully mapped invoice for {customer_name} with {len(items)} items")

        return mapped_invoice

    except Exception as e:
        # Log error and return a minimal valid structure
        logging.error(f"Error mapping POS data: {str(e)}")
        logging.error(f"POS data: {json.dumps(pos_invoice)[:500]}...")

        # Return minimal valid structure for Laravel API
        import uuid
        return {
            "invoice_number": f"ERR-{datetime.datetime.now().strftime('%Y%m%d')}-{str(uuid.uuid4())[:8].upper()}",
            "customer_name": "Error in POS data",
            "customer_email": "",
            "customer_phone": "",
            "items": [{
                "description": f"Error processing invoice data: {str(e)}",
                "quantity": 1,
                "price": 0
            }]
        }

# Function to save a sample mapping template
def generate_sample_template(output_path="sample_pos_format.json"):
    """Generate a sample POS data format that works with this mapper"""
    sample = {
        "customer": {
            "name": "Sample Customer",
            "tax_number": "123456789",
            "address": "123 Sample Street, Amman, Jordan"
        },
        "invoice_date": datetime.datetime.now().strftime("%Y-%m-%d"),
        "currency": "JOD",
        "notes": "Sample invoice generated by JoFotara POS Connector",
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

    with open(output_path, 'w') as f:
        json.dump(sample, f, indent=4)

    return output_path
