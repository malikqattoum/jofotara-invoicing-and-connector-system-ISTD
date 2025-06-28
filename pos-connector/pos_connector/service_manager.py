#!/usr/bin/env python3
"""
Windows Service Manager for JoFotara POS Connector
"""

import os
import sys
import win32serviceutil
import win32service
import win32event
import servicemanager
import time
import threading
import asyncio
from pathlib import Path
import json
import logging
from typing import Dict, Any

class WindowsServiceManager:
    """Manager for Windows service operations"""

    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.service_name = "JoFotaraPOSConnector"
        self.service_display_name = "JoFotara POS Connector Service"
        self.service_description = "Universal POS connector for JoFotara invoicing system"

    def install_service(self):
        """Install the service"""
        try:
            # Get the Python executable and script path
            python_exe = sys.executable
            script_path = os.path.abspath(__file__)

            # Create service command
            service_command = f'"{python_exe}" "{script_path}" service'

            # Install service
            win32serviceutil.InstallService(
                JoFotaraService,
                self.service_name,
                self.service_display_name,
                description=self.service_description,
                exeArgs=service_command
            )

            print(f"Service '{self.service_display_name}' installed successfully")

        except Exception as e:
            print(f"Failed to install service: {e}")
            raise

    def uninstall_service(self):
        """Uninstall the service"""
        try:
            win32serviceutil.RemoveService(self.service_name)
            print(f"Service '{self.service_display_name}' uninstalled successfully")
        except Exception as e:
            print(f"Failed to uninstall service: {e}")
            raise

    def start_service(self):
        """Start the service"""
        try:
            win32serviceutil.StartService(self.service_name)
            print(f"Service '{self.service_display_name}' started successfully")
        except Exception as e:
            print(f"Failed to start service: {e}")
            raise

    def stop_service(self):
        """Stop the service"""
        try:
            win32serviceutil.StopService(self.service_name)
            print(f"Service '{self.service_display_name}' stopped successfully")
        except Exception as e:
            print(f"Failed to stop service: {e}")
            raise

    def restart_service(self):
        """Restart the service"""
        try:
            self.stop_service()
            time.sleep(2)
            self.start_service()
        except Exception as e:
            print(f"Failed to restart service: {e}")
            raise

    def get_service_status(self):
        """Get service status"""
        try:
            import win32service
            scm = win32service.OpenSCManager(None, None, win32service.SC_MANAGER_ALL_ACCESS)
            service_handle = win32service.OpenService(scm, self.service_name, win32service.SERVICE_ALL_ACCESS)
            status = win32service.QueryServiceStatus(service_handle)
            win32service.CloseServiceHandle(service_handle)
            win32service.CloseServiceHandle(scm)

            status_map = {
                win32service.SERVICE_STOPPED: 'Stopped',
                win32service.SERVICE_START_PENDING: 'Start Pending',
                win32service.SERVICE_STOP_PENDING: 'Stop Pending',
                win32service.SERVICE_RUNNING: 'Running',
                win32service.SERVICE_CONTINUE_PENDING: 'Continue Pending',
                win32service.SERVICE_PAUSE_PENDING: 'Pause Pending',
                win32service.SERVICE_PAUSED: 'Paused'
            }

            return status_map.get(status[1], 'Unknown')

        except Exception as e:
            return f"Error: {e}"

