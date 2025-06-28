# JoFotara E-Invoicing Flutter App

A comprehensive Flutter mobile application for the JoFotara (Jordan E-Invoicing) system with extensive printer support for all types of printers.

## Features

### Core Features
- 📱 **Multi-platform Support**: Works on both Android and iOS
- 🌐 **Bilingual**: Full support for Arabic (RTL) and English (LTR)
- 🔐 **Authentication**: Secure login with JWT tokens
- 📄 **Invoice Management**: Create, edit, submit, and track invoices
- 📊 **Dashboard**: Real-time statistics and recent invoices
- 🔄 **Sync**: Real-time synchronization with Laravel backend

### Printer Support
- 🖨️ **Bluetooth Printers**: ESC/POS thermal printers
- 🌐 **Network Printers**: WiFi/Ethernet ESC/POS printers
- 📄 **PDF Printing**: System print dialog for any printer
- 🔌 **USB Printers**: Direct USB connection (Android)
- 📱 **Receipt Formatting**: Optimized for thermal receipt printers
- 🔍 **Auto-discovery**: Automatic printer detection

### Supported Printer Brands
- Epson (TM series, L series)
- Star Micronics
- Citizen
- Brother
- Canon
- HP
- Zebra
- Generic ESC/POS printers

## Installation

