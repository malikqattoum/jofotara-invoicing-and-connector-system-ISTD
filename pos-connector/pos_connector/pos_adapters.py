#!/usr/bin/env python3
"""
POS Adapters for different POS systems
Each adapter handles the specific connection and data extraction for a particular POS system type
"""

import os
import json
import sqlite3
import pyodbc
import pymysql
import psycopg2
# import cx_Oracle
import pandas as pd
import xml.etree.ElementTree as ET
import csv
import requests
import time
import winreg
import win32com.client
import wmi
from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional
from abc import ABC, abstractmethod
import logging

class BasePOSAdapter(ABC):
    """Base class for all POS adapters"""

    def __init__(self):
        self.config = {}
        self.connection = None
        self.logger = logging.getLogger(self.__class__.__name__)

    @abstractmethod
    def configure(self, system_config: Dict[str, Any]):
        """Configure the adapter with system-specific settings"""
        pass

    @abstractmethod
    def test_connection(self) -> bool:
        """Test if the connection to the POS system is working"""
        pass

    @abstractmethod
    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        """Get new transactions since the specified datetime"""
        pass

    def close_connection(self):
        """Close the connection to the POS system"""
        if self.connection:
            try:
                self.connection.close()
            except:
                pass
            self.connection = None

class SquarePOSAdapter(BasePOSAdapter):
    """Adapter for Square POS systems"""

    def configure(self, system_config: Dict[str, Any]):
        self.config = system_config
        # Square typically uses API access
        self.api_token = system_config.get('api_token', '')
        self.application_id = system_config.get('application_id', '')
        self.base_url = "https://connect.squareup.com/v2"

    def test_connection(self) -> bool:
        try:
            headers = {
                'Authorization': f'Bearer {self.api_token}',
                'Square-Version': '2023-10-18'
            }
            response = requests.get(f"{self.base_url}/locations", headers=headers, timeout=10)
            return response.status_code == 200
        except:
            return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        transactions = []
        try:
            headers = {
                'Authorization': f'Bearer {self.api_token}',
                'Square-Version': '2023-10-18'
            }

            # Get payments since the specified date
            params = {
                'begin_time': since.isoformat() + 'Z',
                'sort_order': 'ASC'
            }

            response = requests.get(f"{self.base_url}/payments", headers=headers, params=params, timeout=30)
            if response.status_code == 200:
                data = response.json()
                for payment in data.get('payments', []):
                    transactions.append(self._convert_square_payment(payment))
        except Exception as e:
            self.logger.error(f"Error getting Square transactions: {e}")

        return transactions

    def _convert_square_payment(self, payment: Dict[str, Any]) -> Dict[str, Any]:
        """Convert Square payment to standard transaction format"""
        return {
            'id': payment.get('id'),
            'date': payment.get('created_at'),
            'total_amount': float(payment.get('amount_money', {}).get('amount', 0)) / 100,
            'currency': payment.get('amount_money', {}).get('currency', 'USD'),
            'customer_name': 'Walk-in Customer',  # Square may not always have customer info
            'customer_email': '',
            'status': payment.get('status'),
            'items': []  # Would need additional API calls to get line items
        }

class ShopifyPOSAdapter(BasePOSAdapter):
    """Adapter for Shopify POS systems"""

    def configure(self, system_config: Dict[str, Any]):
        self.config = system_config
        self.shop_domain = system_config.get('shop_domain', '')
        self.access_token = system_config.get('access_token', '')
        self.base_url = f"https://{self.shop_domain}.myshopify.com/admin/api/2023-10"

    def test_connection(self) -> bool:
        try:
            headers = {'X-Shopify-Access-Token': self.access_token}
            response = requests.get(f"{self.base_url}/shop.json", headers=headers, timeout=10)
            return response.status_code == 200
        except:
            return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        transactions = []
        try:
            headers = {'X-Shopify-Access-Token': self.access_token}
            params = {
                'created_at_min': since.isoformat(),
                'status': 'any',
                'limit': 250
            }

            response = requests.get(f"{self.base_url}/orders.json", headers=headers, params=params, timeout=30)
            if response.status_code == 200:
                data = response.json()
                for order in data.get('orders', []):
                    transactions.append(self._convert_shopify_order(order))
        except Exception as e:
            self.logger.error(f"Error getting Shopify transactions: {e}")

        return transactions

    def _convert_shopify_order(self, order: Dict[str, Any]) -> Dict[str, Any]:
        """Convert Shopify order to standard transaction format"""
        items = []
        for line_item in order.get('line_items', []):
            items.append({
                'description': line_item.get('title', ''),
                'quantity': line_item.get('quantity', 1),
                'unit_price': float(line_item.get('price', 0)),
                'total_price': float(line_item.get('price', 0)) * line_item.get('quantity', 1)
            })

        customer = order.get('customer', {})

        return {
            'id': order.get('id'),
            'date': order.get('created_at'),
            'total_amount': float(order.get('total_price', 0)),
            'currency': order.get('currency', 'USD'),
            'customer_name': f"{customer.get('first_name', '')} {customer.get('last_name', '')}".strip() or 'Walk-in Customer',
            'customer_email': customer.get('email', ''),
            'status': order.get('financial_status'),
            'items': items
        }

