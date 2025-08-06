# 🚀 JoFotara Universal POS Connector - Installation Guide

## 📦 Customer Installation (Recommended - No Python Required)

### **Step 1: Download Files**
You should have received these files:
- `JoFotara_POS_Connector.exe` (Main program)
- `install.bat` (Enhanced installer)
- `uninstall.bat` (Uninstaller)
- `troubleshoot.bat` (Troubleshooting tool)
- `config.json` (Configuration file)

### **Step 2: Install**
1. **Right-click** on `install.bat`
2. Select **"Run as administrator"**
3. Installer handles running processes automatically
4. Choose to start the connector when prompted

### **Step 3: Done!**
✅ Connector installed and running automatically!

---

## 🔧 Enhanced Installer Features

### **Automatic Process Management:**
- Detects if connector is already running
- Safely stops processes before updating
- **Fixes "file in use" errors automatically**

### **Complete System Setup:**
- Creates installation directory: `C:\JoFotara\POS_Connector`
- Adds Windows Firewall exceptions
- Creates desktop and Start Menu shortcuts
- Sets up logging and data directories

### **Troubleshooting Tools:**
- `troubleshoot.bat` - System diagnostics
- `uninstall.bat` - Clean removal
- Enhanced error messages and solutions

---

## 🔧 Fixing "Process Cannot Access File" Error

### **Problem:**
```
The process cannot access the file because it is being used by another process.
ERROR: Failed to copy executable
```

### **Solution (Automatic):**
The enhanced installer now handles this automatically:
1. Detects running connector processes
2. Safely stops them before installation
3. Waits for complete termination
4. Proceeds with installation

### **Manual Solution (if needed):**
1. **Stop the Connector:**
   - Press `Ctrl+C` in the connector console window
   - Or close the console window
   - Or use Task Manager to end `JoFotara_POS_Connector.exe`

2. **Wait a moment** for the process to fully terminate

3. **Run installer again:**
   - Right-click `install.bat`
   - Select "Run as administrator"

### **Prevention:**
- Always use the enhanced `install.bat` (not the old version)
- The new installer includes automatic process management
- No manual intervention needed for updates

---

## ⚡ Developer Installation (Python Required)

### 1. Prerequisites Check
```bash
# Check Python version (3.8+ required)
python --version

# Check pip
pip --version
```

### 2. Install Dependencies
```bash
cd pos-connector
pip install -r requirements.txt
```

### 3. Run Setup Wizard
```bash
python main.py --setup
```

### 4. Start Connector
```bash
# Test in interactive mode first
python main.py

# Install as Windows service for production
python main.py install
python main.py start
```

## 🔧 Detailed Installation

### Step 1: System Preparation

