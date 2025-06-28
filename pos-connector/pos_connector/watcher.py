import time
import json
import os
from pathlib import Path
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
from .laravel_api import LaravelAPI
# You should provide a mapping function for your POS data:
from .pos_data_mapping import map_pos_to_laravel

INVOICE_FOLDER = r"C:\POS\Invoices"  # Default POS export folder

class InvoiceHandler(FileSystemEventHandler):
    def __init__(self, api):
        self.api = api
        self.processed_files = set()

    def on_created(self, event):
        if event.is_directory or not event.src_path.endswith('.json'):
            return
            
        # Avoid processing the same file multiple times
        if event.src_path in self.processed_files:
            return
            
        try:
            print(f"New invoice detected: {os.path.basename(event.src_path)}")
            
            # Wait a moment to ensure the file is completely written
            time.sleep(0.5)
            
            # Load the POS invoice data
            with open(event.src_path, 'r', encoding='utf-8') as f:
                pos_invoice = json.load(f)
                
            # Map POS data to Laravel format
            invoice_data = map_pos_to_laravel(pos_invoice)
            
            # Create invoice in Laravel system
            print("Creating invoice in Laravel system...")
            invoice = self.api.create_invoice(invoice_data)
            if not invoice or 'id' not in invoice:
                print(f"Error: Failed to create invoice from {event.src_path}")
                return
                
            # Submit invoice to JoFotara
            print(f"Submitting invoice {invoice['id']} to JoFotara...")
            result = self.api.submit_invoice(invoice['id'])
            if not result:
                print(f"Warning: Failed to submit invoice {invoice['id']} to JoFotara")
            
            # Download invoice PDF
            output_dir = os.path.join(os.path.dirname(event.src_path), "Processed")
            os.makedirs(output_dir, exist_ok=True)
            pdf_path = os.path.join(output_dir, f"invoice_{invoice['id']}.pdf")
            
            print(f"Downloading invoice PDF to {pdf_path}...")
            if self.api.download_invoice_pdf(invoice['id'], pdf_path):
                print(f"Successfully processed invoice: {invoice['id']}")
            else:
                print(f"Warning: Failed to download PDF for invoice {invoice['id']}")
                
            # Mark as processed
            self.processed_files.add(event.src_path)
            
        except json.JSONDecodeError:
            print(f"Error: Invalid JSON format in {event.src_path}")
        except Exception as e:
            print(f"Error processing invoice {event.src_path}: {str(e)}")
            import traceback
            traceback.print_exc()

def start_watcher(api, folder=INVOICE_FOLDER):
    # Ensure the folder exists
    if not os.path.exists(folder):
        try:
            os.makedirs(folder)
            print(f"Created invoice folder: {folder}")
        except Exception as e:
            print(f"Error creating invoice folder {folder}: {e}")
            print("Using current directory instead.")
            folder = os.path.dirname(os.path.abspath(__file__))
    
    # Create a 'Processed' subfolder
    processed_folder = os.path.join(folder, "Processed")
    if not os.path.exists(processed_folder):
        try:
            os.makedirs(processed_folder)
            print(f"Created processed invoices folder: {processed_folder}")
        except Exception as e:
            print(f"Error creating processed folder: {e}")
    
    # Start the file watcher
    event_handler = InvoiceHandler(api)
    observer = Observer()
    observer.schedule(event_handler, folder, recursive=False)
    observer.start()
    print(f"Watching {folder} for new invoices...")
    
    # Process any existing files in the folder
    for file in os.listdir(folder):
        if file.endswith('.json'):
            file_path = os.path.join(folder, file)
            print(f"Found existing invoice file: {file}")
            event_handler.on_created(type('obj', (object,), {'is_directory': False, 'src_path': file_path}))
    
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
        print("Stopping invoice watcher...")
    observer.join()
