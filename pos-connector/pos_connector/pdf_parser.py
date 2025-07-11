#!/usr/bin/env python3
"""
PDF Parser for POS invoices
Extracts transaction data from PDF invoices exported by various POS systems
"""

import re
import logging
from datetime import datetime
from typing import Dict, Any, List, Optional
from pathlib import Path

try:
    import pdfplumber
    PDF_AVAILABLE = True
except ImportError:
    PDF_AVAILABLE = False
    pdfplumber = None

class PDFInvoiceParser:
    """Parser for PDF invoices from various POS systems"""

    def __init__(self):
        self.logger = logging.getLogger(__name__)
        if not PDF_AVAILABLE:
            self.logger.warning("PDF processing libraries not available. Install pdfplumber to enable PDF parsing.")

    def parse_pdf_invoice(self, pdf_path: str, pos_system: str = "Unknown") -> Optional[Dict[str, Any]]:
        """Parse a PDF invoice and extract transaction data"""
        if not PDF_AVAILABLE:
            self.logger.error("PDF processing not available. Please install required libraries.")
            return None

        try:
            with pdfplumber.open(pdf_path) as pdf:
                # Extract text from all pages
                full_text = ""
                for page in pdf.pages:
                    full_text += page.extract_text() + "\n"

                # Determine POS system type and parse accordingly
                if "aronium" in pos_system.lower() or self._is_aronium_pdf(full_text):
                    return self._parse_aronium_pdf(full_text, pdf_path)
                else:
                    # Try generic parsing
                    return self._parse_generic_pdf(full_text, pdf_path, pos_system)

        except Exception as e:
            self.logger.error(f"Error parsing PDF {pdf_path}: {e}")
            return None

    def _is_aronium_pdf(self, text: str) -> bool:
        """Check if PDF is from Aronium POS"""
        aronium_indicators = [
            "aronium",
            "pos system",
            # Add more Aronium-specific indicators as needed
        ]

        text_lower = text.lower()
        return any(indicator in text_lower for indicator in aronium_indicators)

    def _parse_aronium_pdf(self, text: str, pdf_path: str) -> Dict[str, Any]:
        """Parse Aronium POS PDF invoice"""
        self.logger.info(f"Parsing Aronium PDF: {pdf_path}")

        transaction = {
            'transaction_id': self._extract_transaction_id(text, pdf_path),
            'pos_system': 'Aronium POS',
            'source_file': pdf_path,
            'transaction_date': self._extract_date(text),
            'customer_name': self._extract_customer_name(text),
            'customer_email': self._extract_customer_email(text),
            'customer_phone': self._extract_customer_phone(text),
            'items': self._extract_items(text),
            'subtotal': self._extract_subtotal(text),
            'tax_amount': self._extract_tax_amount(text),
            'total_amount': self._extract_total_amount(text),
            'payment_method': self._extract_payment_method(text),
            'notes': f"Imported from Aronium PDF: {Path(pdf_path).name}"
        }

        return transaction

    def _parse_generic_pdf(self, text: str, pdf_path: str, pos_system: str) -> Dict[str, Any]:
        """Parse generic PDF invoice"""
        self.logger.info(f"Parsing generic PDF: {pdf_path}")

        transaction = {
            'transaction_id': self._extract_transaction_id(text, pdf_path),
            'pos_system': pos_system,
            'source_file': pdf_path,
            'transaction_date': self._extract_date(text),
            'customer_name': self._extract_customer_name(text),
            'customer_email': self._extract_customer_email(text),
            'customer_phone': self._extract_customer_phone(text),
            'items': self._extract_items(text),
            'subtotal': self._extract_subtotal(text),
            'tax_amount': self._extract_tax_amount(text),
            'total_amount': self._extract_total_amount(text),
            'payment_method': self._extract_payment_method(text),
            'notes': f"Imported from PDF: {Path(pdf_path).name}"
        }

        return transaction

    def _extract_transaction_id(self, text: str, pdf_path: str) -> str:
        """Extract transaction/invoice ID"""
        # Common patterns for invoice/receipt numbers
        patterns = [
            r'invoice\s*#?\s*:?\s*(\w+)',
            r'receipt\s*#?\s*:?\s*(\w+)',
            r'transaction\s*#?\s*:?\s*(\w+)',
            r'order\s*#?\s*:?\s*(\w+)',
            r'bill\s*#?\s*:?\s*(\w+)',
            r'#\s*(\d+)',
            r'no\.?\s*(\d+)',
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1)

        # Fallback: use filename + timestamp
        filename = Path(pdf_path).stem
        timestamp = int(datetime.now().timestamp())
        return f"{filename}_{timestamp}"

    def _extract_date(self, text: str) -> str:
        """Extract transaction date"""
        # Common date patterns
        patterns = [
            r'date\s*:?\s*(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})',
            r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})',
            r'(\d{4}-\d{2}-\d{2})',
            r'(\d{2}-\d{2}-\d{4})',
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                date_str = match.group(1)
                try:
                    # Try to parse and standardize the date
                    if '/' in date_str:
                        parts = date_str.split('/')
                    elif '-' in date_str:
                        parts = date_str.split('-')
                    else:
                        continue

                    if len(parts) == 3:
                        # Assume MM/DD/YYYY or DD/MM/YYYY format
                        if len(parts[2]) == 4:  # Year is last
                            if int(parts[0]) > 12:  # DD/MM/YYYY
                                day, month, year = parts
                            else:  # MM/DD/YYYY
                                month, day, year = parts
                        else:  # Year is first (YYYY-MM-DD)
                            year, month, day = parts

                        return f"{year}-{month.zfill(2)}-{day.zfill(2)}"
                except:
                    continue

        # Fallback to current date
        return datetime.now().strftime('%Y-%m-%d')

    def _extract_customer_name(self, text: str) -> str:
        """Extract customer name"""
        patterns = [
            r'customer\s*:?\s*([^\n\r]+)',
            r'bill\s*to\s*:?\s*([^\n\r]+)',
            r'sold\s*to\s*:?\s*([^\n\r]+)',
            r'name\s*:?\s*([^\n\r]+)',
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                name = match.group(1).strip()
                if name and len(name) > 2:
                    return name

        return "Walk-in Customer"

    def _extract_customer_email(self, text: str) -> str:
        """Extract customer email"""
        pattern = r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b'
        match = re.search(pattern, text)
        return match.group(0) if match else ""

    def _extract_customer_phone(self, text: str) -> str:
        """Extract customer phone"""
        patterns = [
            r'phone\s*:?\s*([\d\s\-\+\(\)]+)',
            r'tel\s*:?\s*([\d\s\-\+\(\)]+)',
            r'mobile\s*:?\s*([\d\s\-\+\(\)]+)',
            r'\b(\+?\d{1,3}[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{4})\b',
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                phone = re.sub(r'[^\d\+]', '', match.group(1))
                if len(phone) >= 10:
                    return phone

        return ""

    def _extract_items(self, text: str) -> List[Dict[str, Any]]:
        """Extract line items from invoice"""
        items = []

        # Look for table-like structures with items
        lines = text.split('\n')

        # Common item patterns
        item_patterns = [
            # Quantity Description Price Total
            r'(\d+(?:\.\d+)?)\s+(.+?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)$',
            # Description Qty Price Total
            r'(.+?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)$',
        ]

        for line in lines:
            line = line.strip()
            if not line or len(line) < 10:
                continue

            # Skip header lines
            if any(word in line.lower() for word in ['description', 'item', 'qty', 'quantity', 'price', 'total', 'amount']):
                continue

            # Skip total lines
            if any(word in line.lower() for word in ['subtotal', 'tax', 'total', 'discount', 'payment']):
                continue

            for pattern in item_patterns:
                match = re.search(pattern, line)
                if match:
                    groups = match.groups()

                    if len(groups) == 4:
                        # Try to determine which format it is
                        try:
                            # Check if first group is numeric (quantity first)
                            qty = float(groups[0])
                            desc = groups[1].strip()
                            price = float(groups[2])
                            total = float(groups[3])
                        except ValueError:
                            try:
                                # Description first format
                                desc = groups[0].strip()
                                qty = float(groups[1])
                                price = float(groups[2])
                                total = float(groups[3])
                            except ValueError:
                                continue

                        if desc and qty > 0:
                            items.append({
                                'description': desc,
                                'quantity': qty,
                                'unit_price': price,
                                'total_price': total
                            })
                    break

        # If no items found, create a generic item
        if not items:
            total_amount = self._extract_total_amount(text)
            if total_amount > 0:
                items.append({
                    'description': 'Invoice Items',
                    'quantity': 1,
                    'unit_price': total_amount,
                    'total_price': total_amount
                })

        return items

    def _extract_amount(self, text: str, keywords: List[str]) -> float:
        """Extract monetary amount for given keywords"""
        for keyword in keywords:
            patterns = [
                rf'{keyword}\s*:?\s*\$?(\d+(?:,\d{{3}})*(?:\.\d{{2}})?)',
                rf'{keyword}\s*:?\s*(\d+(?:,\d{{3}})*(?:\.\d{{2}})?)',
            ]

            for pattern in patterns:
                match = re.search(pattern, text, re.IGNORECASE)
                if match:
                    amount_str = match.group(1).replace(',', '')
                    try:
                        return float(amount_str)
                    except ValueError:
                        continue

        return 0.0

    def _extract_subtotal(self, text: str) -> float:
        """Extract subtotal amount"""
        return self._extract_amount(text, ['subtotal', 'sub total', 'sub-total'])

    def _extract_tax_amount(self, text: str) -> float:
        """Extract tax amount"""
        return self._extract_amount(text, ['tax', 'vat', 'gst', 'sales tax'])

    def _extract_total_amount(self, text: str) -> float:
        """Extract total amount"""
        return self._extract_amount(text, ['total', 'grand total', 'amount due', 'balance due'])

    def _extract_payment_method(self, text: str) -> str:
        """Extract payment method"""
        patterns = [
            r'payment\s*:?\s*([^\n\r]+)',
            r'paid\s*by\s*:?\s*([^\n\r]+)',
            r'method\s*:?\s*([^\n\r]+)',
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                method = match.group(1).strip()
                if method:
                    return method

        # Look for common payment methods
        payment_methods = ['cash', 'credit', 'debit', 'card', 'check', 'cheque']
        text_lower = text.lower()

        for method in payment_methods:
            if method in text_lower:
                return method.title()

        return "Unknown"
