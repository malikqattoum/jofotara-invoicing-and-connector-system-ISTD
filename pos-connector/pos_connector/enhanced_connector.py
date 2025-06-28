#!/usr/bin/env python3
"""
Enhanced POS Connector that can connect to any Windows POS system
"""

import os
import json
import time
import logging
import threading
import asyncio
from datetime import datetime, timedelta
from pathlib import Path
from typing import Dict, Any, List, Optional, Callable
import queue
import sqlite3
import hashlib
import win32serviceutil
import win32service
import win32event
import winreg
import subprocess
import psutil
import pyodbc
import pymysql
import sqlite3
import requests
from concurrent.futures import ThreadPoolExecutor, as_completed

from .pos_adapters import *
from .data_extractors import *
from .laravel_api import LaravelAPI

class EnhancedPOSConnector:
    """
    Universal POS Connector that can detect and connect to any Windows POS system
    """

    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.logger = self._setup_logging()
        self.running = False
        self.pos_systems = []
        self.data_queue = queue.Queue()
        self.error_queue = queue.Queue()
        self.sync_threads = []
        self.pos_adapters = {}
        self.last_sync_times = {}
        self.failed_syncs = {}

        # Initialize API client
        self.api_client = LaravelAPI(
            base_url=config.get('base_url'),
            email=config.get('email'),
            password=config.get('password')
        )

        # Initialize database for local caching
        self.db_path = Path(__file__).parent.parent / 'data' / 'pos_cache.db'
        self.db_path.parent.mkdir(exist_ok=True)
        self._init_database()

        # Load POS adapters
        self._load_pos_adapters()

        self.logger.info("Enhanced POS Connector initialized")

    def _setup_logging(self) -> logging.Logger:
        """Setup logging configuration"""
        log_dir = Path(__file__).parent.parent / 'logs'
        log_dir.mkdir(exist_ok=True)

        logger = logging.getLogger('EnhancedPOSConnector')
        logger.setLevel(logging.INFO)

        # File handler
        file_handler = logging.FileHandler(log_dir / 'enhanced_connector.log')
        file_handler.setLevel(logging.INFO)

        # Console handler
        console_handler = logging.StreamHandler()
        console_handler.setLevel(logging.INFO)

        # Formatter
        formatter = logging.Formatter(
            '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        )
        file_handler.setFormatter(formatter)
        console_handler.setFormatter(formatter)

        logger.addHandler(file_handler)
        logger.addHandler(console_handler)

        return logger

    def _init_database(self):
        """Initialize local SQLite database for caching"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()

            # Create tables
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS pos_systems (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    type TEXT NOT NULL,
                    connection_string TEXT,
                    config TEXT,
                    status TEXT DEFAULT 'active',
                    last_sync TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ''')

            cursor.execute('''
                CREATE TABLE IF NOT EXISTS sync_log (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    pos_system_id INTEGER,
                    transaction_id TEXT,
                    sync_status TEXT,
                    error_message TEXT,
                    data_hash TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (pos_system_id) REFERENCES pos_systems (id)
                )
            ''')

            cursor.execute('''
                CREATE TABLE IF NOT EXISTS cached_transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    pos_system_id INTEGER,
                    transaction_id TEXT UNIQUE,
                    transaction_data TEXT,
                    processed INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (pos_system_id) REFERENCES pos_systems (id)
                )
            ''')

            conn.commit()
            conn.close()

            self.logger.info("Database initialized successfully")
        except Exception as e:
            self.logger.error(f"Failed to initialize database: {e}")
            raise

    def _load_pos_adapters(self):
        """Load all available POS adapters"""
        self.pos_adapters = {
            'square': SquarePOSAdapter(),
            'shopify': ShopifyPOSAdapter(),
            'quickbooks': QuickBooksPOSAdapter(),
            'sage': SagePOSAdapter(),
            'dynamics': DynamicsPOSAdapter(),
            'generic_sql': GenericSQLAdapter(),
            'generic_file': GenericFileAdapter(),
            'generic_api': GenericAPIAdapter(),
            'odbc': ODBCAdapter(),
            'csv': CSVAdapter(),
            'xml': XMLAdapter(),
            'json': JSONAdapter(),
            'excel': ExcelAdapter(),
            'access': AccessAdapter(),
            'foxpro': FoxProAdapter(),
            'dbase': DBaseAdapter(),
            'firebird': FirebirdAdapter(),
            'sqlite': SQLiteAdapter(),
            'mysql': MySQLAdapter(),
            'postgresql': PostgreSQLAdapter(),
            'mssql': MSSQLAdapter(),
            'oracle': OracleAdapter(),
            'registry': RegistryAdapter(),
            'network': NetworkAdapter(),
            'service': ServiceAdapter(),
            'com': COMAdapter(),
            'wmi': WMIAdapter(),
        }

        self.logger.info(f"Loaded {len(self.pos_adapters)} POS adapters")

    async def discover_pos_systems(self) -> List[Dict[str, Any]]:
        """
        Discover all POS systems on the current machine
        """
        self.logger.info("Starting POS system discovery...")
        discovered_systems = []

        # Use multiple discovery methods
        discovery_methods = [
            self._discover_by_registry,
            self._discover_by_services,
            self._discover_by_processes,
            self._discover_by_files,
            self._discover_by_databases,
            self._discover_by_network,
            self._discover_by_common_paths,
        ]

        # Run discovery methods concurrently
        with ThreadPoolExecutor(max_workers=len(discovery_methods)) as executor:
            futures = [executor.submit(method) for method in discovery_methods]

            for future in as_completed(futures):
                try:
                    systems = future.result()
                    if systems:
                        discovered_systems.extend(systems)
                except Exception as e:
                    self.logger.error(f"Discovery method failed: {e}")

        # Remove duplicates and validate systems
        unique_systems = self._deduplicate_systems(discovered_systems)
        validated_systems = await self._validate_systems(unique_systems)

        self.logger.info(f"Discovered {len(validated_systems)} POS systems")
        return validated_systems

    def _discover_by_registry(self) -> List[Dict[str, Any]]:
        """Discover POS systems by scanning Windows registry"""
        systems = []

        try:
            # Common registry locations for POS software
            registry_paths = [
                (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall"),
                (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall"),
                (winreg.HKEY_CURRENT_USER, r"SOFTWARE\Microsoft\Windows\CurrentVersion\Run"),
                (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\Microsoft\Windows\CurrentVersion\Run"),
            ]

            pos_keywords = [
                'pos', 'point of sale', 'retail', 'cash register', 'checkout',
                'square', 'shopify', 'quickbooks', 'sage', 'dynamics',
                'ncr', 'micros', 'aloha', 'toast', 'revel', 'lightspeed',
                'vend', 'shopkeep', 'clover', 'talech', 'loyverse',
                'erply', 'epos', 'tillpoint', 'retail pro', 'counterpoint'
            ]

            for hkey, subkey_path in registry_paths:
                try:
                    with winreg.OpenKey(hkey, subkey_path) as key:
                        for i in range(winreg.QueryInfoKey(key)[0]):
                            try:
                                subkey_name = winreg.EnumKey(key, i)
                                with winreg.OpenKey(key, subkey_name) as subkey:
                                    try:
                                        display_name = winreg.QueryValueEx(subkey, "DisplayName")[0]
                                        if any(keyword in display_name.lower() for keyword in pos_keywords):
                                            install_location = ""
                                            try:
                                                install_location = winreg.QueryValueEx(subkey, "InstallLocation")[0]
                                            except FileNotFoundError:
                                                try:
                                                    install_location = winreg.QueryValueEx(subkey, "UninstallString")[0]
                                                    install_location = os.path.dirname(install_location)
                                                except FileNotFoundError:
                                                    pass

                                            systems.append({
                                                'name': display_name,
                                                'type': 'registry_discovered',
                                                'install_path': install_location,
                                                'discovery_method': 'registry'
                                            })
                                    except FileNotFoundError:
                                        pass
                            except Exception:
                                continue
                except Exception as e:
                    self.logger.debug(f"Registry scan error: {e}")
                    continue

        except Exception as e:
            self.logger.error(f"Registry discovery failed: {e}")

        return systems

    def _discover_by_services(self) -> List[Dict[str, Any]]:
        """Discover POS systems by scanning Windows services"""
        systems = []

        try:
            pos_service_keywords = [
                'pos', 'retail', 'cash', 'checkout', 'square', 'shopify',
                'quickbooks', 'sage', 'dynamics', 'micros', 'aloha',
                'toast', 'ncr', 'point of sale'
            ]

            for service in psutil.win_service_iter():
                try:
                    service_info = service.as_dict()
                    service_name = service_info.get('name', '').lower()
                    display_name = service_info.get('display_name', '').lower()

                    if any(keyword in service_name or keyword in display_name
                           for keyword in pos_service_keywords):

                        # Get executable path
                        try:
                            service_obj = psutil.win_service_get(service_info['name'])
                            binpath = service_obj.binpath()
                        except:
                            binpath = ""

                        systems.append({
                            'name': service_info.get('display_name', service_info.get('name')),
                            'type': 'service',
                            'service_name': service_info.get('name'),
                            'status': service_info.get('status'),
                            'executable_path': binpath,
                            'discovery_method': 'service'
                        })
                except Exception:
                    continue

        except Exception as e:
            self.logger.error(f"Service discovery failed: {e}")

        return systems

    def _discover_by_processes(self) -> List[Dict[str, Any]]:
        """Discover POS systems by scanning running processes"""
        systems = []

        try:
            pos_process_keywords = [
                'pos', 'retail', 'cash', 'square', 'shopify', 'quickbooks',
                'sage', 'dynamics', 'micros', 'aloha', 'toast', 'ncr'
            ]

            for proc in psutil.process_iter(['pid', 'name', 'exe', 'cmdline']):
                try:
                    process_info = proc.info
                    process_name = process_info.get('name', '').lower()

                    if any(keyword in process_name for keyword in pos_process_keywords):
                        systems.append({
                            'name': process_info.get('name'),
                            'type': 'process',
                            'pid': process_info.get('pid'),
                            'executable_path': process_info.get('exe'),
                            'command_line': process_info.get('cmdline'),
                            'discovery_method': 'process'
                        })
                except (psutil.NoSuchProcess, psutil.AccessDenied):
                    continue

        except Exception as e:
            self.logger.error(f"Process discovery failed: {e}")

        return systems

    def _discover_by_files(self) -> List[Dict[str, Any]]:
        """Discover POS systems by scanning common file locations"""
        systems = []

        try:
            # Common POS installation directories
            search_paths = [
                r"C:\Program Files",
                r"C:\Program Files (x86)",
                r"C:\POS",
                r"C:\Retail",
                r"C:\Cash",
                r"C:\Square",
                r"C:\Shopify",
                r"C:\QuickBooks",
                r"C:\Sage",
                r"C:\NCR",
                r"C:\Micros",
                r"C:\Aloha",
                r"C:\Toast",
                os.path.expanduser("~\\AppData\\Local"),
                os.path.expanduser("~\\AppData\\Roaming"),
            ]

            pos_file_patterns = [
                'pos*.exe', 'retail*.exe', 'cash*.exe', 'checkout*.exe',
                'square*.exe', 'shopify*.exe', 'quickbooks*.exe',
                'pos*.db', 'pos*.mdb', 'pos*.accdb',
                'sales*.db', 'transactions*.db'
            ]

            for search_path in search_paths:
                if os.path.exists(search_path):
                    for root, dirs, files in os.walk(search_path):
                        # Limit depth to avoid scanning entire system
                        if root.count(os.sep) - search_path.count(os.sep) > 3:
                            continue

                        for file in files:
                            file_lower = file.lower()
                            if any(pattern.replace('*', '') in file_lower
                                   for pattern in pos_file_patterns):

                                full_path = os.path.join(root, file)
                                systems.append({
                                    'name': f"POS System ({file})",
                                    'type': 'file_based',
                                    'file_path': full_path,
                                    'file_type': os.path.splitext(file)[1],
                                    'discovery_method': 'file_scan'
                                })

        except Exception as e:
            self.logger.error(f"File discovery failed: {e}")

        return systems

    def _discover_by_databases(self) -> List[Dict[str, Any]]:
        """Discover POS systems by scanning for database files and connections"""
        systems = []

        try:
            # Database file extensions to look for
            db_extensions = ['.db', '.sqlite', '.sqlite3', '.mdb', '.accdb', '.dbf']

            # Common database locations
            db_search_paths = [
                r"C:\POS",
                r"C:\Data",
                os.path.expanduser("~\\Documents"),
                os.path.expanduser("~\\AppData\\Local"),
                os.path.expanduser("~\\AppData\\Roaming"),
            ]

            for search_path in db_search_paths:
                if os.path.exists(search_path):
                    for root, dirs, files in os.walk(search_path):
                        if root.count(os.sep) - search_path.count(os.sep) > 2:
                            continue

                        for file in files:
                            if any(file.lower().endswith(ext) for ext in db_extensions):
                                full_path = os.path.join(root, file)

                                # Check if it looks like a POS database
                                if self._is_pos_database(full_path):
                                    systems.append({
                                        'name': f"Database POS ({file})",
                                        'type': 'database',
                                        'database_path': full_path,
                                        'database_type': os.path.splitext(file)[1],
                                        'discovery_method': 'database_scan'
                                    })

            # Check for SQL Server instances
            try:
                # Try to connect to local SQL Server instances
                sql_instances = self._discover_sql_server_instances()
                for instance in sql_instances:
                    systems.append({
                        'name': f"SQL Server POS ({instance})",
                        'type': 'sql_server',
                        'connection_string': instance,
                        'discovery_method': 'sql_server_scan'
                    })
            except Exception as e:
                self.logger.debug(f"SQL Server discovery failed: {e}")

        except Exception as e:
            self.logger.error(f"Database discovery failed: {e}")

        return systems

    def _discover_by_network(self) -> List[Dict[str, Any]]:
        """Discover POS systems by scanning network connections"""
        systems = []

        try:
            # Check for common POS system ports
            pos_ports = [1433, 3306, 5432, 8080, 443, 80, 9090, 8443]

            for connection in psutil.net_connections():
                if connection.laddr and connection.laddr.port in pos_ports:
                    try:
                        # Try to identify the process
                        if connection.pid:
                            proc = psutil.Process(connection.pid)
                            proc_name = proc.name()

                            # Check if it looks like a POS system
                            if any(keyword in proc_name.lower()
                                   for keyword in ['pos', 'retail', 'cash', 'sql']):

                                systems.append({
                                    'name': f"Network POS ({proc_name})",
                                    'type': 'network_service',
                                    'port': connection.laddr.port,
                                    'process_name': proc_name,
                                    'process_id': connection.pid,
                                    'discovery_method': 'network_scan'
                                })
                    except (psutil.NoSuchProcess, psutil.AccessDenied):
                        continue

        except Exception as e:
            self.logger.error(f"Network discovery failed: {e}")

        return systems

    def _discover_by_common_paths(self) -> List[Dict[str, Any]]:
        """Discover POS systems by checking common installation paths"""
        systems = []

        common_pos_paths = [
            (r"C:\Program Files\NCR\Aloha", "Aloha POS"),
            (r"C:\Program Files\Micros\Res", "Micros RES"),
            (r"C:\Program Files\Square\Square Point of Sale", "Square POS"),
            (r"C:\Program Files\Shopify\Shopify POS", "Shopify POS"),
            (r"C:\Program Files\Intuit\QuickBooks Point of Sale", "QuickBooks POS"),
            (r"C:\Program Files\Sage\Sage 50", "Sage 50"),
            (r"C:\Program Files\Microsoft Dynamics", "Microsoft Dynamics"),
            (r"C:\Program Files\Toast\Toast POS", "Toast POS"),
            (r"C:\Program Files\Lightspeed", "Lightspeed"),
            (r"C:\Program Files\Vend", "Vend POS"),
            (r"C:\Program Files\Clover", "Clover POS"),
            (r"C:\Program Files\Revel", "Revel Systems"),
            (r"C:\POS", "Generic POS"),
            (r"C:\Retail", "Generic Retail System"),
        ]

        for path, name in common_pos_paths:
            if os.path.exists(path):
                systems.append({
                    'name': name,
                    'type': 'common_path',
                    'install_path': path,
                    'discovery_method': 'common_path_scan'
                })

        return systems

    def _is_pos_database(self, db_path: str) -> bool:
        """Check if a database file appears to be from a POS system"""
        try:
            # Check file name for POS-related keywords
            filename = os.path.basename(db_path).lower()
            pos_keywords = [
                'pos', 'retail', 'sales', 'transaction', 'cash', 'checkout',
                'inventory', 'customer', 'product', 'invoice', 'receipt'
            ]

            if any(keyword in filename for keyword in pos_keywords):
                return True

            # For SQLite databases, try to inspect table structure
            if db_path.lower().endswith(('.db', '.sqlite', '.sqlite3')):
                try:
                    conn = sqlite3.connect(db_path)
                    cursor = conn.cursor()
                    cursor.execute("SELECT name FROM sqlite_master WHERE type='table'")
                    tables = [row[0].lower() for row in cursor.fetchall()]
                    conn.close()

                    # Check for typical POS table names
                    pos_table_keywords = [
                        'transaction', 'sale', 'receipt', 'payment', 'product',
                        'customer', 'inventory', 'tax', 'discount', 'tender'
                    ]

                    return any(any(keyword in table for keyword in pos_table_keywords)
                              for table in tables)
                except Exception:
                    pass

            return False
        except Exception:
            return False

    def _discover_sql_server_instances(self) -> List[str]:
        """Discover SQL Server instances"""
        instances = []

        try:
            # Try to get SQL Server instances from registry
            with winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE,
                               r"SOFTWARE\Microsoft\Microsoft SQL Server") as key:
                try:
                    installed_instances = winreg.QueryValueEx(key, "InstalledInstances")[0]
                    for instance in installed_instances:
                        if instance == "MSSQLSERVER":
                            instances.append("localhost")
                        else:
                            instances.append(f"localhost\\{instance}")
                except FileNotFoundError:
                    pass
        except Exception:
            pass

        # Add common instance names
        common_instances = [
            "localhost",
            "localhost\\SQLEXPRESS",
            "localhost\\POS",
            "localhost\\RETAIL",
            ".\\SQLEXPRESS",
            "(local)",
            "(local)\\SQLEXPRESS"
        ]

        instances.extend(common_instances)
        return list(set(instances))  # Remove duplicates

    def _deduplicate_systems(self, systems: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Remove duplicate systems from the discovery results"""
        unique_systems = []
        seen_systems = set()

        for system in systems:
            # Create a unique identifier for the system
            identifier_parts = [
                system.get('name', ''),
                system.get('type', ''),
                system.get('install_path', ''),
                system.get('file_path', ''),
                system.get('database_path', ''),
                system.get('service_name', ''),
            ]

            identifier = hashlib.md5(
                '|'.join(str(part) for part in identifier_parts).encode()
            ).hexdigest()

            if identifier not in seen_systems:
                seen_systems.add(identifier)
                unique_systems.append(system)

        return unique_systems

    async def _validate_systems(self, systems: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Validate discovered POS systems"""
        validated_systems = []

        for system in systems:
            try:
                # Try to determine the best adapter for this system
                adapter = await self._get_adapter_for_system(system)
                if adapter:
                    system['adapter'] = adapter.__class__.__name__
                    system['validated'] = True
                    validated_systems.append(system)
                else:
                    system['validated'] = False
                    self.logger.warning(f"No suitable adapter found for system: {system.get('name')}")
            except Exception as e:
                self.logger.error(f"Error validating system {system.get('name')}: {e}")
                system['validated'] = False

        return validated_systems

    async def _get_adapter_for_system(self, system: Dict[str, Any]) -> Optional[Any]:
        """Get the most suitable adapter for a POS system"""
        system_type = system.get('type', '')
        system_name = system.get('name', '').lower()

        # Try specific adapters first
        if 'square' in system_name:
            return self.pos_adapters.get('square')
        elif 'shopify' in system_name:
            return self.pos_adapters.get('shopify')
        elif 'quickbooks' in system_name:
            return self.pos_adapters.get('quickbooks')
        elif 'sage' in system_name:
            return self.pos_adapters.get('sage')
        elif 'dynamics' in system_name:
            return self.pos_adapters.get('dynamics')

        # Try by system type
        if system_type == 'database':
            db_type = system.get('database_type', '').lower()
            if db_type in ['.db', '.sqlite', '.sqlite3']:
                return self.pos_adapters.get('sqlite')
            elif db_type in ['.mdb', '.accdb']:
                return self.pos_adapters.get('access')
            elif db_type == '.dbf':
                return self.pos_adapters.get('dbase')
        elif system_type == 'sql_server':
            return self.pos_adapters.get('mssql')
        elif system_type == 'file_based':
            file_type = system.get('file_type', '').lower()
            if file_type == '.json':
                return self.pos_adapters.get('json')
            elif file_type == '.xml':
                return self.pos_adapters.get('xml')
            elif file_type in ['.csv', '.txt']:
                return self.pos_adapters.get('csv')
            elif file_type in ['.xls', '.xlsx']:
                return self.pos_adapters.get('excel')

        # Default to generic adapters
        if system_type in ['database', 'sql_server']:
            return self.pos_adapters.get('generic_sql')
        elif system_type == 'file_based':
            return self.pos_adapters.get('generic_file')
        elif system_type == 'network_service':
            return self.pos_adapters.get('generic_api')

        return self.pos_adapters.get('generic_sql')  # Default fallback

    async def start_monitoring(self):
        """Start monitoring all discovered POS systems"""
        self.running = True
        self.logger.info("Starting POS monitoring...")

        # Discover POS systems
        self.pos_systems = await self.discover_pos_systems()

        if not self.pos_systems:
            self.logger.warning("No POS systems discovered")
            return

        # Start monitoring threads for each system
        for system in self.pos_systems:
            if system.get('validated', False):
                thread = threading.Thread(
                    target=self._monitor_pos_system,
                    args=(system,),
                    daemon=True
                )
                thread.start()
                self.sync_threads.append(thread)

        # Start data processing loop
        processing_thread = threading.Thread(
            target=self._process_data_queue,
            daemon=True
        )
        processing_thread.start()

        self.logger.info(f"Started monitoring {len(self.sync_threads)} POS systems")

    def _monitor_pos_system(self, system: Dict[str, Any]):
        """Monitor a single POS system for new transactions"""
        system_name = system.get('name', 'Unknown')
        self.logger.info(f"Starting monitoring for {system_name}")

        try:
            # Get the appropriate adapter
            adapter_name = system.get('adapter')
            adapter = self.pos_adapters.get(adapter_name.lower().replace('adapter', ''))

            if not adapter:
                self.logger.error(f"No adapter found for {system_name}")
                return

            # Configure adapter
            adapter.configure(system)

            last_sync = self.last_sync_times.get(system_name, datetime.min)

            while self.running:
                try:
                    # Get new transactions since last sync
                    new_transactions = adapter.get_new_transactions(last_sync)

                    if new_transactions:
                        self.logger.info(f"Found {len(new_transactions)} new transactions from {system_name}")

                        for transaction in new_transactions:
                            # Add to processing queue
                            self.data_queue.put({
                                'system': system,
                                'transaction': transaction,
                                'timestamp': datetime.now()
                            })

                        # Update last sync time
                        self.last_sync_times[system_name] = datetime.now()
                        self._update_system_sync_time(system, datetime.now())

                    # Clear failed sync count on success
                    if system_name in self.failed_syncs:
                        del self.failed_syncs[system_name]

                    # Wait before next sync
                    sync_interval = self.config.get('sync_interval', 60)
                    time.sleep(sync_interval)

                except Exception as e:
                    self.logger.error(f"Error monitoring {system_name}: {e}")

                    # Track failed syncs
                    self.failed_syncs[system_name] = self.failed_syncs.get(system_name, 0) + 1

                    # Exponential backoff on failures
                    wait_time = min(300, 30 * (2 ** self.failed_syncs[system_name]))
                    time.sleep(wait_time)

        except Exception as e:
            self.logger.error(f"Failed to monitor {system_name}: {e}")

    def _process_data_queue(self):
        """Process the data queue and send to Laravel API"""
        while self.running:
            try:
                # Get data from queue (with timeout)
                try:
                    data = self.data_queue.get(timeout=5)
                except queue.Empty:
                    continue

                system = data['system']
                transaction = data['transaction']

                # Convert to Laravel format
                invoice_data = self._convert_to_laravel_format(system, transaction)

                if invoice_data:
                    # Send to Laravel API
                    result = self.api_client.create_invoice(invoice_data)

                    if result:
                        self.logger.info(f"Successfully created invoice {result.get('id')} from {system.get('name')}")

                        # Cache the transaction
                        self._cache_transaction(system, transaction, 'success')

                        # Submit to JoFotara if configured
                        if self.config.get('auto_submit_jofotara', False):
                            self.api_client.submit_invoice(result['id'])
                    else:
                        self.logger.error(f"Failed to create invoice from {system.get('name')}")
                        self._cache_transaction(system, transaction, 'failed')

                self.data_queue.task_done()

            except Exception as e:
                self.logger.error(f"Error processing data queue: {e}")

    def _convert_to_laravel_format(self, system: Dict[str, Any], transaction: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """Convert POS transaction to Laravel invoice format"""
        try:
            # This is a generic converter - specific adapters should provide their own
            invoice_data = {
                'customer_name': transaction.get('customer_name', 'Walk-in Customer'),
                'customer_email': transaction.get('customer_email', ''),
                'customer_phone': transaction.get('customer_phone', ''),
                'invoice_date': transaction.get('date', datetime.now().isoformat()),
                'due_date': transaction.get('due_date', ''),
                'subtotal': transaction.get('subtotal', 0),
                'tax_amount': transaction.get('tax_amount', 0),
                'total_amount': transaction.get('total_amount', 0),
                'currency': transaction.get('currency', 'SAR'),
                'notes': f"Imported from {system.get('name')}",
                'items': []
            }

            # Convert line items
            for item in transaction.get('items', []):
                invoice_data['items'].append({
                    'description': item.get('description', item.get('name', 'Unknown Item')),
                    'quantity': item.get('quantity', 1),
                    'unit_price': item.get('unit_price', item.get('price', 0)),
                    'total_price': item.get('total_price', item.get('total', 0)),
                    'tax_rate': item.get('tax_rate', 0),
                })

            return invoice_data

        except Exception as e:
            self.logger.error(f"Error converting transaction to Laravel format: {e}")
            return None

    def _cache_transaction(self, system: Dict[str, Any], transaction: Dict[str, Any], status: str):
        """Cache transaction to local database"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()

            # Get system ID
            cursor.execute("SELECT id FROM pos_systems WHERE name = ?", (system.get('name'),))
            result = cursor.fetchone()

            if not result:
                # Insert new system
                cursor.execute('''
                    INSERT INTO pos_systems (name, type, config, status)
                    VALUES (?, ?, ?, ?)
                ''', (system.get('name'), system.get('type'), json.dumps(system), 'active'))
                system_id = cursor.lastrowid
            else:
                system_id = result[0]

            # Cache transaction
            transaction_id = transaction.get('id', transaction.get('transaction_id', str(time.time())))
            transaction_data = json.dumps(transaction)

            cursor.execute('''
                INSERT OR REPLACE INTO cached_transactions
                (pos_system_id, transaction_id, transaction_data, processed)
                VALUES (?, ?, ?, ?)
            ''', (system_id, transaction_id, transaction_data, 1 if status == 'success' else 0))

            # Log sync result
            cursor.execute('''
                INSERT INTO sync_log (pos_system_id, transaction_id, sync_status)
                VALUES (?, ?, ?)
            ''', (system_id, transaction_id, status))

            conn.commit()
            conn.close()

        except Exception as e:
            self.logger.error(f"Error caching transaction: {e}")

    def _update_system_sync_time(self, system: Dict[str, Any], sync_time: datetime):
        """Update last sync time for a system"""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()

            cursor.execute('''
                UPDATE pos_systems SET last_sync = ? WHERE name = ?
            ''', (sync_time, system.get('name')))

            conn.commit()
            conn.close()

        except Exception as e:
            self.logger.error(f"Error updating sync time: {e}")

    def stop_monitoring(self):
        """Stop monitoring all POS systems"""
        self.logger.info("Stopping POS monitoring...")
        self.running = False

        # Wait for threads to finish
        for thread in self.sync_threads:
            if thread.is_alive():
                thread.join(timeout=5)

        self.logger.info("POS monitoring stopped")

    def get_status(self) -> Dict[str, Any]:
        """Get current status of the connector"""
        return {
            'running': self.running,
            'discovered_systems': len(self.pos_systems),
            'active_monitors': len([t for t in self.sync_threads if t.is_alive()]),
            'queue_size': self.data_queue.qsize(),
            'last_sync_times': self.last_sync_times,
            'failed_syncs': self.failed_syncs
        }