### Prerequisites
- Flutter SDK (3.10.0 or higher)
- Dart SDK (3.0.0 or higher)
- Android Studio / Xcode
- Laravel backend API running

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://your-repo-url.git
   cd jo-invoicing/flutter_app
   ```

2. **Install dependencies**
   ```bash
   flutter pub get
   ```

3. **Configure API endpoint**
   Edit `lib/utils/constants.dart`:
   ```dart
   static const String baseUrl = 'https://your-api-domain.com/api';
   ```

4. **Android Setup**
   - Ensure minimum SDK version is 21
   - Bluetooth permissions are already configured
   - USB printer support enabled

5. **iOS Setup**
   - Minimum iOS version: 11.0
   - Bluetooth permissions configured
   - Network permissions enabled

6. **Run the app**
   ```bash
   flutter run
   ```

## Configuration

### API Configuration
Update the API endpoints in `lib/utils/constants.dart`:
```dart
class AppConstants {
  static const String baseUrl = 'https://your-domain.com/api';
  static const String loginEndpoint = '/vendors/login';
  static const String invoicesEndpoint = '/invoices';
  // ... other endpoints
}
```

### Printer Configuration
The app automatically detects and configures printers:
- **Bluetooth**: Scan and pair with nearby printers
- **Network**: Auto-discover printers on local network
- **USB**: Plug-and-play support on Android

## Usage

### Login
1. Enter your vendor credentials
2. Select preferred language (Arabic/English)
3. The app will sync with the server

### Creating Invoices
1. Tap "Create Invoice" from dashboard
2. Fill in customer details
3. Add invoice items with quantities and prices
4. Review totals (subtotal, tax, total)
5. Save as draft or submit directly

### Printing Invoices
1. Open invoice details
2. Tap "Print" button
3. Select printer type:
   - **Bluetooth**: Choose from paired printers
   - **Network**: Select from discovered printers
   - **PDF**: Use system print dialog

### Printer Setup
1. Go to Settings > Printer Settings
2. Choose printer type
3. For Bluetooth:
   - Tap "Scan for Printers"
   - Select your printer from the list
   - Pair and connect
4. For Network:
   - Ensure printer is on same network
   - Tap "Scan for Printers"
   - Or manually enter IP address

## Supported Printer Models

### Bluetooth ESC/POS Printers
- Epson TM-T20, TM-T82, TM-T88
- Star TSP100, TSP650, TSP700
- Citizen CT-S310, CT-S4000
- Brother RJ series
- Generic 58mm/80mm thermal printers

### Network Printers
- Any ESC/POS printer with WiFi/Ethernet
- Epson TM-T88VI, TM-T20III
- Star TSP100III, TSP650II
- Brother TD series
- Custom IP:Port configuration

### USB Printers (Android Only)
- Direct USB connection
- Supports most USB thermal printers
- Automatic driver detection

## Printing Features

### Receipt Format
- Company/store header
- Invoice number and date
- Customer information
- Itemized list with quantities and prices
- Tax calculations
- Total amount
- QR code (if available)
- Footer with thank you message

### Print Customization
- Logo printing support
- Multiple paper sizes (58mm, 80mm)
- Font size adjustments
- Language-specific formatting
- Arabic text support

## Language Support

### Arabic (العربية)
- Full RTL (Right-to-Left) support
- Arabic fonts (Cairo font family)
- Localized text and numbers
- Arabic date formatting
- Currency formatting (JOD)

### English
- LTR (Left-to-Right) layout
- International formatting
- Multi-currency support

## Permissions

### Android
- `BLUETOOTH` - Connect to Bluetooth printers
- `BLUETOOTH_ADMIN` - Manage Bluetooth connections
- `BLUETOOTH_CONNECT` - Android 12+ Bluetooth
- `BLUETOOTH_SCAN` - Android 12+ Bluetooth scanning
- `ACCESS_COARSE_LOCATION` - Required for Bluetooth scanning
- `INTERNET` - API communication
- `ACCESS_NETWORK_STATE` - Network printer discovery
- `CAMERA` - QR code scanning
- `WRITE_EXTERNAL_STORAGE` - PDF saving

### iOS
- `NSBluetoothAlwaysUsageDescription` - Bluetooth printer access
- `NSCameraUsageDescription` - QR code scanning
- `NSPhotoLibraryUsageDescription` - PDF saving
- `NSLocationWhenInUseUsageDescription` - Network discovery

## Troubleshooting

### Bluetooth Issues
1. **Printer not found**:
   - Ensure printer is in pairing mode
   - Check if Bluetooth is enabled
   - Grant location permissions (Android)

2. **Connection failed**:
   - Restart Bluetooth on device
   - Clear app cache
   - Try pairing from device settings first

### Network Printer Issues
1. **Printer not discovered**:
   - Ensure device and printer on same network
   - Check printer IP address
   - Manually add printer by IP

2. **Printing failed**:
   - Verify printer port (usually 9100)
   - Check network connectivity
   - Try different ESC/POS commands

### General Issues
1. **App crashes**:
   - Update to latest Flutter version
   - Clear app data
   - Check device compatibility

2. **API connection failed**:
   - Verify internet connection
   - Check API endpoint URL
   - Ensure backend is running

## Development

### Project Structure
```
lib/
├── main.dart                 # App entry point
├── l10n/                    # Localization files
├── models/                  # Data models
├── providers/               # State management
├── screens/                 # UI screens
├── services/                # API and printing services
├── utils/                   # Constants and utilities
└── widgets/                 # Reusable widgets
```

### Adding New Printer Support
1. Extend `PrinterProvider` class
2. Add printer-specific commands
3. Test with physical device
4. Update documentation

### Contributing
1. Fork the repository
2. Create feature branch
3. Add tests for new features
4. Submit pull request

## Support

### Printer Compatibility
If your printer is not working:
1. Check if it supports ESC/POS commands
2. Verify connection method (Bluetooth/Network/USB)
3. Test with manufacturer's app first
4. Contact support with printer model

### Getting Help
- Check the troubleshooting section
- Review printer manufacturer documentation
- Open an issue with details:
  - Device model and OS version
  - Printer model and connection type
  - Error messages or logs

## License
This project is licensed under the MIT License - see the LICENSE file for details.

## Changelog

### Version 1.0.0
- Initial release
- Full printer support (Bluetooth, Network, PDF, USB)
- Arabic and English localization
- JoFotara API integration
- Invoice management features
- Dashboard and statistics
