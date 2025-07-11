#!/usr/bin/env python3
"""
POS Connector API Client - Specifically for POS Connector endpoints
Uses API key authentication instead of vendor email/password
"""

import os
import requests
import json
import time
import logging
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional

class PosApiClient:
    """API client for POS Connector specific endpoints"""

    def __init__(self, base_url: str, api_key: str, customer_id: str = None):
        self.base_url = base_url.rstrip('/')
        self.api_key = api_key
        self.customer_id = customer_id
        self.headers = {
            'X-API-Key': self.api_key,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
        self.max_retries = 3
        self.retry_delay = 2  # seconds

        # Setup logging
        self.logger = logging.getLogger('PosApiClient')

        # Ensure base URL has protocol
        if not self.base_url.startswith(('http://', 'https://')):
            self.base_url = f"http://{self.base_url}"

        self.logger.info(f"POS API Client initialized with base URL: {self.base_url}")

    def test_connection(self) -> bool:
        """Test connection to the POS Connector API"""
        try:
            response = requests.get(
                f"{self.base_url}/api/pos-connector/test",
                headers=self.headers,
                timeout=10
            )

            if response.status_code == 200:
                data = response.json()
                self.logger.info(f"Connection test successful: {data.get('message', 'OK')}")
                return True
            else:
                self.logger.error(f"Connection test failed: {response.status_code} - {response.text}")
                return False

        except requests.exceptions.RequestException as e:
            self.logger.error(f"Connection test error: {e}")
            return False

    def send_transactions(self, transactions: List[Dict[str, Any]]) -> bool:
        """Send transactions to the POS Connector API"""
        try:
            payload = {
                'customer_id': self.customer_id,
                'transactions': transactions
            }

            self.logger.info(f"Sending {len(transactions)} transactions to API")

            response = requests.post(
                f"{self.base_url}/api/pos-connector/transactions",
                headers=self.headers,
                json=payload,
                timeout=30
            )

            if response.status_code == 200:
                result = response.json()
                self.logger.info(f"Transactions sent successfully: {result.get('processed', 0)} processed, {result.get('skipped', 0)} skipped, {result.get('errors', 0)} errors")

                if result.get('errors', 0) > 0:
                    self.logger.warning(f"Transaction errors: {result.get('error_details', [])}")

                return True
            else:
                self.logger.error(f"Failed to send transactions: {response.status_code} - {response.text}")
                return False

        except requests.exceptions.RequestException as e:
            self.logger.error(f"Error sending transactions: {e}")
            return False

    def send_heartbeat(self, status_data: Dict[str, Any]) -> bool:
        """Send heartbeat to track connector status"""
        try:
            response = requests.post(
                f"{self.base_url}/api/pos-connector/heartbeat",
                headers=self.headers,
                json=status_data,
                timeout=10
            )

            if response.status_code == 200:
                result = response.json()
                self.logger.debug("Heartbeat sent successfully")
                return True
            else:
                self.logger.error(f"Failed to send heartbeat: {response.status_code} - {response.text}")
                return False

        except requests.exceptions.RequestException as e:
            self.logger.error(f"Error sending heartbeat: {e}")
            return False

    def get_config(self) -> Optional[Dict[str, Any]]:
        """Get connector configuration from server"""
        try:
            response = requests.get(
                f"{self.base_url}/api/pos-connector/config",
                headers=self.headers,
                timeout=10
            )

            if response.status_code == 200:
                config = response.json()
                self.logger.info("Configuration retrieved successfully")
                return config
            else:
                self.logger.error(f"Failed to get configuration: {response.status_code} - {response.text}")
                return None

        except requests.exceptions.RequestException as e:
            self.logger.error(f"Error getting configuration: {e}")
            return None

    def get_stats(self) -> Optional[Dict[str, Any]]:
        """Get transaction statistics"""
        try:
            response = requests.get(
                f"{self.base_url}/api/pos-connector/stats",
                headers=self.headers,
                timeout=10
            )

            if response.status_code == 200:
                stats = response.json()
                self.logger.info("Statistics retrieved successfully")
                return stats
            else:
                self.logger.error(f"Failed to get statistics: {response.status_code} - {response.text}")
                return None

        except requests.exceptions.RequestException as e:
            self.logger.error(f"Error getting statistics: {e}")
            return None

    def authenticate(self) -> bool:
        """Test authentication with the API key"""
        return self.test_connection()
