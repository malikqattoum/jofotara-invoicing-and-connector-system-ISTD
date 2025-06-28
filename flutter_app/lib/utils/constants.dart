class AppConstants {
  // API Configuration
  static const String baseUrl = 'http://your-domain.com/api'; // Replace with your Laravel API URL

  // API Endpoints
  static const String loginEndpoint = '/vendors/login';
  static const String profileEndpoint = '/vendors/profile';
  static const String invoicesEndpoint = '/invoices';
  static const String submitInvoiceEndpoint = '/invoices/{id}/submit';

  // Local Storage Keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String languageKey = 'selected_language';

  // App Settings
  static const String appName = 'JoFotara Invoicing';
  static const String appVersion = '1.0.0';

  // Invoice Status
  static const String statusDraft = 'draft';
  static const String statusSubmitted = 'submitted';
  static const String statusRejected = 'rejected';
  static const String statusPaid = 'paid';

  // Payment Status
  static const String paymentPending = 'pending';
  static const String paymentPaid = 'paid';
  static const String paymentOverdue = 'overdue';

  // Printer Types
  static const String printerBluetooth = 'bluetooth';
  static const String printerNetwork = 'network';
  static const String printerUSB = 'usb';

  // Default Values
  static const int defaultTimeout = 30000;
  static const String defaultCurrency = 'JOD';
  static const String defaultLanguage = 'ar';
}

class AppColors {
  static const primaryColor = Color(0xFF2E7D32);
  static const primaryLight = Color(0xFF60AD5E);
  static const primaryDark = Color(0xFF005005);
  static const secondary = Color(0xFF1976D2);
  static const accent = Color(0xFFFF6F00);
  static const error = Color(0xFFD32F2F);
  static const warning = Color(0xFFF57C00);
  static const success = Color(0xFF388E3C);
  static const info = Color(0xFF1976D2);
}

class AppSizes {
  static const double paddingSmall = 8.0;
  static const double paddingMedium = 16.0;
  static const double paddingLarge = 24.0;
  static const double paddingXLarge = 32.0;

  static const double radiusSmall = 4.0;
  static const double radiusMedium = 8.0;
  static const double radiusLarge = 12.0;
  static const double radiusXLarge = 16.0;

  static const double iconSmall = 16.0;
  static const double iconMedium = 24.0;
  static const double iconLarge = 32.0;
  static const double iconXLarge = 48.0;
}
