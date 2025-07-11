#!/usr/bin/env python3
"""
Dynamic Invoice Folder Detection for POS Systems
Automatically detects common POS invoice export folders
"""

import os
import json
import winreg
import logging
from pathlib import Path
from typing import List, Dict, Any, Optional
import glob
import time
from datetime import datetime, timedelta

class InvoiceFolderDetector:
    """
    Detects common POS invoice export folders automatically
    """

    def __init__(self, logger: Optional[logging.Logger] = None):
        self.logger = logger or logging.getLogger(__name__)

        # Common POS systems and their typical invoice export folders
        self.pos_folder_patterns = {
            'aronium': [
                r'C:\Aronium\Invoices',
                r'C:\Aronium\Reports',
                r'C:\Aronium\Export',
                r'C:\Program Files\Aronium\Invoices',
                r'C:\Program Files (x86)\Aronium\Invoices',
            ],
            'square': [
                r'C:\Square\Exports',
                r'C:\Square\Reports',
                r'C:\Users\{username}\Documents\Square',
                r'C:\ProgramData\Square\Exports',
            ],
            'shopify': [
                r'C:\Shopify\Exports',
                r'C:\Users\{username}\Documents\Shopify',
                r'C:\ProgramData\Shopify\Reports',
            ],
            'quickbooks': [
                r'C:\Users\{username}\Documents\QuickBooks\Exports',
                r'C:\ProgramData\Intuit\QuickBooks\Exports',
                r'C:\QuickBooks\Reports',
            ],
            'sage': [
                r'C:\Sage\Reports',
                r'C:\Sage\Exports',
                r'C:\Program Files\Sage\Reports',
                r'C:\Program Files (x86)\Sage\Reports',
            ],
            'ncr': [
                r'C:\NCR\Aloha\Reports',
                r'C:\NCR\Exports',
                r'C:\Aloha\Reports',
            ],
            'micros': [
                r'C:\Micros\Reports',
                r'C:\Micros\Exports',
                r'C:\Program Files\Micros\Reports',
            ],
            'toast': [
                r'C:\Toast\Exports',
                r'C:\Toast\Reports',
                r'C:\Users\{username}\Documents\Toast',
            ],
            'lightspeed': [
                r'C:\Lightspeed\Exports',
                r'C:\Users\{username}\Documents\Lightspeed',
            ],
            'revel': [
                r'C:\Revel\Exports',
                r'C:\Users\{username}\Documents\Revel',
            ],
            'clover': [
                r'C:\Clover\Exports',
                r'C:\Users\{username}\Documents\Clover',
            ],
            'generic': [
                r'C:\POS\Invoices',
                r'C:\POS\Reports',
                r'C:\POS\Exports',
                r'C:\Retail\Invoices',
                r'C:\Retail\Reports',
                r'C:\Cash\Invoices',
                r'C:\Store\Invoices',
                r'C:\Restaurant\Invoices',
                r'C:\Users\{username}\Documents\POS',
                r'C:\Users\{username}\Documents\Invoices',
                r'C:\Users\{username}\Desktop\Invoices',
                r'C:\Invoices',
                r'C:\Reports',
                r'C:\Exports',
            ]
        }

        # File patterns that indicate invoice/receipt files
        self.invoice_file_patterns = [
            '*.pdf', '*.json', '*.xml', '*.csv', '*.txt',
            '*invoice*', '*receipt*', '*transaction*', '*sale*'
        ]

    def detect_invoice_folders(self, include_empty: bool = False) -> List[Dict[str, Any]]:
        """
        Detect all potential invoice folders on the system

        Args:
            include_empty: Whether to include empty folders

        Returns:
            List of detected folders with metadata
        """
        self.logger.info("Starting automatic invoice folder detection...")
        detected_folders = []

        # Get current username for path substitution
        username = os.getenv('USERNAME', 'User')

        # Check all POS system patterns
        for pos_system, folder_patterns in self.pos_folder_patterns.items():
            for pattern in folder_patterns:
                # Substitute username in path
                folder_path = pattern.replace('{username}', username)

                if os.path.exists(folder_path):
                    folder_info = self._analyze_folder(folder_path, pos_system)
                    if folder_info and (include_empty or folder_info['file_count'] > 0):
                        detected_folders.append(folder_info)

        # Additional discovery methods
        detected_folders.extend(self._discover_by_recent_files())
        detected_folders.extend(self._discover_by_registry())
        detected_folders.extend(self._discover_by_common_locations())

        # Remove duplicates and sort by priority
        unique_folders = self._deduplicate_folders(detected_folders)
        sorted_folders = self._prioritize_folders(unique_folders)

        self.logger.info(f"Detected {len(sorted_folders)} potential invoice folders")
        return sorted_folders

    def _analyze_folder(self, folder_path: str, pos_system: str) -> Optional[Dict[str, Any]]:
        """Analyze a folder to determine if it contains invoice files"""
        try:
            if not os.path.exists(folder_path):
                return None

            # Count files by type
            file_counts = {
                'pdf': 0, 'json': 0, 'xml': 0, 'csv': 0, 'txt': 0, 'other': 0
            }

            total_files = 0
            recent_files = 0
            oldest_file = None
            newest_file = None

            # Analyze files in the folder
            for root, dirs, files in os.walk(folder_path):
                # Limit depth to avoid deep scanning
                if root.count(os.sep) - folder_path.count(os.sep) > 2:
                    continue

                for file in files:
                    file_path = os.path.join(root, file)
                    file_ext = os.path.splitext(file)[1].lower().lstrip('.')

                    # Count by extension
                    if file_ext in file_counts:
                        file_counts[file_ext] += 1
                    else:
                        file_counts['other'] += 1

                    total_files += 1

                    # Check file age
                    try:
                        file_time = os.path.getmtime(file_path)
                        file_date = datetime.fromtimestamp(file_time)

                        if oldest_file is None or file_date < oldest_file:
                            oldest_file = file_date
                        if newest_file is None or file_date > newest_file:
                            newest_file = file_date

                        # Count recent files (last 30 days)
                        if file_date > datetime.now() - timedelta(days=30):
                            recent_files += 1

                    except (OSError, ValueError):
                        continue

            # Calculate folder score based on various factors
            score = self._calculate_folder_score(
                pos_system, total_files, recent_files, file_counts
            )

            return {
                'path': folder_path,
                'pos_system': pos_system,
                'file_count': total_files,
                'recent_files': recent_files,
                'file_types': file_counts,
                'oldest_file': oldest_file.isoformat() if oldest_file else None,
                'newest_file': newest_file.isoformat() if newest_file else None,
                'score': score,
                'discovery_method': 'pattern_match'
            }

        except Exception as e:
            self.logger.debug(f"Error analyzing folder {folder_path}: {e}")
            return None

    def _calculate_folder_score(self, pos_system: str, total_files: int,
                              recent_files: int, file_counts: Dict[str, int]) -> float:
        """Calculate a priority score for the folder"""
        score = 0.0

        # Base score for having files
        if total_files > 0:
            score += 10.0

        # Bonus for recent activity
        score += min(recent_files * 2.0, 20.0)

        # Bonus for invoice-like file types
        score += file_counts.get('pdf', 0) * 3.0
        score += file_counts.get('json', 0) * 2.0
        score += file_counts.get('xml', 0) * 2.0
        score += file_counts.get('csv', 0) * 1.5

        # Bonus for known POS systems (not generic)
        if pos_system != 'generic':
            score += 15.0

        # Penalty for too many files (might be wrong folder)
        if total_files > 1000:
            score -= 10.0

        return score

    def _discover_by_recent_files(self) -> List[Dict[str, Any]]:
        """Discover folders by looking for recently modified invoice-like files"""
        folders = []

        try:
            # Search common locations for recent invoice files
            search_locations = [
                os.path.expanduser("~\\Documents"),
                os.path.expanduser("~\\Desktop"),
                "C:\\",
            ]

            for location in search_locations:
                if not os.path.exists(location):
                    continue

                # Find recent invoice-like files
                for pattern in ['*invoice*', '*receipt*', '*transaction*']:
                    try:
                        for file_path in glob.glob(
                            os.path.join(location, '**', pattern),
                            recursive=True
                        ):
                            if os.path.isfile(file_path):
                                # Check if file is recent (last 7 days)
                                file_time = os.path.getmtime(file_path)
                                if time.time() - file_time < 7 * 24 * 3600:
                                    folder_path = os.path.dirname(file_path)
                                    folder_info = self._analyze_folder(folder_path, 'discovered')
                                    if folder_info:
                                        folder_info['discovery_method'] = 'recent_files'
                                        folders.append(folder_info)
                    except (OSError, ValueError):
                        continue

        except Exception as e:
            self.logger.debug(f"Recent files discovery error: {e}")

        return folders

    def _discover_by_registry(self) -> List[Dict[str, Any]]:
        """Discover folders by checking registry for POS software data paths"""
        folders = []

        try:
            # Registry keys that might contain data paths
            registry_keys = [
                (winreg.HKEY_CURRENT_USER, r"Software"),
                (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE"),
                (winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\WOW6432Node"),
            ]

            pos_keywords = ['pos', 'retail', 'cash', 'invoice', 'receipt', 'aronium']

            for hkey, base_path in registry_keys:
                try:
                    with winreg.OpenKey(hkey, base_path) as key:
                        self._scan_registry_key(key, pos_keywords, folders, depth=0, max_depth=3)
                except Exception:
                    continue

        except Exception as e:
            self.logger.debug(f"Registry discovery error: {e}")

        return folders

    def _scan_registry_key(self, key, keywords: List[str], folders: List[Dict],
                          depth: int, max_depth: int):
        """Recursively scan registry key for POS-related paths"""
        if depth > max_depth:
            return

        try:
            # Check values in current key
            for i in range(winreg.QueryInfoKey(key)[1]):  # Number of values
                try:
                    value_name, value_data, _ = winreg.EnumValue(key, i)
                    if isinstance(value_data, str) and any(kw in value_name.lower() for kw in keywords):
                        if os.path.exists(value_data) and os.path.isdir(value_data):
                            folder_info = self._analyze_folder(value_data, 'registry')
                            if folder_info:
                                folder_info['discovery_method'] = 'registry'
                                folders.append(folder_info)
                except Exception:
                    continue

            # Scan subkeys
            for i in range(winreg.QueryInfoKey(key)[0]):  # Number of subkeys
                try:
                    subkey_name = winreg.EnumKey(key, i)
                    if any(kw in subkey_name.lower() for kw in keywords):
                        with winreg.OpenKey(key, subkey_name) as subkey:
                            self._scan_registry_key(subkey, keywords, folders, depth + 1, max_depth)
                except Exception:
                    continue

        except Exception:
            pass

    def _discover_by_common_locations(self) -> List[Dict[str, Any]]:
        """Discover folders in common business software locations"""
        folders = []

        common_locations = [
            r"C:\Business",
            r"C:\Data",
            r"C:\Export",
            r"C:\Reports",
            os.path.expanduser("~\\Documents\\Business"),
            os.path.expanduser("~\\Documents\\POS"),
            os.path.expanduser("~\\Documents\\Retail"),
        ]

        for location in common_locations:
            if os.path.exists(location):
                folder_info = self._analyze_folder(location, 'common')
                if folder_info and folder_info['file_count'] > 0:
                    folder_info['discovery_method'] = 'common_locations'
                    folders.append(folder_info)

        return folders

    def _deduplicate_folders(self, folders: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Remove duplicate folders"""
        seen_paths = set()
        unique_folders = []

        for folder in folders:
            normalized_path = os.path.normpath(folder['path']).lower()
            if normalized_path not in seen_paths:
                seen_paths.add(normalized_path)
                unique_folders.append(folder)

        return unique_folders

    def _prioritize_folders(self, folders: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Sort folders by priority score"""
        return sorted(folders, key=lambda x: x['score'], reverse=True)

    def get_best_folder(self) -> Optional[str]:
        """Get the best candidate folder for invoice monitoring"""
        folders = self.detect_invoice_folders()

        if folders:
            best_folder = folders[0]
            self.logger.info(f"Best invoice folder candidate: {best_folder['path']} "
                           f"(score: {best_folder['score']:.1f})")
            return best_folder['path']

        return None

    def suggest_folders_interactive(self) -> Optional[str]:
        """Interactive folder selection for setup wizard"""
        folders = self.detect_invoice_folders(include_empty=True)

        if not folders:
            print("❌ No potential invoice folders detected.")
            print("💡 You can manually specify a folder path.")
            return None

        print(f"\n📁 Found {len(folders)} potential invoice folders:")
        print("=" * 60)

        for i, folder in enumerate(folders[:10], 1):  # Show top 10
            print(f"{i:2d}. {folder['path']}")
            print(f"    POS System: {folder['pos_system']}")
            print(f"    Files: {folder['file_count']} total, {folder['recent_files']} recent")
            print(f"    Score: {folder['score']:.1f}")
            print()

        while True:
            try:
                choice = input("Select folder number (1-{}) or 'c' for custom path: ".format(
                    min(len(folders), 10)
                )).strip().lower()

                if choice == 'c':
                    custom_path = input("Enter custom folder path: ").strip()
                    if os.path.exists(custom_path):
                        return custom_path
                    else:
                        print("❌ Path does not exist. Please try again.")
                        continue

                folder_index = int(choice) - 1
                if 0 <= folder_index < min(len(folders), 10):
                    return folders[folder_index]['path']
                else:
                    print("❌ Invalid selection. Please try again.")

            except ValueError:
                print("❌ Invalid input. Please enter a number or 'c'.")
                continue
