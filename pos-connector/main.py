#!/usr/bin/env python3
"""
JoFotara Universal POS Connector - Enhanced Version
Connects any Windows POS system to JoFotara Laravel invoicing system
"""

import sys
import os
import json
import time
import asyncio
import logging
import signal
import threading
from pathlib import Path
from typing import Dict, Any, Optional, List

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.bridge import POSConnector
from pos_connector.ubl import UBLInvoiceGenerator
from pos_connector.printer import Printer
from pos_connector.laravel_api import LaravelAPI
from pos_connector.watcher import start_watcher, INVOICE_FOLDER
from pos_connector.enhanced_connector import EnhancedPOSConnector
from pos_connector.database_scanner import DatabaseScanner
from pos_connector.network_scanner import NetworkScanner
from pos_connector.registry_scanner import RegistryScanner
from pos_connector.service_manager import WindowsServiceManager
from pos_connector.folder_detector import InvoiceFolderDetector

def setup_wizard():
    """Enhanced setup wizard for the POS connector"""
    config_file = Path(__file__).parent / 'config.json'
    config = {}

    if config_file.exists():
        try:
            with open(config_file, 'r') as f:
                config = json.load(f)
        except Exception as e:
            print(f"Error loading existing config: {e}")

    print("\n" + "="*60)
    print("     JoFotara Universal POS Connector Setup Wizard")
    print("="*60)
    print("This wizard will configure the connection to your JoFotara system")
    print("and help you set up automatic POS integration.\n")

    # Laravel API URL
    default_url = config.get('base_url', 'http://localhost:8000')
    base_url = input(f"JoFotara API URL [{default_url}]: ").strip() or default_url
    config['base_url'] = base_url

    # Vendor credentials
    default_email = config.get('email', '')
    email = input(f"Vendor Email [{default_email}]: ").strip() or default_email
    config['email'] = email

    password = input("Vendor Password (leave blank to keep existing): ").strip()
    if password:
        config['password'] = password

    # Enhanced configuration options
    print("\n--- Advanced Configuration ---")

    # Sync interval
    default_interval = config.get('sync_interval', 60)
    try:
        sync_interval = int(input(f"Sync interval in seconds [{default_interval}]: ").strip() or default_interval)
        config['sync_interval'] = sync_interval
    except ValueError:
        config['sync_interval'] = default_interval

    # Auto-submit to JoFotara
    default_auto_submit = config.get('auto_submit_jofotara', False)
    auto_submit = input(f"Auto-submit invoices to JoFotara? (y/n) [{'y' if default_auto_submit else 'n'}]: ").strip().lower()
    config['auto_submit_jofotara'] = auto_submit in ['y', 'yes', 'true']

    # POS detection mode
    print("\n--- POS Detection Mode ---")
    print("1. Automatic detection (recommended)")
    print("2. File monitoring only")
    print("3. Manual configuration")

    detection_mode = input("Choose detection mode [1]: ").strip() or "1"
    config['detection_mode'] = detection_mode

    if detection_mode == "2":
        # File monitoring configuration with user choice
        print("\n--- Invoice Folder Configuration ---")
        print("Choose how to configure the invoice folder:")
        print("1. Automatic detection (recommended)")
        print("2. Manual folder selection")

        folder_choice = input("Choose option (1/2) [1]: ").strip() or "1"

        if folder_choice == "1":
            # Automatic detection
            print("\n🔍 Scanning for POS invoice folders...")
            print("This may take a few moments...")

            folder_detector = InvoiceFolderDetector()
            auto_detected = folder_detector.suggest_folders_interactive()

            if auto_detected:
                config['invoice_folder'] = auto_detected
                config['folder_detection_mode'] = 'automatic'
                print(f"✅ Using automatically detected folder: {auto_detected}")
            else:
                print("❌ No suitable folders detected automatically.")
                print("Falling back to manual selection...")
                default_folder = config.get('invoice_folder', INVOICE_FOLDER)
                invoice_folder = input(f"POS Invoice Export Folder [{default_folder}]: ").strip() or default_folder
                config['invoice_folder'] = invoice_folder
                config['folder_detection_mode'] = 'manual'
        else:
            # Manual selection
            print("\n📁 Manual folder selection:")
            default_folder = config.get('invoice_folder', INVOICE_FOLDER)
            invoice_folder = input(f"POS Invoice Export Folder [{default_folder}]: ").strip() or default_folder
            config['invoice_folder'] = invoice_folder
            config['folder_detection_mode'] = 'manual'

            # Validate the folder exists
            if not os.path.exists(invoice_folder):
                create_folder = input(f"Folder doesn't exist. Create it? (y/n) [y]: ").strip().lower()
                if create_folder in ['', 'y', 'yes']:
                    try:
                        os.makedirs(invoice_folder, exist_ok=True)
                        print(f"✅ Created folder: {invoice_folder}")
                    except Exception as e:
                        print(f"❌ Failed to create folder: {e}")
                        print("Please create the folder manually or choose a different path.")
                else:
                    print("⚠️  Warning: Folder doesn't exist. Please create it before starting the connector.")

    # Logging level
    log_level = input("Log level (DEBUG/INFO/WARNING/ERROR) [INFO]: ").strip().upper() or "INFO"
    config['log_level'] = log_level

    # Save configuration
    try:
        with open(config_file, 'w') as f:
            json.dump(config, f, indent=4)
        print(f"\n✓ Configuration saved to {config_file}")
        print("✓ Setup completed successfully!")
        return config
    except Exception as e:
        print(f"✗ Error saving config: {e}")
        return None

