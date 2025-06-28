#!/usr/bin/env python3
"""
Data Extractors for different data sources and formats
"""

import os
import json
import sqlite3
import csv
import xml.etree.ElementTree as ET
import pandas as pd
import pyodbc
import pymysql
import psycopg2
import winreg
import win32com.client
import wmi
import psutil
import socket
import requests
from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional, Generator
import logging
import re
import struct
import codecs

class DatabaseScanner:
    """Scans for database files and connections"""

    def __init__(self):
        self.logger = logging.getLogger('DatabaseScanner')

    def scan_for_databases(self, search_paths: List[str] = None) -> List[Dict[str, Any]]:
        """Scan for database files in specified paths"""
        if search_paths is None:
            search_paths = self._get_default_search_paths()

        databases = []

        for path in search_paths:
            if os.path.exists(path):
                databases.extend(self._scan_directory(path))

        return databases

    def _get_default_search_paths(self) -> List[str]:
        """Get default paths to search for databases"""
        return [
            r"C:\POS",
            r"C:\Data",
            r"C:\Program Files",
            r"C:\Program Files (x86)",
            os.path.expanduser("~\\Documents"),
            os.path.expanduser("~\\AppData\\Local"),
            os.path.expanduser("~\\AppData\\Roaming"),
        ]

    def _scan_directory(self, directory: str) -> List[Dict[str, Any]]:
        """Scan a directory for database files"""
        databases = []

        # Database file extensions
        db_extensions = {
            '.db': 'sqlite',
            '.sqlite': 'sqlite',
            '.sqlite3': 'sqlite',
            '.mdb': 'access',
            '.accdb': 'access',
            '.dbf': 'dbase',
            '.fdb': 'firebird',
            '.gdb': 'firebird',
        }

        try:
            for root, dirs, files in os.walk(directory):
                # Limit depth to avoid deep scanning
                if root.count(os.sep) - directory.count(os.sep) > 3:
                    continue

                for file in files:
                    file_lower = file.lower()
                    file_path = os.path.join(root, file)

                    for ext, db_type in db_extensions.items():
                        if file_lower.endswith(ext):
                            db_info = self._analyze_database_file(file_path, db_type)
                            if db_info:
                                databases.append(db_info)
                            break

        except Exception as e:
            self.logger.error(f"Error scanning directory {directory}: {e}")

        return databases

    def _analyze_database_file(self, file_path: str, db_type: str) -> Optional[Dict[str, Any]]:
        """Analyze a database file to determine if it's POS-related"""
        try:
            file_size = os.path.getsize(file_path)
            file_modified = datetime.fromtimestamp(os.path.getmtime(file_path))

            # Skip very small files (likely not real databases)
            if file_size < 1024:
                return None

            # Check if file contains POS-related keywords in name
            filename = os.path.basename(file_path).lower()
            pos_keywords = [
                'pos', 'retail', 'sales', 'transaction', 'cash', 'checkout',
                'inventory', 'customer', 'product', 'invoice', 'receipt'
            ]

            has_pos_keywords = any(keyword in filename for keyword in pos_keywords)

            db_info = {
                'path': file_path,
                'type': db_type,
                'size': file_size,
                'modified': file_modified.isoformat(),
                'has_pos_keywords': has_pos_keywords,
                'tables': []
            }

            # Try to get table information
            if db_type == 'sqlite':
                db_info['tables'] = self._get_sqlite_tables(file_path)
            elif db_type == 'access':
                db_info['tables'] = self._get_access_tables(file_path)

            # Check if tables suggest this is a POS database
            table_names = ' '.join(db_info['tables']).lower()
            has_pos_tables = any(keyword in table_names for keyword in pos_keywords)

            db_info['likely_pos'] = has_pos_keywords or has_pos_tables

            return db_info

        except Exception as e:
            self.logger.debug(f"Error analyzing database {file_path}: {e}")
            return None

    def _get_sqlite_tables(self, db_path: str) -> List[str]:
        """Get table names from SQLite database"""
        tables = []
        try:
            conn = sqlite3.connect(db_path)
            cursor = conn.cursor()
            cursor.execute("SELECT name FROM sqlite_master WHERE type='table'")
            tables = [row[0] for row in cursor.fetchall()]
            conn.close()
        except Exception as e:
            self.logger.debug(f"Error getting SQLite tables from {db_path}: {e}")

        return tables

    def _get_access_tables(self, db_path: str) -> List[str]:
        """Get table names from Access database"""
        tables = []
        try:
            connection_string = f"Driver={{Microsoft Access Driver (*.mdb, *.accdb)}};DBQ={db_path};"
            conn = pyodbc.connect(connection_string)
            cursor = conn.cursor()

            for table_info in cursor.tables(tableType='TABLE'):
                tables.append(table_info.table_name)

            conn.close()
        except Exception as e:
            self.logger.debug(f"Error getting Access tables from {db_path}: {e}")

        return tables

