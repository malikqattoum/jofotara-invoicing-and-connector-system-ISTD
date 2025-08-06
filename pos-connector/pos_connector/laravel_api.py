import os
import requests
import json
import time
import logging
from pathlib import Path
from datetime import datetime, timedelta

# Set up logging
log_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'logs')
os.makedirs(log_dir, exist_ok=True)
logging.basicConfig(
    filename=os.path.join(log_dir, 'laravel_api.log'),
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

class LaravelAPI:
    def __init__(self, base_url, email, password, vendor_id=None):
        self.base_url = base_url.rstrip('/')
        self.email = email
        self.password = password
        self.vendor_id = vendor_id
        self.token = None
        self.token_expiry = None
        self.headers = {}
        self.max_retries = 3
        self.retry_delay = 2  # seconds
        self.config_file = Path(os.path.dirname(os.path.dirname(__file__))) / 'config.json'
        self.load_config()

        logging.info(f"LaravelAPI initialized with base URL: {self.base_url}")
        if not self.base_url.startswith(('http://', 'https://')):
            logging.warning(f"Base URL does not include protocol: {self.base_url}")
            self.base_url = f"http://{self.base_url}"

    def load_config(self):
        """Load configuration from config.json if it exists"""
        if self.config_file.exists():
            try:
                with open(self.config_file, 'r') as f:
                    config = json.load(f)
                    self.base_url = config.get('base_url', self.base_url).rstrip('/')
                    self.email = config.get('email', self.email)
                    self.password = config.get('password', self.password)
                    self.vendor_id = config.get('vendor_id', self.vendor_id)
                    self.token = config.get('token', self.token)

                    # Load token expiry if available
                    expiry_str = config.get('token_expiry')
                    if expiry_str:
                        try:
                            self.token_expiry = datetime.fromisoformat(expiry_str)
                        except ValueError:
                            self.token_expiry = None

                    if self.token:
                        self.headers = {'Authorization': f'Bearer {self.token}'}

                    logging.info(f"Loaded configuration from {self.config_file}")
                    print(f"Loaded configuration from {self.config_file}")
            except Exception as e:
                error_msg = f"Error loading config: {e}"
                logging.error(error_msg)
                print(error_msg)

    def save_config(self):
        """Save configuration to config.json"""
        # Load existing config to preserve other fields
        existing_config = {}
        if self.config_file.exists():
            try:
                with open(self.config_file, 'r') as f:
                    existing_config = json.load(f)
            except Exception as e:
                logging.warning(f"Could not load existing config for update: {e}")

        # Update with current values
        existing_config.update({
             'base_url': self.base_url,
             'email': self.email,
             'password': self.password,  # Save password for automatic token refresh
             'vendor_id': self.vendor_id,
             'token': self.token,
        })

        # Save token expiry if available
        if self.token_expiry:
            existing_config['token_expiry'] = self.token_expiry.isoformat()

        try:
            # Ensure the directory exists
            os.makedirs(os.path.dirname(self.config_file), exist_ok=True)

            with open(self.config_file, 'w') as f:
                json.dump(existing_config, f, indent=4)
            logging.info(f"Configuration saved to {self.config_file}")
            print(f"Configuration saved to {self.config_file}")
        except Exception as e:
            error_msg = f"Error saving config: {e}"
            logging.error(error_msg)
            print(error_msg)

    def is_token_valid(self):
        """Check if the current token is valid"""
        if not self.token:
            return False

        # Check if token is expired based on saved expiry time
        if self.token_expiry and datetime.now() >= self.token_expiry:
            logging.info("Token has expired based on saved expiry time")
            return False

        # Verify token with a lightweight API call
        try:
            test_response = requests.get(
                f"{self.base_url}/api/vendors/profile",
                headers=self.headers,
                timeout=10
            )

            if test_response.status_code == 200:
                logging.info("Token validation successful")
                return True
            else:
                logging.warning(f"Token validation failed: {test_response.status_code}")
                return False
        except requests.exceptions.RequestException as e:
            logging.error(f"Error validating token: {e}")
            return False

    def is_token_expiring_soon(self, minutes_threshold=30):
        """Check if token will expire within the specified minutes"""
        if not self.token_expiry:
            return False

        time_until_expiry = self.token_expiry - datetime.now()
        return time_until_expiry.total_seconds() < (minutes_threshold * 60)

    def ensure_valid_token(self):
        """Ensure we have a valid token, refreshing if necessary"""
        # If token is expired or expiring soon, refresh it
        if not self.token or not self.is_token_valid() or self.is_token_expiring_soon():
            logging.info("Token is expired, invalid, or expiring soon - refreshing...")
            return self.refresh_token()
        return True

    def refresh_token(self):
        """Refresh the authentication token using stored password"""
        if not self.password:
            logging.error("Cannot refresh token: no password available")
            print("❌ Cannot refresh token: no password stored in configuration")
            return False

        logging.info("Refreshing authentication token...")
        print("🔄 Token expired, refreshing authentication...")

        # Clear current token
        old_token = self.token
        self.token = None
        self.headers = {}

        # Attempt to authenticate with password (force refresh)
        if self.authenticate(force_refresh=True):
            logging.info("Token refresh successful")
            print("✅ Token refreshed successfully")
            return True
        else:
            # Restore old token if refresh failed
            self.token = old_token
            if self.token:
                self.headers = {'Authorization': f'Bearer {self.token}'}
            logging.error("Token refresh failed")
            print("❌ Token refresh failed")
            return False

    def authenticate(self, force_refresh=False):
        """Authenticate with the Laravel API and get a token"""
        # Check if we already have a valid token (unless forcing refresh)
        if not force_refresh and self.is_token_valid():
            print("Using existing authentication token")
            return True

        # Token is invalid or missing, authenticate
        logging.info(f"Authenticating with Laravel API at {self.base_url}")


        if not self.password:
            logging.error("Cannot authenticate: no password available")
            print("❌ Cannot authenticate: no password stored in configuration")
            return False

        for attempt in range(1, self.max_retries + 1):
            try:
                response = requests.post(
                    f"{self.base_url}/api/vendors/login",
                    json={
                        'email': self.email,
                        'password': self.password
                    },
                    timeout=15
                )

                if response.status_code == 200:
                    try:
                        data = response.json()
                        self.token = data['token']
                        self.vendor_id = data.get('user', {}).get('id')
                        self.headers = {'Authorization': f'Bearer {self.token}'}

                        # Set token expiry (default to 24 hours if not provided by API)
                        self.token_expiry = datetime.now() + timedelta(hours=24)

                        self.save_config()
                        logging.info("Authentication successful")
                        print("Authentication successful")
                        return True
                    except json.JSONDecodeError as e:
                        error_msg = f"Invalid JSON in successful response (Attempt {attempt}/{self.max_retries}): {e}"
                        logging.error(error_msg)
                        print(error_msg)
                        print(f"Response content: {response.text[:200]}...")

                        if attempt < self.max_retries:
                            time.sleep(self.retry_delay * attempt)
                        continue
                else:
                    error_msg = f"Authentication failed (Attempt {attempt}/{self.max_retries}): {response.status_code} - {response.text}"
                    logging.error(error_msg)
                    print(error_msg)

                    if attempt < self.max_retries:
                        time.sleep(self.retry_delay * attempt)  # Exponential backoff
            except requests.exceptions.ConnectionError as e:
                error_msg = f"Connection error (Attempt {attempt}/{self.max_retries}): Cannot connect to {self.base_url}"
                logging.error(error_msg)
                print(error_msg)
                print(f"💡 Tip: Make sure the Laravel backend is running at {self.base_url}")

                if attempt < self.max_retries:
                    time.sleep(self.retry_delay * attempt)
            except requests.exceptions.Timeout as e:
                error_msg = f"Timeout error (Attempt {attempt}/{self.max_retries}): Request timed out"
                logging.error(error_msg)
                print(error_msg)

                if attempt < self.max_retries:
                    time.sleep(self.retry_delay * attempt)
            except requests.exceptions.RequestException as e:
                error_msg = f"Authentication request error (Attempt {attempt}/{self.max_retries}): {e}"
                logging.error(error_msg)
                print(error_msg)

                if attempt < self.max_retries:
                    time.sleep(self.retry_delay * attempt)  # Exponential backoff
            except json.JSONDecodeError as e:
                error_msg = f"Invalid JSON response (Attempt {attempt}/{self.max_retries}): {e}"
                logging.error(error_msg)
                print(error_msg)
                print(f"💡 Tip: The API endpoint {self.base_url}/api/vendors/login may not exist or is returning HTML")

                if attempt < self.max_retries:
                    time.sleep(self.retry_delay * attempt)

        return False

    def _make_api_request(self, method, endpoint, **kwargs):
        """Make an API request with automatic token refresh if needed"""
        url = f"{self.base_url}/{endpoint.lstrip('/')}"

        if endpoint != 'api/vendors/login':
            if not self.ensure_valid_token():
                raise Exception("Cannot make API request: failed to obtain valid authentication token")


        # Ensure we have headers with authentication token
        if 'headers' not in kwargs:
            kwargs['headers'] = self.headers

        # Add timeout if not specified
        if 'timeout' not in kwargs:
            kwargs['timeout'] = 30

        for attempt in range(1, self.max_retries + 1):
            try:
                response = requests.request(method, url, **kwargs)

                # If unauthorized and not already trying to authenticate, refresh token and retry
                if response.status_code == 401 and endpoint != 'api/vendors/login':
                    logging.warning("Received 401 Unauthorized, attempting to refresh token")
                    if self.refresh_token():
                        # Update headers with new token
                        kwargs['headers'] = self.headers
                        continue
                    else:
                        logging.error("Token refresh failed, cannot continue with API request")
                        break

                return response
            except requests.exceptions.RequestException as e:
                error_msg = f"API request error ({method} {url}) (Attempt {attempt}/{self.max_retries}): {e}"
                logging.error(error_msg)

                if attempt < self.max_retries:
                    time.sleep(self.retry_delay * attempt)  # Exponential backoff

        # If we get here, all attempts failed
        raise Exception(f"Failed to make API request after {self.max_retries} attempts")

    def create_invoice(self, invoice_data):
        """Create a new invoice in the Laravel system"""
        try:
            logging.info(f"Creating invoice for customer: {invoice_data.get('customer_name')}")

            response = self._make_api_request(
                'POST',
                'api/invoices',
                json=invoice_data
            )

            if response.status_code == 201:
                invoice = response.json()
                logging.info(f"Invoice created successfully with ID: {invoice.get('id')}")
                return invoice
            else:
                error_msg = f"Failed to create invoice: {response.status_code} - {response.text}"
                logging.error(error_msg)
                print(error_msg)
                return None
        except Exception as e:
            error_msg = f"Error creating invoice: {e}"
            logging.error(error_msg)
            print(error_msg)
            return None

    def submit_invoice(self, invoice_id):
        """Submit an invoice to JoFotara through the Laravel API"""
        try:
            logging.info(f"Submitting invoice {invoice_id} to JoFotara")

            response = self._make_api_request(
                'POST',
                f"api/invoices/{invoice_id}/submit"
            )

            if response.status_code == 200:
                result = response.json()
                logging.info(f"Invoice {invoice_id} submitted successfully")
                return result
            else:
                error_msg = f"Failed to submit invoice {invoice_id}: {response.status_code} - {response.text}"
                logging.error(error_msg)
                print(error_msg)
                return None
        except Exception as e:
            error_msg = f"Error submitting invoice {invoice_id}: {e}"
            logging.error(error_msg)
            print(error_msg)
            return None

    def get_invoice_status(self, invoice_id):
        """Get the status of an invoice"""
        try:
            logging.info(f"Getting status for invoice {invoice_id}")

            response = self._make_api_request(
                'GET',
                f"api/invoices/status/{invoice_id}"
            )

            if response.status_code == 200:
                status = response.json()
                logging.info(f"Invoice {invoice_id} status: {status.get('status')}")
                return status
            else:
                error_msg = f"Failed to get invoice {invoice_id} status: {response.status_code} - {response.text}"
                logging.error(error_msg)
                print(error_msg)
                return None
        except Exception as e:
            error_msg = f"Error getting invoice {invoice_id} status: {e}"
            logging.error(error_msg)
            print(error_msg)
            return None

    def download_invoice_pdf(self, invoice_id, output_path):
        """Download the PDF version of an invoice"""
        try:
            logging.info(f"Downloading PDF for invoice {invoice_id} to {output_path}")

            # Ensure the directory exists
            os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)

            response = self._make_api_request(
                'GET',
                f"api/invoices/{invoice_id}/pdf",
                stream=True
            )

            if response.status_code == 200:
                with open(output_path, 'wb') as f:
                    for chunk in response.iter_content(chunk_size=8192):
                        f.write(chunk)
                logging.info(f"Invoice {invoice_id} PDF downloaded to {output_path}")
                print(f"Invoice PDF downloaded to {output_path}")
                return True
            else:
                error_msg = f"Failed to download invoice {invoice_id} PDF: {response.status_code} - {response.text}"
                logging.error(error_msg)
                print(error_msg)
                return False
        except Exception as e:
            error_msg = f"Error downloading invoice {invoice_id} PDF: {e}"
            logging.error(error_msg)
            print(error_msg)
            return False

    def download_invoice_xml(self, invoice_id, output_path):
        """Download the XML version of an invoice"""
        try:
            logging.info(f"Downloading XML for invoice {invoice_id} to {output_path}")

            # Ensure the directory exists
            os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)

            response = self._make_api_request(
                'GET',
                f"api/invoices/{invoice_id}/xml",
                stream=True
            )

            if response.status_code == 200:
                with open(output_path, 'wb') as f:
                    for chunk in response.iter_content(chunk_size=8192):
                        f.write(chunk)
                logging.info(f"Invoice {invoice_id} XML downloaded to {output_path}")
                print(f"Invoice XML downloaded to {output_path}")
                return True
            else:
                error_msg = f"Failed to download invoice {invoice_id} XML: {response.status_code} - {response.text}"
                logging.error(error_msg)
                print(error_msg)
                return False
        except Exception as e:
            error_msg = f"Error downloading invoice {invoice_id} XML: {e}"
            logging.error(error_msg)
            print(error_msg)
            return False

    def get_vendor_profile(self):
        """Get the vendor profile information"""
        try:
            logging.info("Getting vendor profile information")

            response = self._make_api_request(
                'GET',
                "api/vendors/profile"
            )

            if response.status_code == 200:
                profile = response.json()
                logging.info(f"Retrieved vendor profile for: {profile.get('name')}")
                return profile
            else:
                error_msg = f"Failed to get vendor profile: {response.status_code} - {response.text}"
                logging.error(error_msg)
                print(error_msg)
                return None
        except Exception as e:
            error_msg = f"Error getting vendor profile: {e}"
            logging.error(error_msg)
            print(error_msg)
            return None
