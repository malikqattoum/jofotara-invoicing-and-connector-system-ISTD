# JoFotara Invoicing System Information

## Summary
JoFotara Invoicing is a comprehensive e-invoicing solution that connects point-of-sale (POS) systems with electronic invoicing services. The system includes a Laravel-based backend, a Python POS connector, and a Flutter mobile application, providing a complete ecosystem for automated invoice processing.

## Structure
- **app/**: Core Laravel application code (controllers, models, services)
- **pos-connector/**: Python-based POS system connector for data extraction
- **flutter_app/**: Mobile application for invoice management
- **resources/**: Frontend assets, views, and components
- **database/**: Database migrations, seeders, and factories
- **config/**: Application configuration files
- **routes/**: API and web route definitions
- **tests/**: Unit and feature tests

## Projects

### Laravel Backend
**Configuration File**: composer.json

#### Language & Runtime
**Language**: PHP
**Version**: 8.2
**Framework**: Laravel 12.19.3
**Package Manager**: Composer

#### Dependencies
**Main Dependencies**:
- laravel/framework: ^12.0
- jafar-albadarneh/jofotara: ^0.7.0
- barryvdh/laravel-dompdf: ^3.1
- quickbooks/v3-php-sdk: ^6.2
- xeroapi/xero-php-oauth2: ^9.2

**Development Dependencies**:
- phpunit/phpunit: ^11.5.3
- laravel/pint: ^1.13
- fakerphp/faker: ^1.23

#### Build & Installation
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

#### Testing
**Framework**: PHPUnit
**Test Location**: tests/
**Configuration**: phpunit.xml
**Run Command**:
```bash
php artisan test
```

### Frontend Assets
**Configuration File**: package.json

#### Language & Runtime
**Language**: JavaScript
**Build System**: Vite
**Package Manager**: npm

#### Dependencies
**Development Dependencies**:
- vite: ^5.2.0
- tailwindcss: ^4.0.0
- vue: ^3.2.37
- bootstrap: ^5.2.3

#### Build & Installation
```bash
npm install
npm run build
```

### POS Connector
**Configuration File**: requirements.txt

#### Language & Runtime
**Language**: Python
**Version**: 3.x
**Package Manager**: pip

#### Dependencies
**Main Dependencies**:
- requests: >=2.31.0
- pandas: >=2.0.0
- pywin32: >=306
- lxml: >=4.9.3
- database drivers (pyodbc, pymysql, psycopg2)

#### Build & Installation
```bash
cd pos-connector
pip install -r requirements.txt
python main.py
```

### Flutter Mobile App
**Configuration File**: pubspec.yaml

#### Language & Runtime
**Language**: Dart
**SDK**: >=3.0.0 <4.0.0
**Framework**: Flutter >=3.10.0
**Package Manager**: pub

#### Dependencies
**Main Dependencies**:
- flutter: sdk
- provider: ^6.1.1
- http: ^1.1.0
- dio: ^5.3.2
- printing: ^5.12.0
- qr_flutter: ^4.1.0

#### Build & Installation
```bash
cd flutter_app
flutter pub get
flutter run
```

## Key Features
- Universal POS system compatibility
- Real-time transaction processing
- Automated invoice creation
- Multi-business type support (Restaurant, Retail, Medical)
- API-based integration
- Mobile application for on-the-go management
- Enterprise-grade security with API key authentication
