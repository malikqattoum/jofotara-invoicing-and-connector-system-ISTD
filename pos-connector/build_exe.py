#!/usr/bin/env python3
"""
Build script to create standalone EXE for JoFotara POS Connector
This creates a single executable file that customers can install and run
"""

import os
import subprocess
import sys
from pathlib import Path

def create_spec_file():
    """Create PyInstaller spec file for the POS Connector"""

    spec_content = '''
# -*- mode: python ; coding: utf-8 -*-

import os
import sys
from PyInstaller.utils.hooks import collect_all

# Collect all necessary modules
datas = []
hiddenimports = []

# Add config and required files
datas += [('config.json', '.')]
datas += [('pos_connector', 'pos_connector')]

# Hidden imports for database drivers and Windows modules
hiddenimports += [
    'sqlite3',
    'win32api', 'win32con', 'win32security', 'win32process',
    'wmi', 'psutil',
    'requests', 'urllib3', 'certifi',
    'json', 'csv', 'xml.etree.ElementTree',
    'datetime', 'time', 'threading', 'concurrent.futures'
]

# Optional database drivers (include if available)
try:
    import pyodbc
    hiddenimports.append('pyodbc')
except ImportError:
    pass

try:
    import pymysql
    hiddenimports.append('pymysql')
except ImportError:
    pass

try:
    import psycopg2
    hiddenimports.append('psycopg2')
except ImportError:
    pass

try:
    import pandas
    hiddenimports.extend(['pandas', 'numpy'])
except ImportError:
    pass

a = Analysis(
    ['main.py'],
    pathex=[],
    binaries=[],
    datas=datas,
    hiddenimports=hiddenimports,
    hookspath=[],
    hooksconfig={},
    runtime_hooks=[],
    excludes=[],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    cipher=None,
    noarchive=False,
)

pyz = PYZ(a.pure, a.zipped_data, cipher=None)

exe = EXE(
    pyz,
    a.scripts,
    a.binaries,
    a.zipfiles,
    a.datas,
    [],
    name='JoFotara_POS_Connector',
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=True,
    upx_exclude=[],
    runtime_tmpdir=None,
    console=True,
    disable_windowed_traceback=False,
    argv_emulation=False,
    target_arch=None,
    codesign_identity=None,
    entitlements_file=None,
    icon='icon.ico' if os.path.exists('icon.ico') else None
)
'''

    with open('JoFotara_POS_Connector.spec', 'w') as f:
        f.write(spec_content.strip())

    print("✅ Created PyInstaller spec file")

def install_pyinstaller():
    """Install PyInstaller if not already installed"""
    try:
        import PyInstaller
        print("✅ PyInstaller already installed")
    except ImportError:
        print("📦 Installing PyInstaller...")
        subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'pyinstaller'])
        print("✅ PyInstaller installed")

def build_executable():
    """Build the standalone executable"""
    print("🔨 Building JoFotara POS Connector executable...")

    # Run PyInstaller
    cmd = [
        sys.executable, '-m', 'PyInstaller',
        '--clean',
        '--onefile',
        'JoFotara_POS_Connector.spec'
    ]

    result = subprocess.run(cmd, capture_output=True, text=True)

    if result.returncode == 0:
        print("✅ Executable built successfully!")
        print("📁 Location: dist/JoFotara_POS_Connector.exe")
        print("📦 File size: ~20-30 MB (includes Python runtime)")
        return True
    else:
        print("❌ Build failed:")
        print(result.stderr)
        return False

