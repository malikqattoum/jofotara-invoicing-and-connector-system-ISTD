# 🌍 JoFotara E-Invoicing System with Universal POS Connector

A comprehensive Laravel-based e-invoicing system that integrates with Jordan's JoFotara e-invoicing platform and provides universal connectors for both POS systems and major ERP platforms (SAP, Microsoft Dynamics, QuickBooks, Oracle, etc.).

## 🎯 System Overview

The **JoFotara E-Invoicing System** is a complete business solution that enables seamless integration between any business system (POS, ERP, Accounting) and Jordan's mandatory e-invoicing platform. The system provides:

- **Universal POS Integration** - Works with ANY POS system through our connector
- **ERP System Integration** - Direct connectors to major ERP platforms including SAP, Microsoft Dynamics, QuickBooks, Oracle, and more
- **JoFotara Compliance** - Full integration with Jordan's e-invoicing platform
- **Mobile App Support** - Flutter mobile application for on-the-go management
- **Admin Dashboard** - Complete system monitoring and management
- **Real-time Processing** - Automatic transaction detection and invoice generation

## 🏗️ System Architecture

### Core Components

1. **Laravel Backend** - Main application server with API endpoints and ERP connectors
2. **Universal POS Connector** - Standalone application that connects to any POS system
3. **ERP Connectors** - Dedicated connectors for major ERP platforms (SAP, Dynamics, QuickBooks, Oracle)
4. **Flutter Mobile App** - Mobile application for vendors and customers
5. **JoFotara Integration** - Direct integration with Jordan's e-invoicing system

### Data Flow

```
POS System → Universal Connector → Laravel API → JoFotara Platform
    ↓              ↓                ↓           ↓
  Detects      Standardizes      Processes   Submits
Transactions     Format          & Validates  Invoices

ERP System → ERP Connector → Laravel API → JoFotara Platform
    ↓            ↓              ↓           ↓
  Extracts    Transforms     Processes   Submits
Financial    Data Format    & Validates  Invoices
```

## 🚀 Key Features

### Universal POS Connector
- 🔌 **Universal Compatibility** - Works with ANY POS system (Restaurant, Retail, Medical, Automotive, Beauty, Professional)
- 🚀 **Real-time Sync** - Automatic transaction detection and processing
- 📊 **Auto Invoicing** - Automatic invoice creation from POS transactions
- 🔒 **Secure API** - API key-based authentication
- 📦 **Easy Distribution** - Generate custom installer packages for customers

### ERP System Integration
- 🏢 **Major ERP Support** - Direct integration with SAP, Microsoft Dynamics, QuickBooks, Oracle, and more
- 🔄 **Real-time Sync** - Automatic financial data extraction and invoice generation
- 📊 **Financial Integration** - Leverages existing ERP financial data and customer information
- 🔒 **Secure API** - OAuth and API key-based authentication for each ERP platform
- 📋 **Data Mapping** - Flexible data mapping to accommodate different ERP structures

### JoFotara Integration
- 🇯🇴 **Jordan Compliance** - Full compliance with Jordan Tax Authority requirements
- 📄 **UBL 2.1 Standard** - Generates valid XML according to international standards
- 🔐 **Secure Authentication** - OAuth2-based authentication with JoFotara
- 📊 **Multiple Invoice Types** - Support for sales, income, credit invoices
- 💰 **Automatic Calculations** - Built-in tax and total calculations

### Mobile Application
- 📱 **Cross-platform** - Works on Android and iOS
- 🌐 **Bilingual** - Full Arabic (RTL) and English (LTR) support
- 🖨️ **Printer Support** - Bluetooth, Network, USB, and PDF printing
- 📊 **Dashboard** - Real-time statistics and invoice tracking
- 🔐 **Secure Login** - JWT-based authentication

