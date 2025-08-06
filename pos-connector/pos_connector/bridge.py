import os
import requests

# Try to load dotenv if available, but don't fail if it's not
try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    # dotenv not available, continue without it
    pass

class POSConnector:
    def __init__(self):
        self.api_url = os.getenv('JOFOTARA_API_URL')
        self.client_id = os.getenv('JOFOTARA_CLIENT_ID')
        self.secret_key = os.getenv('JOFOTARA_SECRET_KEY')

    def send_invoice(self, xml_base64, uuid, filename):
        headers = {
            'client_id': self.client_id,
            'secret_key': self.secret_key,
        }
        payload = {
            'invoice': xml_base64,
            'uuid': uuid,
            'filename': filename
        }
        response = requests.post(self.api_url, headers=headers, json=payload)
        return response
