--- pos-connector/test_token_refresh.py
 pos-connector/test_token_refresh.py
@@ -0,0 1,152 @@
#!/usr/bin/env python3
"""
Test script for automatic token refresh functionality
"""

import sys
import os
import json
import time
from datetime import datetime, timedelta
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.laravel_api import LaravelAPI

def test_token_refresh():
    """Test the automatic token refresh functionality"""
    print("🧪 Testing Automatic Token Refresh Functionality")
    print("=" * 60)

    # Load config
    config_file = Path(__file__).parent / 'config.json'
    if not config_file.exists():
        print("❌ config.json not found. Please run setup first.")
        return False

    try:
        with open(config_file, 'r') as f:
            config = json.load(f)
    except Exception as e:
        print(f"❌ Error loading config: {e}")
        return False

    # Check if password is configured
    if not config.get('password'):
        print("❌ Password not configured in config.json")
        print("💡 Please add your password to config.json for automatic token refresh")
        return False

    print(f"📧 Email: {config['email']}")
    print(f"🔗 API URL: {config['base_url']}")
    print(f"🔑 Password configured: {'Yes' if config.get('password') else 'No'}")
    print()

    # Initialize API client
    api = LaravelAPI(
        base_url=config['base_url'],
        email=config['email'],
        password=config['password']
    )

    print("1️⃣ Testing initial authentication...")
    if api.authenticate():
        print("✅ Initial authentication successful")
        print(f"   Token: {api.token[:20]}...")
        print(f"   Expires: {api.token_expiry}")
    else:
        print("❌ Initial authentication failed")
        return False

    print("\n2️⃣ Testing token validation...")
    if api.is_token_valid():
        print("✅ Token is valid")
    else:
        print("❌ Token is invalid")
        return False

    print("\n3️⃣ Testing API request with valid token...")
    try:
        # Make a test API request
        response = api._make_api_request('GET', 'api/vendors/profile')
        if response.status_code == 200:
            print("✅ API request successful")
            profile = response.json()
            print(f"   Vendor ID: {profile.get('id')}")
            print(f"   Email: {profile.get('email')}")
        else:
            print(f"❌ API request failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ API request error: {e}")
        return False

    print("\n4️⃣ Testing token expiry simulation...")
    # Simulate expired token by setting expiry to past
    original_expiry = api.token_expiry
    api.token_expiry = datetime.now() - timedelta(minutes=1)
    print(f"   Simulated expiry: {api.token_expiry}")

    if api.is_token_valid():
        print("❌ Token should be invalid (expired)")
        return False
    else:
        print("✅ Token correctly detected as expired")

    print("\n5️⃣ Testing automatic token refresh...")
    if api.ensure_valid_token():
        print("✅ Token refresh successful")
        print(f"   New token: {api.token[:20]}...")
        print(f"   New expiry: {api.token_expiry}")
    else:
        print("❌ Token refresh failed")
        return False

    print("\n6️⃣ Testing API request after token refresh...")
    try:
        response = api._make_api_request('GET', 'api/vendors/profile')
        if response.status_code == 200:
            print("✅ API request successful after token refresh")
        else:
            print(f"❌ API request failed after refresh: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ API request error after refresh: {e}")
        return False

    print("\n7️⃣ Testing token expiring soon detection...")
    # Simulate token expiring in 15 minutes
    api.token_expiry = datetime.now() + timedelta(minutes=15)
    if api.is_token_expiring_soon(minutes_threshold=30):
        print("✅ Token correctly detected as expiring soon")
    else:
        print("❌ Token should be detected as expiring soon")
        return False

    print("\n🎉 All tests passed! Automatic token refresh is working correctly.")
    print("\n📋 Summary:")
    print("   ✅ Initial authentication works")
    print("   ✅ Token validation works")
    print("   ✅ API requests work with valid token")
    print("   ✅ Expired token detection works")
    print("   ✅ Automatic token refresh works")
    print("   ✅ API requests work after token refresh")
    print("   ✅ Token expiring soon detection works")

    return True

def setup_password():
    """Helper function to set up password in config.json"""
    config_file = Path(__file__).parent / 'config.json'

    if not config_file.exists():
        print("❌ config.json not found. Please run the main setup first.")
        return False

    try:
        with open(config_file, 'r') as f:
            config = json.load(f)
    except Exception as e:
        print(f"❌ Error loading config: {e}")
        return False

    print("🔐 Password Setup for Automatic Token Refresh")
    print("=" * 50)
    print(f"Current email: {config.get('email', 'Not set')}")

    if config.get('password'):
        print("Password is already configured.")
        update = input("Do you want to update it? (y/n): ").strip().lower()
        if update != 'y':
            return True

    password = input("Enter your password: ").strip()
    if not password:
        print("❌ Password cannot be empty")
        return False

    config['password'] = password

    try:
        with open(config_file, 'w') as f:
            json.dump(config, f, indent=4)
        print("✅ Password saved to config.json")
        print("🔒 Note: Password is stored in plain text for automatic refresh")
        print("   Make sure to secure your config.json file appropriately")
        return True
    except Exception as e:
        print(f"❌ Error saving config: {e}")
        return False

if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] == "--setup-password":
        setup_password()
        sys.exit(0)
    try:
        success = test_token_refresh()
        if success:
            print("\n✅ Token refresh functionality is ready!")
        else:
            print("\n❌ Token refresh functionality needs attention.")
            print("💡 Run 'python test_token_refresh.py --setup-password' to configure password")
            sys.exit(1)
    except KeyboardInterrupt:
        print("\n🛑 Test interrupted by user.")
    except Exception as e:
        print(f"\n❌ Test failed with error: {e}")
        sys.exit(1)