### Admin Dashboard
- 📈 **Real-time Monitoring** - Live connector status and transaction tracking
- 👥 **Customer Management** - Complete POS customer lifecycle management
- 📊 **Analytics** - Comprehensive business intelligence and reporting
- 🔧 **System Configuration** - Flexible system-wide settings
- 📋 **Transaction Management** - Full transaction history and processing

## 🛠️ Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Node.js 18 or higher
- Composer
- NPM

### Backend Setup

1. **Clone the repository**
   ```bash
   git clone https://your-repo-url.git
   cd jo-invoicing
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=InvoiceSeeder
   ```

5. **Configure JoFotara integration**
   ```bash
   # Set JoFotara credentials in .env
   JOFOTARA_CLIENT_ID=your_client_id
   JOFOTARA_SECRET_KEY=your_secret_key
   JOFOTARA_INCOME_SOURCE_SEQUENCE=your_sequence
   JOFOTARA_ENVIRONMENT_URL=https://api.jofotara.jo
   ```

6. **Run the application**
   ```bash
   php artisan serve
   ```

### Mobile App Setup

1. **Navigate to Flutter app**
   ```bash
   cd flutter_app
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

4. **Run the app**
   ```bash
   flutter run
   ```

## 🔌 API Endpoints

### Base URL: `/api`

| Endpoint | Method | Purpose | Authentication |
|----------|--------|---------|----------------|
| `/invoices` | GET | List invoices | Bearer Token |
| `/invoices` | POST | Create invoice | Bearer Token |
| `/invoices/{id}` | GET | Get invoice details | Bearer Token |
| `/invoices/{id}` | PUT | Update invoice | Bearer Token |
| `/invoices/{id}/submit` | POST | Submit to JoFotara | Bearer Token |
| `/invoices/{id}/print` | POST | Print invoice | Bearer Token |
| `/auth/login` | POST | Vendor login | None |
| `/auth/logout` | POST | Vendor logout | Bearer Token |
| `/dashboard/stats` | GET | Dashboard statistics | Bearer Token |
| `/pos-connector/transactions` | POST | Submit POS transactions | API Key |
| `/pos-connector/heartbeat` | POST | Connector status | API Key |

### Authentication
```
Header: Authorization: Bearer [token]
OR
Header: X-API-Key: [customer_api_key]
OR
Query: ?api_key=[customer_api_key]
```

## 📱 Supported Business Types

### POS Systems
| Business Type | POS Systems | Features |
|---------------|-------------|----------|
| **Restaurant** | Any restaurant POS | Tables, tips, employees, menu items |
| **Retail** | Any retail POS | Inventory, customers, products |
| **Medical** | Practice management | Patients, appointments, services |
| **Automotive** | Service shop POS | Vehicles, services, parts |
| **Beauty** | Salon/spa POS | Clients, services, appointments |
| **Professional** | Service business | Clients, billable hours, projects |

### ERP Systems
| ERP Platform | Integration Method | Features |
|--------------|-------------------|----------|
| **SAP** | Direct API connector | Financial data, customer master, material master |
| **Microsoft Dynamics** | API connector | Sales orders, customers, products, financials |
| **QuickBooks** | API connector | Invoices, customers, accounts, items |
| **Oracle** | API connector | Financials, customers, orders, projects |
| **Xero** | API connector | Invoices, contacts, accounts, tracking |
| **NetSuite** | API connector | Transactions, customers, items, custom records |

## 🖨️ Printer Support

### Supported Connection Types
- **Bluetooth** - ESC/POS thermal printers
- **Network** - WiFi/Ethernet printers
- **USB** - Direct USB connection (Android)
- **PDF** - System print dialog

### Supported Brands
- Epson (TM series, L series)
- Star Micronics
- Citizen
- Brother
- Canon
- HP
- Zebra
- Generic ESC/POS printers

## 🌐 Language Support

### Full Localization
- **العربية** - Complete RTL support with Arabic fonts
- **English** - LTR layout with international formatting
- **Currency** - JOD (Jordanian Dinar) formatting
- **Dates** - Localized date and time formatting

## 🔒 Security Features

### Authentication & Authorization
- JWT-based authentication for mobile app
- API key authentication for POS connector
- Role-based access control
- Secure token management with automatic refresh

### Data Protection
- SSL/TLS encryption for all API communications
- Secure storage of sensitive data
- Input validation and sanitization
- SQL injection prevention

## 📊 Monitoring & Analytics

### Real-time Dashboard
- Active connector monitoring
- Transaction processing statistics
- Error tracking and alerting
- Performance metrics

### Business Intelligence
- Revenue tracking and reporting
- Customer behavior analysis
- Transaction volume trends
- System usage analytics

## 🔧 Troubleshooting

### Common Issues

**POS Connector Not Connecting**
- Check API key validity
- Verify network connectivity
- Review debug logs in `storage/logs/pos-connector.log`

**JoFotara Integration Issues**
- Verify credentials in `.env` file
- Check internet connectivity
- Review JoFotara API response logs

**Mobile App Issues**
- Check API endpoint configuration
- Verify device permissions
- Clear app cache and data

### Debug Mode
Enable debug mode for detailed logging:
```bash
# In .env file
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