class NetworkScanner:
    """Scans for network services and connections"""

    def __init__(self):
        self.logger = logging.getLogger('NetworkScanner')

    def scan_network_services(self) -> List[Dict[str, Any]]:
        """Scan for network services that might be POS-related"""
        services = []

        # Common POS system ports
        pos_ports = [
            1433,  # SQL Server
            3306,  # MySQL
            5432,  # PostgreSQL
            8080,  # HTTP alternative
            443,   # HTTPS
            80,    # HTTP
            9090,  # Various POS systems
            8443,  # HTTPS alternative
            1521,  # Oracle
            50000, # DB2
        ]

        try:
            for connection in psutil.net_connections(kind='inet'):
                if connection.status == psutil.CONN_LISTEN and connection.laddr:
                    port = connection.laddr.port

                    if port in pos_ports:
                        service_info = self._analyze_network_service(connection)
                        if service_info:
                            services.append(service_info)

        except Exception as e:
            self.logger.error(f"Error scanning network services: {e}")

        return services

    def _analyze_network_service(self, connection) -> Optional[Dict[str, Any]]:
        """Analyze a network connection to determine if it's POS-related"""
        try:
            service_info = {
                'address': connection.laddr.ip,
                'port': connection.laddr.port,
                'status': connection.status,
                'process_name': '',
                'process_id': connection.pid,
                'likely_pos': False
            }

            # Get process information
            if connection.pid:
                try:
                    process = psutil.Process(connection.pid)
                    service_info['process_name'] = process.name()

                    # Check if process name suggests POS system
                    process_name_lower = process.name().lower()
                    pos_keywords = ['pos', 'retail', 'cash', 'sql', 'mysql', 'postgres']
                    service_info['likely_pos'] = any(keyword in process_name_lower for keyword in pos_keywords)

                except (psutil.NoSuchProcess, psutil.AccessDenied):
                    pass

            return service_info

        except Exception as e:
            self.logger.debug(f"Error analyzing network service: {e}")
            return None

    def test_database_connections(self, hosts: List[str] = None) -> List[Dict[str, Any]]:
        """Test database connections on network hosts"""
        if hosts is None:
            hosts = ['localhost', '127.0.0.1']

        connections = []

        # Common database ports and types
        db_tests = [
            (1433, 'mssql', 'SQL Server'),
            (3306, 'mysql', 'MySQL'),
            (5432, 'postgresql', 'PostgreSQL'),
            (1521, 'oracle', 'Oracle'),
        ]

        for host in hosts:
            for port, db_type, description in db_tests:
                if self._test_port(host, port):
                    connections.append({
                        'host': host,
                        'port': port,
                        'type': db_type,
                        'description': description,
                        'connection_string': self._build_connection_string(host, port, db_type)
                    })

        return connections

    def _test_port(self, host: str, port: int, timeout: int = 3) -> bool:
        """Test if a port is open on a host"""
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(timeout)
            result = sock.connect_ex((host, port))
            sock.close()
            return result == 0
        except Exception:
            return False

    def _build_connection_string(self, host: str, port: int, db_type: str) -> str:
        """Build connection string for database type"""
        if db_type == 'mssql':
            return f"DRIVER={{ODBC Driver 17 for SQL Server}};SERVER={host},{port};Trusted_Connection=yes;"
        elif db_type == 'mysql':
            return f"host={host},port={port},user=root,database=mysql"
        elif db_type == 'postgresql':
            return f"host={host} port={port} dbname=postgres"
        elif db_type == 'oracle':
            return f"{host}:{port}/xe"

        return f"{host}:{port}"