async def main():
    """Enhanced main function with automatic POS detection"""
    config_file = Path(__file__).parent / 'config.json'
    config = {}

    if config_file.exists():
        try:
            with open(config_file, 'r') as f:
                config = json.load(f)
        except Exception as e:
            print(f"Error loading config: {e}")
            config = setup_wizard()
    else:
        print("No configuration found. Starting setup wizard...")
        config = setup_wizard()

    if not config:
        print("Failed to load or create configuration. Exiting.")
        sys.exit(1)

    print("\n" + "="*60)
    print("         JoFotara Universal POS Connector")
    print("="*60)
    print(f"API URL: {config['base_url']}")
    print(f"Detection Mode: {config.get('detection_mode', '1')}")
    print(f"Sync Interval: {config.get('sync_interval', 60)} seconds")
    print("="*60)

    try:
        detection_mode = config.get('detection_mode', '1')

        if detection_mode == '1':
            # Enhanced automatic detection mode
            print("🔍 Starting automatic POS system detection...")

            connector = EnhancedPOSConnector(config)

            # Test API connection first
            if not connector.api_client.authenticate():
                print("❌ Authentication failed. Please check your credentials.")
                retry = input("Would you like to run the setup wizard again? (y/n): ").lower()
                if retry == 'y':
                    setup_wizard()
                    print("Please restart the application.")
                sys.exit(1)

            print("✅ Authentication successful!")

            # Start enhanced monitoring
            await connector.start_monitoring()

            print("🚀 Enhanced POS monitoring started!")
            print("📊 Monitoring multiple POS systems simultaneously")
            print("Press Ctrl+C to stop the connector.")

            # Keep running
            try:
                while connector.running:
                    await asyncio.sleep(1)

                    # Display status every 60 seconds
                    if int(time.time()) % 60 == 0:
                        status = connector.get_status()
                        print(f"📈 Status: {status['discovered_systems']} POS systems, "
                              f"{status.get('monitored_folders', 0)} monitored folders, "
                              f"{status['active_monitors']} active monitors, "
                              f"{status['queue_size']} queued items")
            except KeyboardInterrupt:
                print("\n🛑 Shutdown requested...")
                connector.stop_monitoring()
                print("✅ POS Connector stopped.")

        elif detection_mode == '2':
            # File monitoring mode (legacy)
            print("📁 Starting file monitoring mode...")

            api = LaravelAPI(
                base_url=config['base_url'],
                email=config['email'],
                password=config.get('password', '')
            )

            if not api.authenticate():
                print("❌ Authentication failed. Please check your credentials.")
                sys.exit(1)

            print("✅ Authentication successful!")

            folder_path = config.get('invoice_folder', INVOICE_FOLDER)
            folder_mode = config.get('folder_detection_mode', 'manual')

            print(f"📂 Watching folder: {folder_path}")
            print(f"🔍 Folder detection mode: {folder_mode}")
            print("📄 Monitoring for new invoice files (PDF, JSON)...")
            print("✅ Ready to process transactions - filenames will be shown when processed")

            # Start the file watcher
            start_watcher(api, folder_path)

        else:
            print("🔧 Manual configuration mode not implemented yet.")
            print("Please use detection mode 1 or 2.")

    except KeyboardInterrupt:
        print("\n🛑 POS Connector stopped by user.")
    except Exception as e:
        print(f"❌ Error: {e}")
        logging.exception("Fatal error in main")
        sys.exit(1)