def create_installer_script():
    """Create a simple installer script"""

    installer_content = '''@echo off
echo ========================================
echo   JoFotara POS Connector Installer
echo ========================================
echo.

set INSTALL_DIR=C:\\JoFotara\\POS_Connector
set PROGRAM_NAME=JoFotara_POS_Connector.exe

echo Installing to: %INSTALL_DIR%
echo.

:: Create installation directory
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"

:: Copy executable
copy "%~dp0%PROGRAM_NAME%" "%INSTALL_DIR%\\%PROGRAM_NAME%"

if errorlevel 1 (
    echo ERROR: Failed to copy executable
    pause
    exit /b 1
)

:: Create desktop shortcut
echo Creating desktop shortcut...
echo Set oWS = WScript.CreateObject("WScript.Shell") > "%TEMP%\\CreateShortcut.vbs"
echo sLinkFile = "%USERPROFILE%\\Desktop\\JoFotara POS Connector.lnk" >> "%TEMP%\\CreateShortcut.vbs"
echo Set oLink = oWS.CreateShortcut(sLinkFile) >> "%TEMP%\\CreateShortcut.vbs"
echo oLink.TargetPath = "%INSTALL_DIR%\\%PROGRAM_NAME%" >> "%TEMP%\\CreateShortcut.vbs"
echo oLink.WorkingDirectory = "%INSTALL_DIR%" >> "%TEMP%\\CreateShortcut.vbs"
echo oLink.Description = "JoFotara Universal POS Connector" >> "%TEMP%\\CreateShortcut.vbs"
echo oLink.Save >> "%TEMP%\\CreateShortcut.vbs"
cscript /nologo "%TEMP%\\CreateShortcut.vbs"
del "%TEMP%\\CreateShortcut.vbs"

:: Create start menu entry
set START_MENU=%APPDATA%\\Microsoft\\Windows\\Start Menu\\Programs
if not exist "%START_MENU%\\JoFotara" mkdir "%START_MENU%\\JoFotara"
echo Set oWS = WScript.CreateObject("WScript.Shell") > "%TEMP%\\CreateStartMenu.vbs"
echo sLinkFile = "%START_MENU%\\JoFotara\\JoFotara POS Connector.lnk" >> "%TEMP%\\CreateStartMenu.vbs"
echo Set oLink = oWS.CreateShortcut(sLinkFile) >> "%TEMP%\\CreateStartMenu.vbs"
echo oLink.TargetPath = "%INSTALL_DIR%\\%PROGRAM_NAME%" >> "%TEMP%\\CreateStartMenu.vbs"
echo oLink.WorkingDirectory = "%INSTALL_DIR%" >> "%TEMP%\\CreateStartMenu.vbs"
echo oLink.Description = "JoFotara Universal POS Connector" >> "%TEMP%\\CreateStartMenu.vbs"
echo oLink.Save >> "%TEMP%\\CreateStartMenu.vbs"
cscript /nologo "%TEMP%\\CreateStartMenu.vbs"
del "%TEMP%\\CreateStartMenu.vbs"

echo.
echo ✅ Installation completed successfully!
echo.
echo The JoFotara POS Connector has been installed to:
echo %INSTALL_DIR%
echo.
echo Shortcuts created:
echo - Desktop: JoFotara POS Connector
echo - Start Menu: JoFotara ^> JoFotara POS Connector
echo.
echo You can now run the connector from the desktop shortcut.
echo The connector will automatically detect your POS systems!
echo.
pause
'''

    with open('dist/install.bat', 'w') as f:
        f.write(installer_content)

    print("✅ Created installer script: dist/install.bat")

def main():
    """Main build process"""
    print("🚀 JoFotara POS Connector - Executable Builder")
    print("=" * 50)

    # Check if we're in the right directory
    if not os.path.exists('main.py'):
        print("❌ Error: main.py not found. Run this script from the pos-connector directory.")
        return False

    # Install PyInstaller
    install_pyinstaller()

    # Create spec file
    create_spec_file()

    # Build executable
    if build_executable():
        create_installer_script()

        print("\n🎉 BUILD COMPLETE!")
        print("=" * 50)
        print("📦 Files created:")
        print("   - dist/JoFotara_POS_Connector.exe (main executable)")
        print("   - dist/install.bat (installer script)")
        print("\n📋 Distribution Instructions:")
        print("   1. Copy both files to customer machine")
        print("   2. Run 'install.bat' as Administrator")
        print("   3. Connector installs to C:\\JoFotara\\POS_Connector")
        print("   4. Desktop & Start Menu shortcuts created")
        print("   5. Runs automatically on Windows startup")
        print("\n✅ Ready for customer deployment!")
        return True

    return False

if __name__ == "__main__":
    main()
'''