class RegistryScanner:
    """Scans Windows registry for POS software installations"""

    def __init__(self):
        self.logger = logging.getLogger('RegistryScanner')

    def scan_installed_software(self) -> List[Dict[str, Any]]:
        """Scan registry for installed POS software"""
        software = []

        # Registry locations to check
        registry_locations = [
            (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall"),
            (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall"),
            (winreg.HKEY_CURRENT_USER, r"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall"),
        ]

        pos_keywords = [
            'pos', 'point of sale', 'retail', 'cash register', 'checkout',
            'square', 'shopify', 'quickbooks', 'sage', 'dynamics',
            'ncr', 'micros', 'aloha', 'toast', 'revel', 'lightspeed',
            'vend', 'shopkeep', 'clover', 'talech', 'loyverse'
        ]

        for hkey, subkey_path in registry_locations:
            try:
                with winreg.OpenKey(hkey, subkey_path) as key:
                    for i in range(winreg.QueryInfoKey(key)[0]):
                        try:
                            subkey_name = winreg.EnumKey(key, i)
                            software_info = self._analyze_registry_entry(hkey, subkey_path, subkey_name, pos_keywords)
                            if software_info:
                                software.append(software_info)
                        except Exception:
                            continue
            except Exception as e:
                self.logger.debug(f"Error scanning registry location {subkey_path}: {e}")

        return software

    def _analyze_registry_entry(self, hkey, subkey_path: str, subkey_name: str, pos_keywords: List[str]) -> Optional[Dict[str, Any]]:
        """Analyze a registry entry for POS software"""
        try:
            with winreg.OpenKey(hkey, f"{subkey_path}\\{subkey_name}") as subkey:
                software_info = {}

                # Get common values
                try:
                    software_info['display_name'] = winreg.QueryValueEx(subkey, "DisplayName")[0]
                except FileNotFoundError:
                    return None

                try:
                    software_info['install_location'] = winreg.QueryValueEx(subkey, "InstallLocation")[0]
                except FileNotFoundError:
                    try:
                        uninstall_string = winreg.QueryValueEx(subkey, "UninstallString")[0]
                        software_info['install_location'] = os.path.dirname(uninstall_string)
                    except FileNotFoundError:
                        software_info['install_location'] = ""

                try:
                    software_info['publisher'] = winreg.QueryValueEx(subkey, "Publisher")[0]
                except FileNotFoundError:
                    software_info['publisher'] = ""

                try:
                    software_info['version'] = winreg.QueryValueEx(subkey, "DisplayVersion")[0]
                except FileNotFoundError:
                    software_info['version'] = ""

                # Check if this looks like POS software
                display_name_lower = software_info['display_name'].lower()
                publisher_lower = software_info['publisher'].lower()

                is_pos_software = any(
                    keyword in display_name_lower or keyword in publisher_lower
                    for keyword in pos_keywords
                )

                if is_pos_software:
                    software_info['registry_key'] = f"{subkey_path}\\{subkey_name}"
                    software_info['likely_pos'] = True
                    return software_info

        except Exception as e:
            self.logger.debug(f"Error analyzing registry entry {subkey_name}: {e}")

        return None

    def scan_com_objects(self) -> List[Dict[str, Any]]:
        """Scan for COM objects that might be POS-related"""
        com_objects = []

        try:
            # Scan HKEY_CLASSES_ROOT for COM objects
            with winreg.OpenKey(winreg.HKEY_CLASSES_ROOT, "") as key:
                for i in range(winreg.QueryInfoKey(key)[0]):
                    try:
                        class_name = winreg.EnumKey(key, i)

                        # Look for POS-related class names
                        if any(keyword in class_name.lower() for keyword in ['pos', 'retail', 'cash', 'sale']):
                            com_info = self._analyze_com_object(class_name)
                            if com_info:
                                com_objects.append(com_info)
                    except Exception:
                        continue

        except Exception as e:
            self.logger.error(f"Error scanning COM objects: {e}")

        return com_objects

    def _analyze_com_object(self, class_name: str) -> Optional[Dict[str, Any]]:
        """Analyze a COM object"""
        try:
            com_info = {
                'class_name': class_name,
                'description': '',
                'clsid': '',
                'dll_path': ''
            }

            # Try to get more information about the COM object
            try:
                with winreg.OpenKey(winreg.HKEY_CLASSES_ROOT, class_name) as key:
                    try:
                        com_info['description'] = winreg.QueryValueEx(key, "")[0]
                    except FileNotFoundError:
                        pass

                    # Look for CLSID
                    try:
                        with winreg.OpenKey(key, "CLSID") as clsid_key:
                            com_info['clsid'] = winreg.QueryValueEx(clsid_key, "")[0]
                    except FileNotFoundError:
                        pass
            except Exception:
                pass

            return com_info

        except Exception as e:
            self.logger.debug(f"Error analyzing COM object {class_name}: {e}")
            return None

class FileSystemScanner:
    """Scans file system for POS-related files and data"""

    def __init__(self):
        self.logger = logging.getLogger('FileSystemScanner')

    def scan_for_pos_files(self, search_paths: List[str] = None) -> List[Dict[str, Any]]:
        """Scan for POS-related files"""
        if search_paths is None:
            search_paths = self._get_default_search_paths()

        pos_files = []

        # File patterns to look for
        file_patterns = [
            ('*.json', 'JSON data file'),
            ('*.xml', 'XML data file'),
            ('*.csv', 'CSV data file'),
            ('*.txt', 'Text data file'),
            ('*.log', 'Log file'),
            ('*.dat', 'Data file'),
            ('*.dbf', 'dBase file'),
            ('transactions.*', 'Transaction data'),
            ('sales.*', 'Sales data'),
            ('receipts.*', 'Receipt data'),
        ]

        for path in search_paths:
            if os.path.exists(path):
                pos_files.extend(self._scan_directory_for_patterns(path, file_patterns))

        return pos_files

    def _get_default_search_paths(self) -> List[str]:
        """Get default paths to search for POS files"""
        return [
            r"C:\POS",
            r"C:\Data",
            r"C:\Export",
            r"C:\Reports",
            os.path.expanduser("~\\Documents"),
            os.path.expanduser("~\\Desktop"),
        ]

    def _scan_directory_for_patterns(self, directory: str, patterns: List[tuple]) -> List[Dict[str, Any]]:
        """Scan directory for files matching patterns"""
        files = []

        try:
            for root, dirs, filenames in os.walk(directory):
                # Limit depth
                if root.count(os.sep) - directory.count(os.sep) > 2:
                    continue

                for filename in filenames:
                    for pattern, description in patterns:
                        if self._matches_pattern(filename.lower(), pattern.lower()):
                            file_path = os.path.join(root, filename)
                            file_info = self._analyze_file(file_path, description)
                            if file_info:
                                files.append(file_info)
                            break

        except Exception as e:
            self.logger.error(f"Error scanning directory {directory}: {e}")

        return files

    def _matches_pattern(self, filename: str, pattern: str) -> bool:
        """Check if filename matches pattern"""
        import fnmatch
        return fnmatch.fnmatch(filename, pattern)

    def _analyze_file(self, file_path: str, description: str) -> Optional[Dict[str, Any]]:
        """Analyze a file to determine if it's POS-related"""
        try:
            file_size = os.path.getsize(file_path)
            file_modified = datetime.fromtimestamp(os.path.getmtime(file_path))

            # Skip very small files
            if file_size < 100:
                return None

            file_info = {
                'path': file_path,
                'size': file_size,
                'modified': file_modified.isoformat(),
                'description': description,
                'type': os.path.splitext(file_path)[1].lower(),
                'likely_pos': self._is_likely_pos_file(file_path)
            }

            return file_info

        except Exception as e:
            self.logger.debug(f"Error analyzing file {file_path}: {e}")
            return None

    def _is_likely_pos_file(self, file_path: str) -> bool:
        """Determine if a file is likely POS-related"""
        filename = os.path.basename(file_path).lower()

        pos_keywords = [
            'pos', 'retail', 'sales', 'transaction', 'cash', 'checkout',
            'receipt', 'invoice', 'customer', 'product', 'inventory'
        ]

        # Check filename
        if any(keyword in filename for keyword in pos_keywords):
            return True

        # Check file content for certain file types
        if filename.endswith(('.json', '.xml', '.csv')):
            return self._check_file_content(file_path, pos_keywords)

        return False

    def _check_file_content(self, file_path: str, keywords: List[str]) -> bool:
        """Check file content for POS-related keywords"""
        try:
            # Read first few lines/kb to check content
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read(1024).lower()  # Read first 1KB
                return any(keyword in content for keyword in keywords)
        except Exception:
            return False