def run_interactive():
    """Run the connector in interactive mode"""
    print("🚀 Starting JoFotara POS Connector in interactive mode...")

    # Setup basic logging
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
    )

    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\n👋 Goodbye!")
    except Exception as e:
        print(f"❌ Fatal error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) > 1:
        command = sys.argv[1].lower()

        if command == "--setup":
            setup_wizard()
            print("✅ Setup complete. Run the program without --setup to start the connector.")
            sys.exit(0)
        elif command == "install":
            try:
                service_manager = WindowsServiceManager({})
                service_manager.install_service()
                print("✅ Service installed successfully")
                print("Use 'python main.py start' to start the service")
            except Exception as e:
                print(f"❌ Failed to install service: {e}")
                sys.exit(1)
        elif command == "uninstall":
            try:
                service_manager = WindowsServiceManager({})
                service_manager.uninstall_service()
                print("✅ Service uninstalled successfully")
            except Exception as e:
                print(f"❌ Failed to uninstall service: {e}")
                sys.exit(1)
        elif command == "start":
            try:
                service_manager = WindowsServiceManager({})
                service_manager.start_service()
                print("✅ Service started successfully")
            except Exception as e:
                print(f"❌ Failed to start service: {e}")
                sys.exit(1)
        elif command == "stop":
            try:
                service_manager = WindowsServiceManager({})
                service_manager.stop_service()
                print("✅ Service stopped successfully")
            except Exception as e:
                print(f"❌ Failed to stop service: {e}")
                sys.exit(1)
        elif command == "restart":
            try:
                service_manager = WindowsServiceManager({})
                service_manager.restart_service()
                print("✅ Service restarted successfully")
            except Exception as e:
                print(f"❌ Failed to restart service: {e}")
                sys.exit(1)
        elif command == "status":
            try:
                service_manager = WindowsServiceManager({})
                status = service_manager.get_service_status()
                print(f"Service Status: {status}")
            except Exception as e:
                print(f"❌ Failed to get service status: {e}")
                sys.exit(1)
        elif command == "service":
            # Run as service (called by Windows Service Manager)
            from pos_connector.service_manager import JoFotaraService
            import servicemanager
            import win32serviceutil

            if len(sys.argv) == 1:
                servicemanager.Initialize()
                servicemanager.PrepareToHostSingle(JoFotaraService)
                servicemanager.StartServiceCtrlDispatcher()
            else:
                win32serviceutil.HandleCommandLine(JoFotaraService)
        else:
            print("Usage: python main.py [--setup|install|uninstall|start|stop|restart|status|service]")
            print()
            print("Commands:")
            print("  --setup     Run setup wizard")
            print("  install     Install as Windows service")
            print("  uninstall   Uninstall Windows service")
            print("  start       Start Windows service")
            print("  stop        Stop Windows service")
            print("  restart     Restart Windows service")
            print("  status      Check Windows service status")
            print("  service     Run as service (internal use)")
            print()
            print("Run without arguments to start in interactive mode")
            sys.exit(1)
    else:
        # Run in interactive mode
        run_interactive()
