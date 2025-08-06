#!/usr/bin/env python3
"""
Laravel Backend Status Checker
Quick utility to check if the Laravel backend is running
"""

import os
import sys
import json
import requests
import subprocess
from pathlib import Path

def load_config():
    """Load configuration from config.json"""
    config_file = Path(__file__).parent / 'config.json'

    if config_file.exists():
        try:
            with open(config_file, 'r') as f:
                return json.load(f)
        except Exception as e:
            print(f"❌ Error loading config: {e}")
            return None
    else:
        return {'base_url': 'http://127.0.0.1:8000'}

def check_laravel_backend(base_url):
    """Check if Laravel backend is running"""
    print(f"🔍 Checking Laravel backend at {base_url}...")

    try:
        # Test basic connectivity
        response = requests.get(base_url, timeout=10)

        if response.status_code == 200:
            print(f"✅ Laravel backend is running!")
            print(f"   Status: {response.status_code}")

            # Check if it looks like Laravel
            content = response.text.lower()
            if 'laravel' in content:
                print("   ✅ Confirmed Laravel application")
            elif 'app_name' in content:
                print("   ✅ Looks like a Laravel application")
            else:
                print("   ⚠️  May not be a Laravel application")

            return True

        else:
            print(f"⚠️  Server responded with status {response.status_code}")
            print(f"   This may indicate an issue with the Laravel application")
            return False

    except requests.exceptions.ConnectionError:
        print(f"❌ Cannot connect to Laravel backend")
        print(f"   The server at {base_url} is not reachable")
        return False

    except requests.exceptions.Timeout:
        print(f"❌ Connection timeout")
        print(f"   The server at {base_url} is not responding")
        return False

    except Exception as e:
        print(f"❌ Error checking backend: {e}")
        return False

def check_laravel_processes():
    """Check if Laravel development server is running"""
    print(f"\n🔍 Checking for Laravel processes...")

    try:
        # Check for PHP processes (Laravel dev server)
        if os.name == 'nt':  # Windows
            result = subprocess.run(['tasklist', '/FI', 'IMAGENAME eq php.exe'],
                                  capture_output=True, text=True)
            if 'php.exe' in result.stdout:
                print("   ✅ PHP process found (Laravel dev server may be running)")
                return True
            else:
                print("   ❌ No PHP processes found")
                return False
        else:  # Unix-like
            result = subprocess.run(['pgrep', '-f', 'php.*serve'],
                                  capture_output=True, text=True)
            if result.returncode == 0:
                print("   ✅ Laravel development server process found")
                return True
            else:
                print("   ❌ No Laravel development server process found")
                return False

    except Exception as e:
        print(f"   ⚠️  Could not check processes: {e}")
        return False

def suggest_solutions():
    """Suggest solutions for starting Laravel backend"""
    print(f"\n💡 How to start the Laravel backend:")
    print(f"   1. Navigate to the Laravel project directory:")
    print(f"      cd c:/xampp/htdocs/jo-invoicing")
    print(f"   2. Start the development server:")
    print(f"      php artisan serve")
    print(f"   3. The server should start at http://127.0.0.1:8000")
    print(f"   4. Keep the terminal window open while using the POS connector")

    print(f"\n🔧 Alternative methods:")
    print(f"   • Using XAMPP: Start Apache and place Laravel in htdocs")
    print(f"   • Using Laravel Valet (Windows): valet start")
    print(f"   • Using Docker: docker-compose up")

    print(f"\n⚠️  Common issues:")
    print(f"   • Port 8000 already in use: php artisan serve --port=8001")
    print(f"   • Permission issues: Run terminal as Administrator")
    print(f"   • Missing dependencies: composer install")
    print(f"   • Database not configured: Check .env file")

def main():
    """Main function"""
    print("🚀 Laravel Backend Status Checker")
    print("=" * 50)

    # Load configuration
    config = load_config()
    if not config:
        print("❌ Could not load configuration")
        return False

    base_url = config.get('base_url', 'http://127.0.0.1:8000')
    print(f"📋 Configured backend URL: {base_url}")

    # Check if backend is running
    backend_running = check_laravel_backend(base_url)

    # Check for Laravel processes
    process_running = check_laravel_processes()

    print(f"\n" + "=" * 50)

    if backend_running:
        print("🎉 Laravel backend is running and accessible!")
        print("✅ The POS connector should be able to connect successfully.")

        print(f"\n🔗 Backend URLs to test:")
        print(f"   • Main page: {base_url}")
        print(f"   • API login: {base_url}/api/vendors/login")
        print(f"   • API status: {base_url}/api/status")

        return True

    else:
        print("❌ Laravel backend is not accessible!")

        if process_running:
            print("⚠️  PHP process found but backend not accessible")
            print("   This may indicate a configuration issue")
        else:
            print("❌ No Laravel development server process found")

        suggest_solutions()
        return False

if __name__ == "__main__":
    success = main()

    print(f"\n💡 Next steps:")
    if success:
        print("   • Run the POS connector: python main.py")
        print("   • The connector should authenticate successfully")
    else:
        print("   • Start the Laravel backend using the suggestions above")
        print("   • Run this checker again to verify")
        print("   • Then start the POS connector")

    input("\nPress Enter to exit...")