#### Install Python 3.8+
1. Download from [python.org](https://python.org/downloads/)
2. **Important**: Check "Add Python to PATH" during installation
3. Verify installation:
   ```bash
   python --version
   pip --version
   ```

#### Install Visual C++ Redistributables (if needed)
Some database drivers require Visual C++ redistributables:
- Download from [Microsoft](https://support.microsoft.com/en-us/help/2977003/the-latest-supported-visual-c-downloads)

### Step 2: Download and Setup

#### Clone/Download the Connector
```bash
# If using git
git clone <repository-url>
cd pos-connector

# Or download and extract ZIP file
```

#### Install Python Dependencies
```bash
# Install all required packages
pip install -r requirements.txt

# If you encounter errors, try upgrading pip first
pip install --upgrade pip
pip install -r requirements.txt
```

### Step 3: Configuration

#### Run Interactive Setup
```bash
python main.py --setup
```

The setup wizard will ask for:

1. **JoFotara API URL**
   - Example: `http://localhost:8000`
   - Example: `https://your-domain.com`

2. **Vendor Credentials**
   - Email: Your vendor account email
   - Password: Your vendor account password

3. **Detection Mode**
   - `1`: Automatic detection (recommended)
   - `2`: File monitoring only
   - `3`: Manual configuration

4. **Advanced Settings**
   - Sync interval (30-3600 seconds)
   - Auto-submit to JoFotara (yes/no)
   - Log level (DEBUG/INFO/WARNING/ERROR)

#### Manual Configuration (Optional)
Edit `config.json` directly:
```json
{
    "base_url": "http://localhost:8000",
    "email": "vendor@example.com",
    "password": "your_password",
    "detection_mode": "1",
    "sync_interval": 60,
    "auto_submit_jofotara": false,
    "log_level": "INFO"
}
```

### Step 4: Testing

#### Test in Interactive Mode
```bash
python main.py
```

Look for these success indicators:
- ✅ Authentication successful
- 🔍 POS system detection started
- 📊 Systems discovered and validated
- 🚀 Enhanced POS monitoring started

#### Test API Connection
The connector will automatically test the connection and display:
- API connectivity status
- Authentication result
- Discovered POS systems

### Step 5: Production Deployment

#### Install as Windows Service
```bash
# Install service (run as administrator)
python main.py install

# Start service
python main.py start

# Check status
python main.py status
```

#### Service Management Commands
```bash
python main.py start      # Start service
python main.py stop       # Stop service
python main.py restart    # Restart service
python main.py status     # Check status
python main.py uninstall  # Remove service
```

## 🔍 Verification Steps

### 1. Check Service Status
```bash
python main.py status
```
Should show: `Service Status: Running`

### 2. Monitor Logs
Check these log files in the `logs/` directory:
- `enhanced_connector.log` - Main application logs
- `laravel_api.log` - API communication
- `service.log` - Windows service logs

### 3. Test POS Detection
Look in logs for messages like:
```
INFO - Discovered 3 POS systems
INFO - Starting monitoring for Square POS
INFO - Starting monitoring for QuickBooks POS
```

### 4. Test Transaction Processing
The connector will show status updates:
```
📈 Status: 3 systems, 3 active monitors, 0 queued items
```

## 🚨 Troubleshooting Installation

### Common Installation Issues

#### 1. Python Not Found
```bash
# Error: 'python' is not recognized
```
**Solution**: Add Python to PATH or use full path:
```bash
C:\Python38\python.exe main.py --setup
```

#### 2. pip Install Failures
```bash
# Error: Failed building wheel for pyodbc
```
**Solution**: Install Microsoft C++ Build Tools or use pre-compiled wheels:
```bash
pip install --upgrade pip
pip install pyodbc --only-binary=all
```

#### 3. Permission Errors
```bash
# Error: Access denied
```
**Solution**: Run as administrator:
- Right-click Command Prompt → "Run as administrator"
- Then run the installation commands

#### 4. Service Installation Fails
```bash
# Error: Access denied installing service
```
**Solution**: 
1. Run Command Prompt as administrator
2. Navigate to pos-connector directory
3. Run: `python main.py install`

### Network and Connectivity Issues

#### 1. API Connection Failed
**Check**:
- Laravel application is running
- Firewall allows outbound connections
- Correct API URL in config

**Test manually**:
```bash
curl http://localhost:8000/api/vendors/login
```

#### 2. Authentication Failed
**Check**:
- Vendor account exists in Laravel system
- Correct email and password
- Account is active and has vendor role

**Reset**:
```bash
python main.py --setup
```

### Database Driver Issues

#### 1. SQL Server Connection
```bash
# Install SQL Server ODBC driver
# Download from Microsoft's website
```

#### 2. MySQL Connection
```bash
pip install pymysql
# Or install MySQL Connector/ODBC
```

#### 3. PostgreSQL Connection
```bash
pip install psycopg2-binary
```

## 📋 System Requirements Check

### Minimum Requirements
- **OS**: Windows 10 or Windows Server 2016+
- **Python**: 3.8+
- **RAM**: 2GB available
- **Disk**: 1GB free space
- **Network**: Internet access to JoFotara API

### Recommended Requirements
- **OS**: Windows 11 or Windows Server 2019+
- **Python**: 3.9+
- **RAM**: 4GB available
- **Disk**: 5GB free space (for logs and cache)
- **CPU**: Multi-core processor for multiple POS systems

### Performance Optimization

#### For High-Volume Environments
```json
{
    "sync_interval": 30,
    "max_concurrent_syncs": 10,
    "batch_size": 200,
    "log_level": "WARNING"
}
```

#### For Low-Resource Systems
```json
{
    "sync_interval": 300,
    "max_concurrent_syncs": 2,
    "batch_size": 50,
    "log_level": "ERROR"
}
```

## 🔄 Updates and Maintenance

### Updating the Connector
1. Stop the service: `python main.py stop`
2. Backup configuration: Copy `config.json`
3. Update files (replace with new version)
4. Restore configuration
5. Start service: `python main.py start`

### Regular Maintenance
- Monitor log files for errors
- Check disk space in `logs/` directory
- Verify service status regularly
- Update Python and dependencies periodically

## 📞 Getting Help

### Log Analysis
1. Check `logs/enhanced_connector.log` for main errors
2. Check `logs/laravel_api.log` for API issues
3. Enable DEBUG logging for detailed troubleshooting

### Support Checklist
Before contacting support, gather:
- Operating system version
- Python version
- Contents of `config.json` (remove passwords)
- Recent log files
- Description of the issue
- Steps to reproduce

### Contact Information
- **Email**: support@jofotara.com
- **Documentation**: Full docs in Laravel application
- **Emergency**: Contact your system administrator

---

**🎉 Congratulations!** You now have the JoFotara Universal POS Connector installed and running. The connector will automatically detect and sync with your POS systems, creating invoices in the JoFotara system.
