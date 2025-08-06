#!/usr/bin/env python3
"""
API Connection Test Utility
Tests connectivity to the Laravel backend API
"""

import os
import sys
import json
import requests
from urllib.parse import urlparse

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

def test_basic_connectivity(base_url):
    """Test basic HTTP connectivity to the server"""
    print(f"🔗 Testing basic connectivity to {base_url}...")

    try:
        # Test basic HTTP connection
        response = requests.get(base_url, timeout=10)
        print(f"   ✅ Server is reachable (Status: {response.status_code})")

        # Check if it's a Laravel application
        if 'laravel' in response.text.lower() or 'app_name' in response.text:
            print("   ✅ Detected Laravel application")
        else:
            print("   ⚠️  May not be a Laravel application")

        return True

    except requests.exceptions.ConnectionError:
        print(f"   ❌ Cannot connect to server")
        print(f"   💡 Check if the server is running at {base_url}")
        return False
    except requests.exceptions.Timeout:
        print(f"   ❌ Connection timeout")
        print(f"   💡 Server may be slow or overloaded")
        return False
    except Exception as e:
        print(f"   ❌ Connection error: {e}")
        return False

def test_api_endpoints(base_url):
    """Test specific API endpoints"""
    print(f"\n🔍 Testing API endpoints...")

    endpoints_to_test = [
        ('/api', 'API root'),
        ('/api/vendors', 'Vendors API'),
        ('/api/vendors/login', 'Login endpoint'),
        ('/api/health', 'Health check'),
        ('/api/status', 'Status endpoint')
    ]

    for endpoint, description in endpoints_to_test:
        url = f"{base_url}{endpoint}"
        try:
            response = requests.get(url, timeout=5)
            if response.status_code == 200:
                print(f"   ✅ {description}: {endpoint} (200 OK)")
            elif response.status_code == 404:
                print(f"   ❌ {description}: {endpoint} (404 Not Found)")
            elif response.status_code == 405:
                print(f"   ⚠️  {description}: {endpoint} (405 Method Not Allowed - may need POST)")
            else:
                print(f"   ⚠️  {description}: {endpoint} ({response.status_code})")

        except requests.exceptions.RequestException as e:
            print(f"   ❌ {description}: {endpoint} (Error: {e})")

def test_token_authentication(base_url, token):
    """Test authentication with existing token"""
    print(f"\n🎫 Testing token authentication...")

    profile_url = f"{base_url}/api/vendors/profile"

    try:
        response = requests.get(
            profile_url,
            headers={'Authorization': f'Bearer {token}'},
            timeout=10
        )

        print(f"   📤 GET {profile_url}")
        print(f"   🎫 Token: {token[:20]}...")
        print(f"   📊 Response Status: {response.status_code}")

        if response.status_code == 200:
            try:
                data = response.json()
                print(f"   ✅ Token authentication successful!")
                print(f"   👤 User: {data.get('name', 'Unknown')} ({data.get('email', 'No email')})")
                return True
            except json.JSONDecodeError:
                print(f"   ❌ Invalid JSON response")
                print(f"   📄 Response content: {response.text[:200]}...")
        else:
            print(f"   ❌ Token authentication failed")
            print(f"   📄 Response content: {response.text[:200]}...")

    except requests.exceptions.ConnectionError:
        print(f"   ❌ Cannot connect to profile endpoint")
    except requests.exceptions.Timeout:
        print(f"   ❌ Profile request timeout")
    except Exception as e:
        print(f"   ❌ Token authentication error: {e}")

    return False