class QuickBooksPOSAdapter(BasePOSAdapter):
    """Adapter for QuickBooks POS systems"""

    def configure(self, system_config: Dict[str, Any]):
        self.config = system_config
        self.db_path = system_config.get('database_path', '')

        # QuickBooks often uses proprietary database formats or ODBC connections
        # This is a simplified version - actual implementation would depend on QB version

    def test_connection(self) -> bool:
        try:
            if self.db_path and os.path.exists(self.db_path):
                return True

            # Try ODBC connection
            try:
                connection_string = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};DBQ=" + self.db_path
                conn = pyodbc.connect(connection_string)
                conn.close()
                return True
            except:
                pass

            return False
        except:
            return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        transactions = []
        # Implementation would depend on QuickBooks POS version and database structure
        # This is a placeholder implementation
        return transactions

class GenericSQLAdapter(BasePOSAdapter):
    """Generic SQL database adapter for various POS systems"""

    def configure(self, system_config: Dict[str, Any]):
        self.config = system_config
        self.db_type = system_config.get('database_type', 'sqlite')
        self.connection_string = system_config.get('connection_string', '')
        self.tables = system_config.get('tables', {})
        self.queries = system_config.get('queries', {})

    def test_connection(self) -> bool:
        try:
            conn = self._get_connection()
            if conn:
                conn.close()
                return True
            return False
        except:
            return False

    def _get_connection(self):
        """Get database connection based on type"""
        try:
            if self.db_type == 'sqlite':
                return sqlite3.connect(self.connection_string)
            elif self.db_type == 'mysql':
                # Parse MySQL connection string
                return pymysql.connect(host='localhost', user='root', password='', database='pos')
            elif self.db_type == 'postgresql':
                return psycopg2.connect(self.connection_string)
            elif self.db_type == 'mssql':
                return pyodbc.connect(self.connection_string)
            # elif self.db_type == 'oracle':
            #     return cx_Oracle.connect(self.connection_string)
            else:
                return pyodbc.connect(self.connection_string)  # Generic ODBC
        except Exception as e:
            self.logger.error(f"Database connection failed: {e}")
            return None

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        transactions = []
        try:
            conn = self._get_connection()
            if not conn:
                return transactions

            cursor = conn.cursor()

            # Use custom query if provided, otherwise try common patterns
            query = self.queries.get('transactions', self._get_default_transaction_query())

            # Execute query with since parameter
            if 'sqlite' in self.db_type:
                cursor.execute(query, (since.isoformat(),))
            else:
                cursor.execute(query, (since,))

            columns = [desc[0] for desc in cursor.description]

            for row in cursor.fetchall():
                transaction_data = dict(zip(columns, row))
                transactions.append(self._normalize_transaction_data(transaction_data))

            conn.close()

        except Exception as e:
            self.logger.error(f"Error getting SQL transactions: {e}")

        return transactions

    def _get_default_transaction_query(self) -> str:
        """Get default transaction query based on common table patterns"""
        # Try common table and column names
        common_queries = [
            "SELECT * FROM transactions WHERE date_created > ? ORDER BY date_created",
            "SELECT * FROM sales WHERE sale_date > ? ORDER BY sale_date",
            "SELECT * FROM receipts WHERE receipt_date > ? ORDER BY receipt_date",
            "SELECT * FROM orders WHERE order_date > ? ORDER BY order_date",
        ]

        # Return the first query that works
        return common_queries[0]  # Default fallback

    def _normalize_transaction_data(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """Normalize transaction data to standard format"""
        # Map common field names to standard format
        field_mappings = {
            'id': ['id', 'transaction_id', 'sale_id', 'receipt_id', 'order_id'],
            'date': ['date', 'date_created', 'sale_date', 'receipt_date', 'order_date', 'timestamp'],
            'total_amount': ['total', 'total_amount', 'amount', 'grand_total', 'final_total'],
            'customer_name': ['customer_name', 'customer', 'client_name', 'buyer_name'],
            'customer_email': ['customer_email', 'email', 'customer_mail'],
        }

        normalized = {}

        for standard_field, possible_fields in field_mappings.items():
            for field in possible_fields:
                if field in data and data[field] is not None:
                    normalized[standard_field] = data[field]
                    break

        # Set defaults for missing fields
        normalized.setdefault('id', str(time.time()))
        normalized.setdefault('date', datetime.now().isoformat())
        normalized.setdefault('total_amount', 0)
        normalized.setdefault('customer_name', 'Walk-in Customer')
        normalized.setdefault('customer_email', '')
        normalized.setdefault('currency', 'SAR')
        normalized.setdefault('items', [])

        return normalized

class GenericFileAdapter(BasePOSAdapter):
    """Generic file-based adapter for CSV, JSON, XML files"""

    def configure(self, system_config: Dict[str, Any]):
        self.config = system_config
        self.file_path = system_config.get('file_path', '')
        self.file_type = system_config.get('file_type', '.csv')
        self.watch_directory = system_config.get('watch_directory', os.path.dirname(self.file_path))

    def test_connection(self) -> bool:
        return os.path.exists(self.watch_directory)

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        transactions = []

        try:
            # Check for new files in the watch directory
            for filename in os.listdir(self.watch_directory):
                file_path = os.path.join(self.watch_directory, filename)

                # Skip if not a file or if it's been modified before 'since'
                if not os.path.isfile(file_path):
                    continue

                file_modified = datetime.fromtimestamp(os.path.getmtime(file_path))
                if file_modified <= since:
                    continue

                # Process file based on type
                if filename.lower().endswith('.csv'):
                    transactions.extend(self._process_csv_file(file_path))
                elif filename.lower().endswith('.json'):
                    transactions.extend(self._process_json_file(file_path))
                elif filename.lower().endswith('.xml'):
                    transactions.extend(self._process_xml_file(file_path))
                elif filename.lower().endswith(('.xls', '.xlsx')):
                    transactions.extend(self._process_excel_file(file_path))

        except Exception as e:
            self.logger.error(f"Error processing files: {e}")

        return transactions

    def _process_csv_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Process CSV file"""
        transactions = []
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for row in reader:
                    transactions.append(self._normalize_file_data(row))
        except Exception as e:
            self.logger.error(f"Error processing CSV file {file_path}: {e}")

        return transactions

    def _process_json_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Process JSON file"""
        transactions = []
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                data = json.load(f)

                if isinstance(data, list):
                    for item in data:
                        transactions.append(self._normalize_file_data(item))
                elif isinstance(data, dict):
                    transactions.append(self._normalize_file_data(data))

        except Exception as e:
            self.logger.error(f"Error processing JSON file {file_path}: {e}")

        return transactions

    def _process_xml_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Process XML file"""
        transactions = []
        try:
            tree = ET.parse(file_path)
            root = tree.getroot()

            # Try to find transaction elements
            for element in root.findall('.//transaction') or root.findall('.//sale') or root.findall('.//order'):
                data = {}
                for child in element:
                    data[child.tag] = child.text
                transactions.append(self._normalize_file_data(data))

        except Exception as e:
            self.logger.error(f"Error processing XML file {file_path}: {e}")

        return transactions

    def _process_excel_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Process Excel file"""
        transactions = []
        try:
            df = pd.read_excel(file_path)
            for _, row in df.iterrows():
                transactions.append(self._normalize_file_data(row.to_dict()))

        except Exception as e:
            self.logger.error(f"Error processing Excel file {file_path}: {e}")

        return transactions

    def _normalize_file_data(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """Normalize file data to standard transaction format"""
        # Similar to GenericSQLAdapter._normalize_transaction_data
        field_mappings = {
            'id': ['id', 'transaction_id', 'sale_id', 'receipt_id', 'order_id', 'ref', 'reference'],
            'date': ['date', 'timestamp', 'created_at', 'sale_date', 'order_date'],
            'total_amount': ['total', 'amount', 'total_amount', 'grand_total', 'sum'],
            'customer_name': ['customer', 'customer_name', 'client', 'buyer'],
            'customer_email': ['email', 'customer_email', 'customer_mail'],
        }

        normalized = {}

        for standard_field, possible_fields in field_mappings.items():
            for field in possible_fields:
                # Check both exact match and case-insensitive match
                if field in data and data[field] is not None:
                    normalized[standard_field] = data[field]
                    break

                # Case-insensitive search
                for key in data.keys():
                    if key.lower() == field.lower() and data[key] is not None:
                        normalized[standard_field] = data[key]
                        break

                if standard_field in normalized:
                    break

        # Set defaults
        normalized.setdefault('id', str(time.time()))
        normalized.setdefault('date', datetime.now().isoformat())
        normalized.setdefault('total_amount', 0)
        normalized.setdefault('customer_name', 'Walk-in Customer')
        normalized.setdefault('customer_email', '')
        normalized.setdefault('currency', 'SAR')
        normalized.setdefault('items', [])

        return normalized

class GenericAPIAdapter(BasePOSAdapter):
    """Generic REST API adapter"""

    def configure(self, system_config: Dict[str, Any]):
        self.config = system_config
        self.base_url = system_config.get('api_url', '')
        self.api_key = system_config.get('api_key', '')
        self.auth_type = system_config.get('auth_type', 'bearer')
        self.endpoints = system_config.get('endpoints', {})

    def test_connection(self) -> bool:
        try:
            headers = self._get_auth_headers()
            test_endpoint = self.endpoints.get('test', '/health')
            response = requests.get(f"{self.base_url}{test_endpoint}", headers=headers, timeout=10)
            return response.status_code in [200, 401]  # 401 means endpoint exists but auth failed
        except:
            return False

    def _get_auth_headers(self) -> Dict[str, str]:
        """Get authentication headers based on auth type"""
        headers = {'Content-Type': 'application/json'}

        if self.auth_type == 'bearer':
            headers['Authorization'] = f'Bearer {self.api_key}'
        elif self.auth_type == 'api_key':
            headers['X-API-Key'] = self.api_key
        elif self.auth_type == 'basic':
            import base64
            credentials = base64.b64encode(self.api_key.encode()).decode()
            headers['Authorization'] = f'Basic {credentials}'

        return headers

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        transactions = []
        try:
            headers = self._get_auth_headers()
            endpoint = self.endpoints.get('transactions', '/transactions')

            params = {
                'since': since.isoformat(),
                'limit': 1000
            }

            response = requests.get(f"{self.base_url}{endpoint}", headers=headers, params=params, timeout=30)

            if response.status_code == 200:
                data = response.json()

                # Handle different response formats
                if isinstance(data, list):
                    transactions = data
                elif isinstance(data, dict):
                    # Look for common response wrappers
                    transactions = data.get('data', data.get('transactions', data.get('results', [data])))

                # Normalize each transaction
                transactions = [self._normalize_api_data(t) for t in transactions]

        except Exception as e:
            self.logger.error(f"Error getting API transactions: {e}")

        return transactions

    def _normalize_api_data(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """Normalize API response data"""
        # Similar normalization as other adapters
        return data  # Simplified for now

# Additional specific adapters
class SQLiteAdapter(GenericSQLAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['database_type'] = 'sqlite'
        super().configure(system_config)

class MySQLAdapter(GenericSQLAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['database_type'] = 'mysql'
        super().configure(system_config)

class MSSQLAdapter(GenericSQLAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['database_type'] = 'mssql'
        super().configure(system_config)

class PostgreSQLAdapter(GenericSQLAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['database_type'] = 'postgresql'
        super().configure(system_config)

class OracleAdapter(GenericSQLAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['database_type'] = 'oracle'
        super().configure(system_config)

class AccessAdapter(GenericSQLAdapter):
    def configure(self, system_config: Dict[str, Any]):
        db_path = system_config.get('database_path', '')
        connection_string = f"Driver={{Microsoft Access Driver (*.mdb, *.accdb)}};DBQ={db_path};"
        system_config['database_type'] = 'access'
        system_config['connection_string'] = connection_string
        super().configure(system_config)

class CSVAdapter(GenericFileAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['file_type'] = '.csv'
        super().configure(system_config)

class JSONAdapter(GenericFileAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['file_type'] = '.json'
        super().configure(system_config)

class XMLAdapter(GenericFileAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['file_type'] = '.xml'
        super().configure(system_config)

class ExcelAdapter(GenericFileAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['file_type'] = '.xlsx'
        super().configure(system_config)

# Placeholder adapters for other systems
class SagePOSAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

class DynamicsPOSAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

# Additional utility adapters
class ODBCAdapter(GenericSQLAdapter):
    def configure(self, system_config: Dict[str, Any]):
        system_config['database_type'] = 'odbc'
        super().configure(system_config)

class FoxProAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

class DBaseAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

class FirebirdAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

class RegistryAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

class NetworkAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

class ServiceAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

class COMAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []

class WMIAdapter(BasePOSAdapter):
    def configure(self, system_config: Dict[str, Any]):
        pass

    def test_connection(self) -> bool:
        return False

    def get_new_transactions(self, since: datetime) -> List[Dict[str, Any]]:
        return []