class JoFotaraService(win32serviceutil.ServiceFramework):
    """Windows service implementation"""

    _svc_name_ = "JoFotaraPOSConnector"
    _svc_display_name_ = "JoFotara POS Connector Service"
    _svc_description_ = "Universal POS connector for JoFotara invoicing system"

    def __init__(self, args):
        win32serviceutil.ServiceFramework.__init__(self, args)
        self.hWaitStop = win32event.CreateEvent(None, 0, 0, None)
        self.running = True
        self.connector = None

        # Setup logging
        self._setup_service_logging()
        self.logger = logging.getLogger('JoFotaraService')

    def _setup_service_logging(self):
        """Setup logging for the service"""
        log_dir = Path(__file__).parent.parent / 'logs'
        log_dir.mkdir(exist_ok=True)

        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(log_dir / 'service.log'),
                logging.StreamHandler()
            ]
        )

    def SvcStop(self):
        """Stop the service"""
        self.logger.info("Service stop requested")
        self.ReportServiceStatus(win32service.SERVICE_STOP_PENDING)
        self.running = False

        if self.connector:
            try:
                self.connector.stop_monitoring()
            except Exception as e:
                self.logger.error(f"Error stopping connector: {e}")

        win32event.SetEvent(self.hWaitStop)
        self.logger.info("Service stopped")

    def SvcDoRun(self):
        """Main service execution"""
        self.logger.info("JoFotara POS Connector Service starting")

        try:
            # Load configuration
            config = self._load_config()

            # Import and initialize the enhanced connector
            from .enhanced_connector import EnhancedPOSConnector

            self.connector = EnhancedPOSConnector(config)

            # Start monitoring in a separate thread
            monitor_thread = threading.Thread(target=self._run_monitoring)
            monitor_thread.daemon = True
            monitor_thread.start()

            self.logger.info("Service started successfully")

            # Wait for stop signal
            win32event.WaitForSingleObject(self.hWaitStop, win32event.INFINITE)

        except Exception as e:
            self.logger.error(f"Service error: {e}")
            self.SvcStop()

    def _load_config(self) -> Dict[str, Any]:
        """Load configuration from config file"""
        config_file = Path(__file__).parent.parent / 'config.json'

        if config_file.exists():
            try:
                with open(config_file, 'r') as f:
                    return json.load(f)
            except Exception as e:
                self.logger.error(f"Error loading config: {e}")

        # Return default config
        return {
            'base_url': 'http://localhost:8000',
            'email': '',
            'password': '',
            'sync_interval': 60,
            'auto_submit_jofotara': False
        }

    def _run_monitoring(self):
        """Run the monitoring loop"""
        try:
            # Create new event loop for this thread
            loop = asyncio.new_event_loop()
            asyncio.set_event_loop(loop)

            # Start monitoring
            loop.run_until_complete(self.connector.start_monitoring())

            # Keep running until service is stopped
            while self.running:
                time.sleep(1)

                # Check connector health
                if hasattr(self.connector, 'get_status'):
                    status = self.connector.get_status()
                    if not status.get('running', False):
                        self.logger.warning("Connector stopped running, attempting restart")
                        try:
                            loop.run_until_complete(self.connector.start_monitoring())
                        except Exception as e:
                            self.logger.error(f"Failed to restart connector: {e}")

        except Exception as e:
            self.logger.error(f"Monitoring thread error: {e}")
        finally:
            if hasattr(self, 'connector') and self.connector:
                try:
                    self.connector.stop_monitoring()
                except Exception as e:
                    self.logger.error(f"Error stopping connector: {e}")

# Service control functions
def install_service():
    """Install the Windows service"""
    try:
        win32serviceutil.InstallService(
            JoFotaraService,
            JoFotaraService._svc_name_,
            JoFotaraService._svc_display_name_,
            description=JoFotaraService._svc_description_
        )
        print("Service installed successfully")
    except Exception as e:
        print(f"Failed to install service: {e}")

def uninstall_service():
    """Uninstall the Windows service"""
    try:
        win32serviceutil.RemoveService(JoFotaraService._svc_name_)
        print("Service uninstalled successfully")
    except Exception as e:
        print(f"Failed to uninstall service: {e}")

def start_service():
    """Start the Windows service"""
    try:
        win32serviceutil.StartService(JoFotaraService._svc_name_)
        print("Service started successfully")
    except Exception as e:
        print(f"Failed to start service: {e}")

def stop_service():
    """Stop the Windows service"""
    try:
        win32serviceutil.StopService(JoFotaraService._svc_name_)
        print("Service stopped successfully")
    except Exception as e:
        print(f"Failed to stop service: {e}")

if __name__ == '__main__':
    if len(sys.argv) == 1:
        servicemanager.Initialize()
        servicemanager.PrepareToHostSingle(JoFotaraService)
        servicemanager.StartServiceCtrlDispatcher()
    else:
        win32serviceutil.HandleCommandLine(JoFotaraService)
