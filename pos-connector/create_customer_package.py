#!/usr/bin/env python3
"""
Customer Package Creator for JoFotara POS Connector
Creates customized installer packages for each customer
"""

import os
import json
import shutil
import hashlib
import zipfile
from pathlib import Path
from datetime import datetime

class CustomerPackageCreator:
    def __init__(self):
        self.base_dir = Path(__file__).parent
        self.dist_dir = self.base_dir / "dist"
        self.customer_packages_dir = self.base_dir / "customer_packages"

        # Ensure directories exist
        self.customer_packages_dir.mkdir(exist_ok=True)

    def generate_api_key(self, customer_id: str) -> str:
        """Generate unique API key for customer"""
        seed = f"{customer_id}_{datetime.now().isoformat()}_{os.urandom(16).hex()}"
        return hashlib.sha256(seed.encode()).hexdigest()

    def create_customer_config(self, customer_info: dict) -> dict:
        """Create customer-specific configuration"""
        api_key = self.generate_api_key(customer_info['customer_id'])

        config = {
            "customer_id": customer_info['customer_id'],
            "customer_name": customer_info['customer_name'],
            "api_url": customer_info.get('api_url', "https://yourdomain.com/api/pos-transactions"),
            "api_key": api_key,
            "sync_interval": customer_info.get('sync_interval', 300),
            "debug_mode": customer_info.get('debug_mode', False),
            "auto_start": customer_info.get('auto_start', True),
            "pos_types": customer_info.get('pos_types', ["universal"]),
            "contact_info": {
                "support_email": customer_info.get('support_email', "support@yourdomain.com"),
                "support_phone": customer_info.get('support_phone', "+1-800-SUPPORT")
            },
            "created_date": datetime.now().isoformat(),
            "version": "2.0.0"
        }

        return config, api_key

    def create_customer_installer(self, customer_info: dict) -> tuple:
        """Create customized installer for specific customer"""

        customer_id = customer_info['customer_id']
        customer_name = customer_info['customer_name']

        print(f"🏢 Creating package for: {customer_name} ({customer_id})")

        # Create customer package directory
        package_dir = self.customer_packages_dir / f"{customer_id}_{customer_name.replace(' ', '_')}"
        package_dir.mkdir(exist_ok=True)

        # Generate customer configuration
        config, api_key = self.create_customer_config(customer_info)

        # Save customer config
        config_file = package_dir / "customer_config.json"
        with open(config_file, 'w') as f:
            json.dump(config, f, indent=2)

        # Copy main executable if it exists
        exe_source = self.dist_dir / "JoFotara_POS_Connector.exe"
        if exe_source.exists():
            exe_dest = package_dir / "JoFotara_POS_Connector.exe"
            shutil.copy2(exe_source, exe_dest)
            print(f"   ✅ Copied executable ({exe_source.stat().st_size // 1024 // 1024} MB)")
        else:
            print(f"   ⚠️  Executable not found at {exe_source}")
            print(f"   💡 Run 'python build_exe.py' first to create the executable")

        # Create customized installer script
        installer_script = self.create_custom_installer_script(customer_info)
        installer_file = package_dir / "install.bat"
        with open(installer_file, 'w') as f:
            f.write(installer_script)

        # Create readme for customer
        readme_content = self.create_customer_readme(customer_info, api_key)
        readme_file = package_dir / "README.txt"
        with open(readme_file, 'w') as f:
            f.write(readme_content)

        # Create support info file
        support_info = self.create_support_info(customer_info)
        support_file = package_dir / "SUPPORT_INFO.txt"
        with open(support_file, 'w') as f:
            f.write(support_info)

        print(f"   ✅ Package created at: {package_dir}")

        return package_dir, api_key

    def create_custom_installer_script(self, customer_info: dict) -> str:
        """Create customer-specific installer script"""

        customer_name = customer_info['customer_name']
        customer_id = customer_info['customer_id']

        return f'''@echo off
title JoFotara POS Connector - {customer_name} Installation

echo ========================================
echo   JoFotara POS Connector Installer
echo   Customer: {customer_name}
echo   ID: {customer_id}
echo ========================================
echo.

set INSTALL_DIR=C:\\JoFotara\\POS_Connector
set PROGRAM_NAME=JoFotara_POS_Connector.exe
set CUSTOMER_CONFIG=customer_config.json

echo Installing JoFotara POS Connector for {customer_name}...
echo Installation directory: %INSTALL_DIR%
echo.

:: Check for admin rights
net session >nul 2>&1
if %errorLevel% == 0 (
    echo ✅ Administrator privileges confirmed
) else (
    echo ❌ ERROR: This installer must be run as Administrator
    echo Right-click install.bat and select "Run as administrator"
    echo.
    pause
    exit /b 1
)

:: Create installation directory
echo Creating installation directory...
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"

:: Copy executable
echo Copying JoFotara POS Connector...
copy "%~dp0%PROGRAM_NAME%" "%INSTALL_DIR%\\%PROGRAM_NAME%"

if errorlevel 1 (
    echo ❌ ERROR: Failed to copy executable
    pause
    exit /b 1
)

:: Copy customer configuration
echo Configuring for {customer_name}...
copy "%~dp0%CUSTOMER_CONFIG%" "%INSTALL_DIR%\\%CUSTOMER_CONFIG%"

if errorlevel 1 (
    echo ❌ ERROR: Failed to copy configuration
    pause
    exit /b 1
)

:: Create desktop shortcut
echo Creating desktop shortcut...
echo Set oWS = WScript.CreateObject("WScript.Shell") > "%TEMP%\\CreateShortcut.vbs"
echo sLinkFile = "%USERPROFILE%\\Desktop\\JoFotara POS Connector - {customer_name}.lnk" >> "%TEMP%\\CreateShortcut.vbs"
echo Set oLink = oWS.CreateShortcut(sLinkFile) >> "%TEMP%\\CreateShortcut.vbs"
echo oLink.TargetPath = "%INSTALL_DIR%\\%PROGRAM_NAME%" >> "%TEMP%\\CreateShortcut.vbs"
echo oLink.WorkingDirectory = "%INSTALL_DIR%" >> "%TEMP%\\CreateShortcut.vbs"
echo oLink.Description = "JoFotara POS Connector for {customer_name}" >> "%TEMP%\\CreateShortcut.vbs"
echo oLink.Save >> "%TEMP%\\CreateShortcut.vbs"
cscript /nologo "%TEMP%\\CreateShortcut.vbs"
del "%TEMP%\\CreateShortcut.vbs"

:: Create start menu entry
set START_MENU=%APPDATA%\\Microsoft\\Windows\\Start Menu\\Programs
if not exist "%START_MENU%\\JoFotara" mkdir "%START_MENU%\\JoFotara"
echo Set oWS = WScript.CreateObject("WScript.Shell") > "%TEMP%\\CreateStartMenu.vbs"
echo sLinkFile = "%START_MENU%\\JoFotara\\JoFotara POS Connector - {customer_name}.lnk" >> "%TEMP%\\CreateStartMenu.vbs"
echo Set oLink = oWS.CreateShortcut(sLinkFile) >> "%TEMP%\\CreateStartMenu.vbs"
echo oLink.TargetPath = "%INSTALL_DIR%\\%PROGRAM_NAME%" >> "%TEMP%\\CreateStartMenu.vbs"
echo oLink.WorkingDirectory = "%INSTALL_DIR%" >> "%TEMP%\\CreateStartMenu.vbs"
echo oLink.Description = "JoFotara POS Connector for {customer_name}" >> "%TEMP%\\CreateStartMenu.vbs"
echo oLink.Save >> "%TEMP%\\CreateStartMenu.vbs"
cscript /nologo "%TEMP%\\CreateStartMenu.vbs"
del "%TEMP%\\CreateStartMenu.vbs"

:: Test the installation
echo Testing installation...
"%INSTALL_DIR%\\%PROGRAM_NAME%" --test-config

echo.
echo 🎉 Installation completed successfully!
echo ========================================
echo   JoFotara POS Connector for {customer_name}
echo ========================================
echo.
echo Installation location: %INSTALL_DIR%
echo Configuration: Customized for {customer_name}
echo.
echo Shortcuts created:
echo - Desktop: "JoFotara POS Connector - {customer_name}"
echo - Start Menu: JoFotara ^> "JoFotara POS Connector - {customer_name}"
echo.
echo ✅ The connector will now automatically:
echo    - Detect your POS systems
echo    - Sync transaction data
echo    - Connect to JoFotara invoicing system
echo.
echo For support, see SUPPORT_INFO.txt or contact us at:
echo Email: {customer_info.get('support_email', 'support@yourdomain.com')}
echo Phone: {customer_info.get('support_phone', '+1-800-SUPPORT')}
echo.
pause
'''

    def create_customer_readme(self, customer_info: dict, api_key: str) -> str:
        """Create customer-specific README file"""

        return f'''
========================================
  JoFotara POS Connector - {customer_info['customer_name']}
========================================

CUSTOMER INFORMATION:
- Customer Name: {customer_info['customer_name']}
- Customer ID: {customer_info['customer_id']}
- API Key: {api_key}
- Created: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}

INSTALLATION INSTRUCTIONS:
1. Right-click "install.bat" and select "Run as administrator"
2. Follow the installation prompts
3. The connector will install to: C:\\JoFotara\\POS_Connector
4. Desktop and Start Menu shortcuts will be created

WHAT THIS CONNECTOR DOES:
✅ Automatically detects your POS systems
✅ Extracts transaction data securely
✅ Syncs data to JoFotara invoicing system
✅ Works with ANY POS software (universal compatibility)
✅ Runs continuously in the background
✅ No manual intervention required

SUPPORTED POS SYSTEMS:
- Restaurant POS systems
- Retail store systems
- Service business software
- Medical billing systems
- Any business software with transaction data

TECHNICAL DETAILS:
- Sync Interval: {customer_info.get('sync_interval', 300)} seconds
- Debug Mode: {'Enabled' if customer_info.get('debug_mode') else 'Disabled'}
- Auto Start: {'Yes' if customer_info.get('auto_start', True) else 'No'}

SUPPORT INFORMATION:
- Email: {customer_info.get('support_email', 'support@yourdomain.com')}
- Phone: {customer_info.get('support_phone', '+1-800-SUPPORT')}
- See SUPPORT_INFO.txt for detailed troubleshooting

FILES INCLUDED:
- JoFotara_POS_Connector.exe (Main application)
- install.bat (Installation script)
- customer_config.json (Your custom configuration)
- README.txt (This file)
- SUPPORT_INFO.txt (Support and troubleshooting)

PRIVACY & SECURITY:
- Only transaction data is synced (no personal files)
- All data is encrypted during transmission
- Your API key is unique and secure
- Connector only reads business transaction data

© 2025 JoFotara - Universal POS Integration System
'''

    def create_support_info(self, customer_info: dict) -> str:
        """Create support information file"""

        return f'''
========================================
  SUPPORT & TROUBLESHOOTING GUIDE
  JoFotara POS Connector - {customer_info['customer_name']}
========================================

QUICK DIAGNOSTIC COMMANDS:
Run these from Command Prompt (as Administrator):

Check Status:
cd "C:\\JoFotara\\POS_Connector"
JoFotara_POS_Connector.exe --status

Test Configuration:
JoFotara_POS_Connector.exe --test

View Logs:
JoFotara_POS_Connector.exe --logs

COMMON ISSUES & SOLUTIONS:

❌ ISSUE: "No POS systems detected"
✅ SOLUTION:
   - Ensure your POS software is running
   - Check if POS creates database/CSV files
   - Run connector as Administrator

❌ ISSUE: "Connection failed"
✅ SOLUTION:
   - Check internet connection
   - Verify firewall isn't blocking the connector
   - Confirm API key is correct

❌ ISSUE: "Permission denied"
✅ SOLUTION:
   - Right-click connector and "Run as administrator"
   - Check if antivirus is blocking the connector
   - Ensure POS data files aren't locked

❌ ISSUE: "Connector not starting"
✅ SOLUTION:
   - Reinstall using install.bat as Administrator
   - Check Windows Event Viewer for errors
   - Restart computer after installation

CONFIGURATION FILES:
- Main Config: C:\\JoFotara\\POS_Connector\\customer_config.json
- Log Files: C:\\JoFotara\\POS_Connector\\logs\\
- Database: C:\\JoFotara\\POS_Connector\\data\\

CONTACT SUPPORT:
Email: {customer_info.get('support_email', 'support@yourdomain.com')}
Phone: {customer_info.get('support_phone', '+1-800-SUPPORT')}

When contacting support, please provide:
1. Your Customer ID: {customer_info['customer_id']}
2. Error messages or screenshots
3. Your POS system type/name
4. Log files from the connector

REMOTE SUPPORT:
We can provide remote assistance via:
- TeamViewer
- Remote Desktop
- Scheduled support call

UPDATES:
The connector automatically checks for updates.
To manually update:
1. Download latest package from us
2. Run new install.bat as Administrator
3. Existing configuration will be preserved

========================================
© 2025 JoFotara - We're here to help!
========================================
'''

    def create_zip_package(self, package_dir: Path, customer_info: dict) -> Path:
        """Create ZIP package for easy distribution"""

        customer_id = customer_info['customer_id']
        customer_name = customer_info['customer_name'].replace(' ', '_')

        zip_filename = f"JoFotara_POS_Connector_{customer_id}_{customer_name}.zip"
        zip_path = self.customer_packages_dir / zip_filename

        with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
            for file_path in package_dir.rglob('*'):
                if file_path.is_file():
                    arcname = file_path.relative_to(package_dir)
                    zipf.write(file_path, arcname)

        print(f"   📦 ZIP package created: {zip_path}")
        print(f"   📦 Package size: {zip_path.stat().st_size // 1024 // 1024} MB")

        return zip_path

