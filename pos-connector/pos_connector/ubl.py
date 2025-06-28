import qrcode
import base64
from io import BytesIO
import requests

class UBLInvoiceGenerator:
    def send_invoice_to_laravel(self, invoice_data: dict, api_url: str, api_token: str) -> dict:
        headers = {"Authorization": f"Bearer {api_token}", "Content-Type": "application/json"}
        response = requests.post(api_url, json=invoice_data, headers=headers)
        response.raise_for_status()
        return response.json()

    # Optionally, keep QR code generation if needed for POS printout
    def generate_qr_code(self, qr_content: str) -> str:
        qr = qrcode.QRCode(error_correction=getattr(qrcode, 'ERROR_CORRECT_M', 0))
        qr.add_data(qr_content)
        qr.make(fit=True)
        img = qr.make_image(fill_color="black", back_color="white")
        buffer = BytesIO()
        img.save(buffer, format="PNG")
        return base64.b64encode(buffer.getvalue()).decode()
