#!/usr/bin/env python3
"""
Demo script showing automatic token refresh in action
"""

import sys
import os
import json
from datetime import datetime, timedelta
from pathlib import Path

# Add current directory to Python path
sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from pos_connector.laravel_api import LaravelAPI

def demo_token_refresh():
    """Demonstrate automatic token refresh functionality"""
    print("🚀 Automatic Token Refresh Demo")
    print("=" * 40)

    # Load config
    config_file = Path(__file__).parent / 'config.json'
    if not config_file.exists():
        print("❌ config.json not found.")
        return False

    try:
        with open(config_file, 'r') as f:
            config = json.load(f)
    except Exception as e:
        print(f"❌ Error loading config: {e}")
        return False

    if not config.get('password'):
        print("❌ Password not configured.")
        print("💡 Add password to config.json:")
        print('   "password": "your-password-here"')
        return False

    print(f"📧 Email: {config['email']}")
    print(f"🔗 API URL: {config['base_url']}")
    print()

    # Initialize API client
    api = LaravelAPI(
        base_url=config['base_url'],
        email=config['email'],
        password=config['password']
    )

    print("1️⃣ Initial authentication...")
    if api.authenticate():
        print(f"✅ Authenticated successfully")
        print(f"   Token expires: {api.token_expiry}")
    else:
        print("❌ Authentication failed")
        return False

    print("\n2️⃣ Making API request with valid token...")
    try:
        response = api._make_api_request('GET', 'api/vendors/profile')
        if response.status_code == 200:
            profile = response.json()
            print(f"✅ API request successful")
            print(f"   Vendor: {profile.get('email')}")
        else:
            print(f"❌ API request failed: {response.status_code}")
    except Exception as e:
        print(f"❌ API request error: {e}")

    print("\n3️⃣ Simulating token expiry...")
    # Simulate expired token
    api.token_expiry = datetime.now() - timedelta(minutes=1)
    print(f"   Token now expires: {api.token_expiry}")

    print("\n4️⃣ Making API request with expired token...")
    print("   (This should trigger automatic token refresh)")
    try:
        response = api._make_api_request('GET', 'api/vendors/profile')
        if response.status_code == 200:
            print(f"✅ API request successful after automatic refresh!")
            print(f"   New token expires: {api.token_expiry}")
        else:
            print(f"❌ API request failed: {response.status_code}")
    except Exception as e:
        print(f"❌ API request error: {e}")

    print("\n🎉 Demo completed!")
    print("The system automatically refreshed the expired token and continued working.")

    return True

if __name__ == "__main__":
    try:
        demo_token_refresh()
    except KeyboardInterrupt:
        print("\n🛑 Demo interrupted.")
    except Exception as e:
        print(f"\n❌ Demo failed: {e}")