def main():
    """Demo: Create packages for sample customers"""

    creator = CustomerPackageCreator()

    # Sample customers for demonstration
    sample_customers = [
        {
            "customer_id": "CUST_001",
            "customer_name": "Mario's Pizza Restaurant",
            "api_url": "https://jofotara.com/api/pos-transactions",
            "support_email": "support@jofotara.com",
            "support_phone": "+1-800-JOFOTARA",
            "pos_types": ["restaurant", "universal"],
            "sync_interval": 300
        },
        {
            "customer_id": "CUST_002",
            "customer_name": "Fashion Boutique Store",
            "api_url": "https://jofotara.com/api/pos-transactions",
            "support_email": "support@jofotara.com",
            "support_phone": "+1-800-JOFOTARA",
            "pos_types": ["retail", "universal"],
            "sync_interval": 180
        }
    ]

    print("🚀 JoFotara Customer Package Creator")
    print("=" * 50)

    for customer_info in sample_customers:
        package_dir, api_key = creator.create_customer_installer(customer_info)
        zip_path = creator.create_zip_package(package_dir, customer_info)

        print(f"   🔑 API Key: {api_key}")
        print(f"   📧 Ready to email: {zip_path}")
        print()

    print("✅ All customer packages created successfully!")
    print("📁 Location: customer_packages/")
    print()
    print("💡 NEXT STEPS:")
    print("   1. Email ZIP files to respective customers")
    print("   2. Add API keys to your Laravel database")
    print("   3. Provide installation support if needed")

if __name__ == "__main__":
    main()