## 🚀 Deployment

### Production Deployment
1. Set up production database
2. Configure environment variables
3. Run migrations and seeders
4. Set up queue processing
5. Configure SSL certificates
6. Set up monitoring and logging

### POS Connector Distribution
1. Create customer in admin dashboard
2. Generate custom installer package
3. Provide package to customer
4. Customer installs connector on their system
5. Connector automatically detects and integrates with their POS

## 📈 Business Benefits

### For Businesses
- **Compliance** - Meets Jordan's mandatory e-invoicing requirements
- **Automation** - Eliminates manual data entry and invoice creation
- **Integration** - Works with existing POS systems and ERP platforms without replacement
- **Efficiency** - Reduces processing time and errors across all business systems
- **Insights** - Provides valuable business analytics from integrated data
- **Scalability** - Supports businesses of all sizes from small retail to large enterprises

### For Customers
- **Convenience** - Mobile access to invoices and business data
- **Flexibility** - Support for multiple printer types and business systems
- **Localization** - Native Arabic language support
- **Reliability** - Robust system with automatic recovery
- **Enterprise Integration** - Seamless connection to existing enterprise systems

## 🤝 Contributing

We welcome contributions to improve the system. Please follow these steps:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📞 Support

### For System Administrators
- **Documentation**: Comprehensive guides in `/docs` directory
- **API Testing**: Built-in test endpoints
- **Logs**: Detailed logging in `storage/logs/`
- **Community**: GitHub issues and discussions

### For End Users
- **Mobile App Help**: In-app help section
- **Printer Support**: Dedicated printer troubleshooting guide
- **Video Tutorials**: Coming soon
- **Email Support**: Available for enterprise customers

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🔄 Changelog

### Version 1.0.0
- Initial release
- Universal POS Connector with auto-invoicing
- ERP System Integration (SAP, Microsoft Dynamics, QuickBooks, Oracle)
- JoFotara SDK integration
- Flutter mobile app with printer support
- Admin dashboard for system management
- Multi-language support (Arabic/English)

---

## 🎉 Ready for Production

The JoFotara E-Invoicing System is now fully operational and ready for production deployment. The system provides unprecedented flexibility and automation for any business using any POS system or ERP platform while maintaining full compliance with Jordan's e-invoicing requirements.

**Key Features Ready:**
- ✅ Universal POS Connector
- ✅ ERP System Integration (SAP, Microsoft Dynamics, QuickBooks, Oracle)
- ✅ JoFotara Integration
- ✅ Mobile Application
- ✅ Admin Dashboard
- ✅ Multi-language Support
- ✅ Printer Integration
- ✅ Real-time Processing
- ✅ Security & Compliance

**Start transforming your business today!**
