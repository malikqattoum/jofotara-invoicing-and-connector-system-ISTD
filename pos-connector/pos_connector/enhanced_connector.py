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
from .pos_api_client import PosApiClient
from .folder_detector import InvoiceFolderDetector

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
        # Use POS API client if API key is provided, otherwise use Laravel API
        if config.get('api_key'):
            self.api_client = PosApiClient(
                base_url=config.get('base_url'),
                api_key=config.get('api_key'),
                customer_id=config.get('customer_id')
            )
            self.use_pos_api = True
        else:
            self.api_client = LaravelAPI(
                base_url=config.get('base_url'),
                email=config.get('email'),
                password=config.get('password')
            )
            self.use_pos_api = False

        # Initialize database for local caching
        self.db_path = Path(__file__).parent.parent / 'data' / 'pos_cache.db'
        self.db_path.parent.mkdir(exist_ok=True)
        self._init_database()

        # Load POS adapters
        self._load_pos_adapters()

        # Initialize folder detector
        self.folder_detector = InvoiceFolderDetector(self.logger)
        self.monitored_folders = []

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
            'aronium': AroniumPOSAdapter(),
            'universal': UniversalPOSAdapter(),
        }

        self.logger.info(f"Loaded {len(self.pos_adapters)} POS adapters")

    async def discover_pos_systems(self) -> List[Dict[str, Any]]:
        """
        Discover all POS systems on the current machine
        """
        self.logger.info("Starting POS system discovery...")
        discovered_systems = []

        # Use multiple discovery methods with names for logging
        # Start with faster methods first, then slower ones
        fast_discovery_methods = [
            ("Services Scan", self._discover_by_services),
            ("Processes Scan", self._discover_by_processes),
        ]

        slow_discovery_methods = [
            ("Registry Scan", self._discover_by_registry),
            ("Files Scan", self._discover_by_files),
            ("Databases Scan", self._discover_by_databases),
            ("Network Scan", self._discover_by_network),
            ("Common Paths Scan", self._discover_by_common_paths),
        ]

        # Check if we should run quick discovery only
        quick_discovery = self.config.get('quick_discovery', False)
        discovery_methods = fast_discovery_methods if quick_discovery else (fast_discovery_methods + slow_discovery_methods)

        # Run discovery methods concurrently with timeout
        with ThreadPoolExecutor(max_workers=len(discovery_methods)) as executor:
            # Submit all tasks with names
            futures = {}
            for method_name, method_func in discovery_methods:
                future = executor.submit(method_func)
                futures[future] = method_name
                self.logger.info(f"Started {method_name}...")

            # Process completed futures with timeout
            for future in as_completed(futures, timeout=120):  # 2 minute timeout per method
                method_name = futures[future]
                try:
                    self.logger.info(f"Processing results from {method_name}...")
                    systems = future.result(timeout=30)  # 30 second timeout for result
                    if systems:
                        discovered_systems.extend(systems)
                        self.logger.info(f"{method_name} found {len(systems)} potential systems")
                    else:
                        self.logger.info(f"{method_name} completed - no systems found")
                except Exception as e:
                    self.logger.error(f"{method_name} failed: {e}")

        self.logger.info(f"Discovery phase completed. Found {len(discovered_systems)} potential systems")

        # Remove duplicates and validate systems
        unique_systems = self._deduplicate_systems(discovered_systems)
        self.logger.info(f"After deduplication: {len(unique_systems)} unique systems")

        validated_systems = await self._validate_systems(unique_systems)

        self.logger.info(f"Discovered {len(validated_systems)} valid POS systems")
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

            # Exclude common false positives from registry
            registry_exclude_keywords = [
                'microsoft', 'windows', 'office', 'visual studio', 'adobe',
                'google', 'chrome', 'firefox', 'java', 'intel', 'nvidia',
                'realtek', 'driver', 'framework', 'runtime', '.net',
                'directx', 'steam', 'epic games', 'antivirus'
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
                                        display_name_lower = display_name.lower()

                                        # Check if it matches POS keywords
                                        has_pos_keyword = any(keyword in display_name_lower for keyword in pos_keywords)

                                        # Check if it should be excluded
                                        should_exclude = any(keyword in display_name_lower for keyword in registry_exclude_keywords)

                                        if has_pos_keyword and not should_exclude:
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
            # Universal POS system keywords (balanced - inclusive but not too broad)
            pos_service_keywords = [
                'pos ', 'point of sale', 'cash register', 'checkout',
                'till ', 'epos', 'pos system', 'pos software',
                'retail management', 'restaurant pos', 'store pos',
                'square', 'shopify', 'quickbooks', 'sage', 'dynamics',
                'micros', 'aloha', 'toast', 'ncr', 'clover', 'lightspeed',
                'revel', 'vend', 'shopkeep', 'talech', 'loyverse', 'erply',
                'tillpoint', 'retail pro', 'counterpoint', 'aronium'
            ]

            # Windows services to exclude (common false positives)
            exclude_keywords = [
                'microsoft', 'windows', 'defender', 'edge', 'office',
                'bitlocker', 'passport', 'encryption', 'antivirus',
                'update', 'sms', 'shadow', 'backup', 'system',
                'cloud', 'identity', 'wireless', 'device management',
                'aswb', 'avast', 'efs', 'encrypting file system',
                'state repository', 'repository service', 'adobe',
                'google', 'chrome', 'firefox', 'java', 'intel',
                'nvidia', 'realtek', 'bluetooth', 'audio', 'driver',
                'framework', 'runtime', 'visual studio', 'sql server',
                '.net', 'directx', 'steam', 'epic games', 'interface service',
                'demo service', 'nfc/se manager', 'image acquisition',
                'user data storage', 'network store', 'still image'
            ]

            for service in psutil.win_service_iter():
                try:
                    service_info = service.as_dict()
                    service_name = service_info.get('name', '').lower()
                    display_name = service_info.get('display_name', '').lower()

                    # Check if it matches POS keywords
                    has_pos_keyword = any(keyword in service_name or keyword in display_name
                                         for keyword in pos_service_keywords)

                    # Check if it should be excluded
                    should_exclude = any(keyword in service_name or keyword in display_name
                                        for keyword in exclude_keywords)

                    if has_pos_keyword and not should_exclude:

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
            # Universal business software keywords (catch any potential POS)
            pos_process_keywords = [
                # Direct POS terms
                'pos', 'point-of-sale', 'cash-register', 'checkout', 'till', 'epos',
                'register', 'posapp', 'cashier', 'terminal',
                # Business software that could be POS
                'restaurant', 'retail', 'store', 'shop', 'merchant', 'business',
                'invoice', 'billing', 'receipt', 'sale', 'payment', 'order',
                'inventory', 'customer', 'transaction', 'accounting',
                # Specific POS systems
                'square', 'shopify', 'quickbooks', 'sage', 'dynamics',
                'micros', 'aloha', 'toast', 'ncr', 'clover', 'lightspeed',
                'revel', 'vend', 'shopkeep', 'talech', 'loyverse', 'erply',
                'tillpoint', 'retail pro', 'counterpoint', 'aronium',
                # Common business app patterns
                'manager', 'admin', 'system', 'software', 'app'
            ]

            # Exclude non-business processes
            exclude_process_keywords = [
                'microsoft', 'windows', 'system32', 'office', 'word', 'excel',
                'chrome', 'firefox', 'edge', 'browser', 'antivirus', 'defender',
                'adobe', 'acrobat', 'reader', 'steam', 'game', 'nvidia', 'intel',
                'driver', 'service', 'svchost', 'explorer', 'taskmgr', 'notepad',
                'calculator', 'paint', 'cmd', 'powershell', 'conhost'
            ]

            for proc in psutil.process_iter(['pid', 'name', 'exe', 'cmdline']):
                try:
                    process_info = proc.info
                    process_name = process_info.get('name', '').lower()

                    # Check if it matches business/POS keywords
                    has_pos_keyword = any(keyword in process_name for keyword in pos_process_keywords)

                    # Check if it should be excluded
                    should_exclude = any(keyword in process_name for keyword in exclude_process_keywords)

                    if has_pos_keyword and not should_exclude:
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

            # Universal business software patterns
            pos_file_patterns = [
                # Executable patterns
                'pos*.exe', 'retail*.exe', 'store*.exe', 'shop*.exe', 'cash*.exe',
                'checkout*.exe', 'till*.exe', 'register*.exe', 'payment*.exe',
                'invoice*.exe', 'billing*.exe', 'order*.exe', 'sale*.exe',
                'business*.exe', 'restaurant*.exe', 'merchant*.exe',
                # Database patterns
                'pos*.db', 'retail*.db', 'store*.db', 'shop*.db', 'sales*.db',
                'transactions*.db', 'orders*.db', 'customers*.db', 'invoice*.db',
                'pos*.mdb', 'pos*.accdb', 'retail*.mdb', 'sales*.mdb',
                # Data file patterns
                'pos*.sqlite', 'sales*.sqlite', 'transactions*.sqlite',
                'pos*.csv', 'sales*.csv', 'orders*.csv', 'invoices*.csv',
                'pos*.json', 'sales*.json', 'transactions*.json'
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
            # Universal database file extensions
            db_extensions = ['.db', '.sqlite', '.sqlite3', '.mdb', '.accdb', '.dbf', '.sdf', '.ldf', '.mdf']

            # Universal business data locations
            db_search_paths = [
                r"C:\POS", r"C:\Data", r"C:\Business", r"C:\Store", r"C:\Retail",
                r"C:\Restaurant", r"C:\Shop", r"C:\Sales", r"C:\Inventory",
                r"C:\Program Files", r"C:\Program Files (x86)",
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
                    # Find the adapter key from pos_adapters dictionary
                    adapter_key = None
                    for key, value in self.pos_adapters.items():
                        if value.__class__.__name__ == adapter.__class__.__name__:
                            adapter_key = key
                            break

                    system['adapter'] = adapter_key
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
        if 'aronium' in system_name:
            return self.pos_adapters.get('aronium')
        elif 'square' in system_name:
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

        # Default to universal adapter for unknown systems
        if system_type in ['database', 'sql_server']:
            return self.pos_adapters.get('generic_sql')
        elif system_type == 'file_based':
            return self.pos_adapters.get('generic_file')
        elif system_type == 'network_service':
            return self.pos_adapters.get('generic_api')

        # Use universal adapter as the ultimate fallback - it can handle any POS system
        return self.pos_adapters.get('universal')

    async def start_monitoring(self):
        """Start monitoring all discovered POS systems"""
        self.running = True
        self.logger.info("Starting POS monitoring...")

        # Discover POS systems
        self.pos_systems = await self.discover_pos_systems()

        # Also discover and monitor invoice folders
        await self._discover_and_monitor_folders()

        if not self.pos_systems and not self.monitored_folders:
            self.logger.warning("No POS systems or invoice folders discovered")
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
            adapter = self.pos_adapters.get(adapter_name)

            if not adapter:
                self.logger.error(f"No adapter found for {system_name} (adapter_name: {adapter_name})")
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

                if self.use_pos_api:
                    # Convert to POS transaction format
                    transaction_data = self._convert_to_pos_format(system, transaction)

                    if transaction_data:
                        # Send to POS Connector API
                        result = self.api_client.send_transactions([transaction_data])

                        if result:
                            self.logger.info(f"Successfully sent transaction from {system.get('name')}")
                            self._cache_transaction(system, transaction, 'success')
                        else:
                            self.logger.error(f"Failed to send transaction from {system.get('name')}")
                            self._cache_transaction(system, transaction, 'failed')
                else:
                    # Convert to Laravel format (legacy)
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

    def _convert_to_pos_format(self, system: Dict[str, Any], transaction: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """Convert POS transaction to POS Connector API format"""
        try:
            # Generate a unique transaction ID if not present
            transaction_id = transaction.get('transaction_id') or transaction.get('id') or f"{system.get('name', 'unknown')}_{int(time.time())}"

            # Convert to POS Connector format
            pos_transaction = {
                'transaction_id': transaction_id,
                'pos_system': system.get('name', 'Unknown POS'),
                'source_file': transaction.get('source_file'),
                'transaction_date': transaction.get('date', datetime.now().isoformat()),
                'customer_name': transaction.get('customer_name', 'Walk-in Customer'),
                'customer_email': transaction.get('customer_email'),
                'customer_phone': transaction.get('customer_phone'),
                'items': transaction.get('items', []),
                'subtotal': transaction.get('subtotal', 0),
                'tax_amount': transaction.get('tax_amount', 0),
                'total_amount': transaction.get('total_amount', 0),
                'tip_amount': transaction.get('tip_amount'),
                'payment_method': transaction.get('payment_method'),
                'payment_reference': transaction.get('payment_reference'),
                'payment_status': transaction.get('payment_status', 'completed'),
                'location': transaction.get('location'),
                'employee': transaction.get('employee'),
                'notes': transaction.get('notes', f"Imported from {system.get('name')}")
            }

            return pos_transaction

        except Exception as e:
            self.logger.error(f"Error converting transaction to POS format: {e}")
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
        """Stop monitoring all POS systems and folders"""
        self.logger.info("Stopping POS monitoring...")
        self.running = False

        # Stop folder monitoring
        self.stop_folder_monitoring()

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
            'monitored_folders': len(self.monitored_folders),
            'active_monitors': len([t for t in self.sync_threads if t.is_alive()]),
            'queue_size': self.data_queue.qsize(),
            'last_sync_times': self.last_sync_times,
            'failed_syncs': self.failed_syncs
        }

    async def _discover_and_monitor_folders(self):
        """Discover and start monitoring invoice folders"""
        self.logger.info("Discovering invoice folders...")

        # Get detected folders
        detected_folders = self.folder_detector.detect_invoice_folders()

        # Filter folders with recent activity (score > 10)
        active_folders = [f for f in detected_folders if f['score'] > 10.0]

        if active_folders:
            self.logger.info(f"Found {len(active_folders)} active invoice folders")

            # Start monitoring top folders
            for folder_info in active_folders[:5]:  # Monitor top 5 folders
                self._start_folder_monitoring(folder_info)
                self.monitored_folders.append(folder_info)
        else:
            self.logger.info("No active invoice folders detected")

    def _start_folder_monitoring(self, folder_info: Dict[str, Any]):
        """Start monitoring a specific folder for invoice files"""
        from .watcher import InvoiceHandler
        from watchdog.observers import Observer

        try:
            folder_path = folder_info['path']
            pos_system = folder_info.get('pos_system', 'unknown')
            self.logger.info(f"Starting folder monitoring: {folder_path} (POS: {pos_system})")

            # Create enhanced invoice handler with folder info
            handler = EnhancedInvoiceHandler(self.api_client, folder_info, self.logger)

            # Create observer
            observer = Observer()
            observer.schedule(handler, folder_path, recursive=False)
            observer.start()

            # Store observer reference for cleanup
            folder_info['observer'] = observer

            self.logger.info(f"✅ Folder monitoring started: {folder_path}")

        except Exception as e:
            self.logger.error(f"Failed to start folder monitoring for {folder_path}: {e}")

    def stop_folder_monitoring(self):
        """Stop all folder monitoring"""
        for folder_info in self.monitored_folders:
            observer = folder_info.get('observer')
            if observer:
                try:
                    observer.stop()
                    observer.join(timeout=5)
                    self.logger.info(f"Stopped monitoring: {folder_info['path']}")
                except Exception as e:
                    self.logger.error(f"Error stopping folder monitor: {e}")


class EnhancedInvoiceHandler:
    """Enhanced invoice handler that shows detailed processing information"""

    def __init__(self, api_client, folder_info: Dict[str, Any], logger):
        self.api_client = api_client
        self.folder_info = folder_info
        self.logger = logger
        self.processed_files = set()

        # Import PDF parser if needed
        try:
            from .pdf_parser import PDFInvoiceParser
            self.pdf_parser = PDFInvoiceParser()
        except ImportError:
            self.pdf_parser = None

        # Import data mapping
        try:
            from .pos_data_mapping import map_pos_to_laravel
            self.map_pos_to_laravel = map_pos_to_laravel
        except ImportError:
            self.map_pos_to_laravel = None

    def on_created(self, event):
        """Handle new file creation events"""
        if event.is_directory:
            return

        # Support both JSON and PDF files
        if not (event.src_path.endswith('.json') or event.src_path.endswith('.pdf')):
            return

        # Avoid processing the same file multiple times
        if event.src_path in self.processed_files:
            return

        try:
            filename = os.path.basename(event.src_path)
            folder_path = self.folder_info['path']
            pos_system = self.folder_info.get('pos_system', 'unknown')

            self.logger.info(f"📄 New invoice detected: {filename}")
            self.logger.info(f"📁 Source folder: {folder_path} (POS: {pos_system})")

            # Wait a moment to ensure the file is completely written
            import time
            time.sleep(0.5)

            # Process based on file type
            if event.src_path.endswith('.pdf'):
                # Parse PDF invoice
                self.logger.info(f"🔍 Processing PDF invoice: {filename}")
                if self.pdf_parser:
                    pos_invoice = self.pdf_parser.parse_pdf_invoice(event.src_path, pos_system)
                    if not pos_invoice:
                        self.logger.error(f"❌ Failed to parse PDF {filename}")
                        return
                else:
                    self.logger.error(f"❌ PDF parser not available for {filename}")
                    return
            else:
                # Load JSON invoice data
                import json
                with open(event.src_path, 'r', encoding='utf-8') as f:
                    pos_invoice = json.load(f)

            # Map POS data to Laravel format
            if self.map_pos_to_laravel:
                invoice_data = self.map_pos_to_laravel(pos_invoice)
            else:
                self.logger.error(f"❌ Data mapping not available for {filename}")
                return

            # Create invoice in Laravel system
            self.logger.info(f"🔄 Creating invoice in Laravel system from {filename}...")
            invoice = self.api_client.create_invoice(invoice_data)
            if not invoice or 'id' not in invoice:
                self.logger.error(f"❌ Failed to create invoice from {filename}")
                return

            # Submit invoice to JoFotara
            self.logger.info(f"📤 Submitting invoice {invoice['id']} to JoFotara from {filename}...")
            result = self.api_client.submit_invoice(invoice['id'])
            if not result:
                self.logger.warning(f"⚠️  Failed to submit invoice {invoice['id']} to JoFotara from file: {filename}")
            else:
                self.logger.info(f"✅ Successfully sent transaction from file: {filename}")

            # Download invoice PDF
            output_dir = os.path.join(os.path.dirname(event.src_path), "Processed")
            os.makedirs(output_dir, exist_ok=True)
            pdf_path = os.path.join(output_dir, f"invoice_{invoice['id']}.pdf")

            self.logger.info(f"📥 Downloading invoice PDF to {pdf_path}...")
            if self.api_client.download_invoice_pdf(invoice['id'], pdf_path):
                self.logger.info(f"📄 Successfully processed invoice {invoice['id']} from file: {filename}")
                self.logger.info(f"📁 Processed file moved to: {output_dir}")
            else:
                self.logger.warning(f"⚠️  Failed to download PDF for invoice {invoice['id']} from file: {filename}")

            # Mark as processed
            self.processed_files.add(event.src_path)

        except json.JSONDecodeError:
            self.logger.error(f"❌ Invalid JSON format in {filename}")
        except Exception as e:
            self.logger.error(f"❌ Error processing invoice {filename}: {str(e)}")
            import traceback
            self.logger.debug(traceback.format_exc())