def test_authentication(base_url, email, password):
    """Test authentication with provided credentials"""
    print(f"\n🔐 Testing authentication...")

    login_url = f"{base_url}/api/vendors/login"

    try:
        response = requests.post(
            login_url,
            json={
                'email': email,
                'password': password
            },
            timeout=15
        )

        print(f"   📤 POST {login_url}")
        print(f"   📧 Email: {email}")
        print(f"   🔒 Password: {'*' * len(password)}")
        print(f"   📊 Response Status: {response.status_code}")

        if response.status_code == 200:
            try:
                data = response.json()
                if 'token' in data:
                    print(f"   ✅ Authentication successful!")
                    print(f"   🎫 Token received: {data['token'][:20]}...")
                    return True
                else:
                    print(f"   ❌ No token in response")
                    print(f"   📄 Response: {json.dumps(data, indent=2)}")
            except json.JSONDecodeError:
                print(f"   ❌ Invalid JSON response")
                print(f"   📄 Response content: {response.text[:200]}...")
        else:
            print(f"   ❌ Authentication failed")
            try:
                error_data = response.json()
                print(f"   📄 Error response: {json.dumps(error_data, indent=2)}")
            except json.JSONDecodeError:
                print(f"   📄 Response content: {response.text[:200]}...")

    except requests.exceptions.ConnectionError:
        print(f"   ❌ Cannot connect to login endpoint")
    except requests.exceptions.Timeout:
        print(f"   ❌ Login request timeout")
    except Exception as e:
        print(f"   ❌ Login error: {e}")

    return False

def load_config():
    """Load configuration from config.json"""
    config_file = 'config.json'

    if os.path.exists(config_file):
        try:
            with open(config_file, 'r') as f:
                return json.load(f)
        except Exception as e:
            print(f"❌ Error loading config: {e}")
            return None
    else:
        print(f"⚠️  Configuration file not found: {config_file}")
        return None

def suggest_solutions(base_url):
    """Suggest solutions based on the base URL"""
    print(f"\n💡 Troubleshooting Suggestions:")

    parsed_url = urlparse(base_url)

    if parsed_url.hostname in ['127.0.0.1', 'localhost']:
        print("   🏠 Local Development Server:")
        print("      • Make sure Laravel development server is running")
        print("      • Run: php artisan serve")
        print("      • Check if the port (8000) is correct")
        print("      • Verify no firewall is blocking the port")

    elif parsed_url.hostname:
        print("   🌐 Remote Server:")
        print(f"      • Verify the server {parsed_url.hostname} is accessible")
        print("      • Check if the domain/IP is correct")
        print("      • Ensure the Laravel application is deployed")
        print("      • Verify SSL/TLS configuration if using HTTPS")

    print("\n   🔧 General Solutions:")
    print("      • Check Laravel application logs")
    print("      • Verify API routes are registered (php artisan route:list)")
    print("      • Ensure database is connected and migrated")
    print("      • Check Laravel .env configuration")
    print("      • Verify vendor authentication is set up correctly")

def main():
    """Main test function"""
    print("🧪 JoFotara POS Connector - API Connection Test")
    print("=" * 60)

    # Load configuration
    config = load_config()

    if not config:
        print("❌ Cannot load configuration. Please ensure config.json exists.")
        return False

    base_url = config.get('base_url', 'http://127.0.0.1:8000')
    email = config.get('email', '')
    password = config.get('password', '')
    token = config.get('token', '')

    print(f"📋 Configuration:")
    print(f"   🌐 Base URL: {base_url}")
    print(f"   📧 Email: {email}")
    print(f"   🔒 Password: {'*' * len(password) if password else 'Not set'}")
    print(f"   🎫 Token: {'*' * 20 + '...' if token else 'Not set'}")

    # Test basic connectivity
    if not test_basic_connectivity(base_url):
        suggest_solutions(base_url)
        return False

    # Test API endpoints
    test_api_endpoints(base_url)

    # Test authentication - prefer token over password
    auth_success = False

    if token:
        auth_success = test_token_authentication(base_url, token)
    elif email and password:
        auth_success = test_authentication(base_url, email, password)
    else:
        print(f"\n⚠️  Cannot test authentication - no token or password configured")

    if auth_success:
        print(f"\n🎉 All tests passed! The API connection is working correctly.")
        return True
    elif token or (email and password):
        print(f"\n❌ Authentication failed. Check credentials and API configuration.")

    suggest_solutions(base_url)
    return False

if __name__ == "__main__":
    success = main()

    print(f"\n" + "=" * 60)
    if success:
        print("✅ API connection test completed successfully!")
        print("The POS connector should be able to authenticate and work properly.")
    else:
        print("❌ API connection test failed!")
        print("Please resolve the issues above before running the POS connector.")

    print("\n💡 Next steps:")
    print("   • Fix any connection issues identified above")
    print("   • Ensure Laravel backend is running and accessible")
    print("   • Verify API credentials are correct")
    print("   • Run the POS connector after resolving issues")

    input("\nPress Enter to exit...")
