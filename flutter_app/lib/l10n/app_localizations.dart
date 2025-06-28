import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

class AppLocalizations {
  AppLocalizations(this.locale);

  final Locale locale;

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate = _AppLocalizationsDelegate();

  static final Map<String, Map<String, String>> _localizedValues = {
    'en': {
      'appTitle': 'JoFotara',
      'appSubtitle': 'E-Invoicing System',
      'loginTitle': 'Login to Your Account',
      'email': 'Email',
      'emailHint': 'Enter your email address',
      'emailRequired': 'Email is required',
      'emailInvalid': 'Please enter a valid email address',
      'password': 'Password',
      'passwordHint': 'Enter your password',
      'passwordRequired': 'Password is required',
      'passwordTooShort': 'Password must be at least 6 characters',
      'rememberMe': 'Remember me',
      'loginButton': 'Login',
      'forgotPassword': 'Forgot Password?',
      'dontHaveAccount': "Don't have an account?",
      'register': 'Register',
      'error': 'Error',
      'ok': 'OK',
      'cancel': 'Cancel',
      'save': 'Save',
      'delete': 'Delete',
      'edit': 'Edit',
      'view': 'View',
      'print': 'Print',
      'share': 'Share',
      'featureComingSoon': 'This feature is coming soon',
      // Dashboard
      'dashboard': 'Dashboard',
      'welcome': 'Welcome',
      'totalInvoices': 'Total Invoices',
      'submittedInvoices': 'Submitted',
      'rejectedInvoices': 'Rejected',
      'draftInvoices': 'Draft',
      'paidInvoices': 'Paid',
      'recentInvoices': 'Recent Invoices',
      'createInvoice': 'Create Invoice',
      'searchInvoices': 'Search invoices...',
      'noInvoicesFound': 'No invoices found',
      // Invoice
      'invoiceNumber': 'Invoice Number',
      'invoiceDate': 'Invoice Date',
      'dueDate': 'Due Date',
      'customerName': 'Customer Name',
      'customerEmail': 'Customer Email',
      'customerPhone': 'Customer Phone',
      'customerAddress': 'Customer Address',
      'customerTaxNumber': 'Customer Tax Number',
      'status': 'Status',
      'paymentStatus': 'Payment Status',
      'currency': 'Currency',
      'items': 'Items',
      'description': 'Description',
      'quantity': 'Quantity',
      'unitPrice': 'Unit Price',
      'tax': 'Tax (%)',
      'total': 'Total',
      'subtotal': 'Subtotal',
      'taxAmount': 'Tax Amount',
      'totalAmount': 'Total Amount',
      'addItem': 'Add Item',
      'removeItem': 'Remove Item',
      'submitToJoFotara': 'Submit to JoFotara',
      'downloadPdf': 'Download PDF',
      'printInvoice': 'Print Invoice',
      // Status values
      'draft': 'Draft',
      'submitted': 'Submitted',
      'rejected': 'Rejected',
      'paid': 'Paid',
      'pending': 'Pending',
      'overdue': 'Overdue',
      // Settings
      'settings': 'Settings',
      'profile': 'Profile',
      'language': 'Language',
      'printerSettings': 'Printer Settings',
      'logout': 'Logout',
      'about': 'About',
      // Printer
      'selectPrinter': 'Select Printer',
      'bluetoothPrinter': 'Bluetooth Printer',
      'networkPrinter': 'Network Printer',
      'scanForPrinters': 'Scan for Printers',
      'connectPrinter': 'Connect Printer',
      'disconnectPrinter': 'Disconnect Printer',
      'printerConnected': 'Printer Connected',
      'noPrinterSelected': 'No Printer Selected',
      'printingInProgress': 'Printing in progress...',
      'printSuccess': 'Print successful',
      'printFailed': 'Print failed',
      // Messages
      'invoiceCreated': 'Invoice created successfully',
      'invoiceUpdated': 'Invoice updated successfully',
      'invoiceSubmitted': 'Invoice submitted successfully',
      'invoiceDeleted': 'Invoice deleted successfully',
      'connectionError': 'Connection error. Please check your internet connection.',
      'serverError': 'Server error. Please try again later.',
      'validationError': 'Please fill in all required fields.',
      'confirmDelete': 'Are you sure you want to delete this invoice?',
      'confirmLogout': 'Are you sure you want to logout?',
    },
    'ar': {
      'appTitle': 'الفوترة الإلكترونية',
      'appSubtitle': 'نظام الفوترة الإلكترونية الأردني',
      'loginTitle': 'تسجيل الدخول إلى حسابك',
      'email': 'البريد الإلكتروني',
      'emailHint': 'أدخل عنوان بريدك الإلكتروني',
      'emailRequired': 'البريد الإلكتروني مطلوب',
      'emailInvalid': 'يرجى إدخال عنوان بريد إلكتروني صحيح',
      'password': 'كلمة المرور',
      'passwordHint': 'أدخل كلمة المرور',
      'passwordRequired': 'كلمة المرور مطلوبة',
      'passwordTooShort': 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
      'rememberMe': 'تذكرني',
      'loginButton': 'تسجيل الدخول',
      'forgotPassword': 'نسيت كلمة المرور؟',
      'dontHaveAccount': 'ليس لديك حساب؟',
      'register': 'إنشاء حساب',
      'error': 'خطأ',
      'ok': 'موافق',
      'cancel': 'إلغاء',
      'save': 'حفظ',
      'delete': 'حذف',
      'edit': 'تعديل',
      'view': 'عرض',
      'print': 'طباعة',
      'share': 'مشاركة',
      'featureComingSoon': 'هذه الميزة قادمة قريباً',
      // Dashboard
      'dashboard': 'لوحة التحكم',
      'welcome': 'مرحباً',
      'totalInvoices': 'إجمالي الفواتير',
      'submittedInvoices': 'مرسلة',
      'rejectedInvoices': 'مرفوضة',
      'draftInvoices': 'مسودة',
      'paidInvoices': 'مدفوعة',
      'recentInvoices': 'الفواتير الحديثة',
      'createInvoice': 'إنشاء فاتورة',
      'searchInvoices': 'البحث في الفواتير...',
      'noInvoicesFound': 'لم يتم العثور على فواتير',
      // Invoice
      'invoiceNumber': 'رقم الفاتورة',
      'invoiceDate': 'تاريخ الفاتورة',
      'dueDate': 'تاريخ الاستحقاق',
      'customerName': 'اسم العميل',
      'customerEmail': 'بريد العميل الإلكتروني',
      'customerPhone': 'هاتف العميل',
      'customerAddress': 'عنوان العميل',
      'customerTaxNumber': 'الرقم الضريبي للعميل',
      'status': 'الحالة',
      'paymentStatus': 'حالة الدفع',
      'currency': 'العملة',
      'items': 'العناصر',
      'description': 'الوصف',
      'quantity': 'الكمية',
      'unitPrice': 'سعر الوحدة',
      'tax': 'الضريبة (%)',
      'total': 'المجموع',
      'subtotal': 'المجموع الفرعي',
      'taxAmount': 'مبلغ الضريبة',
      'totalAmount': 'المبلغ الإجمالي',
      'addItem': 'إضافة عنصر',
      'removeItem': 'إزالة العنصر',
      'submitToJoFotara': 'إرسال لنظام الفوترة',
      'downloadPdf': 'تحميل PDF',
      'printInvoice': 'طباعة الفاتورة',
      // Status values
      'draft': 'مسودة',
      'submitted': 'مرسلة',
      'rejected': 'مرفوضة',
      'paid': 'مدفوعة',
      'pending': 'في الانتظار',
      'overdue': 'متأخرة',
      // Settings
      'settings': 'الإعدادات',
      'profile': 'الملف الشخصي',
      'language': 'اللغة',
      'printerSettings': 'إعدادات الطابعة',
      'logout': 'تسجيل الخروج',
      'about': 'حول التطبيق',
      // Printer
      'selectPrinter': 'اختر طابعة',
      'bluetoothPrinter': 'طابعة بلوتوث',
      'networkPrinter': 'طابعة شبكة',
      'scanForPrinters': 'البحث عن طابعات',
      'connectPrinter': 'ربط الطابعة',
      'disconnectPrinter': 'قطع الاتصال',
      'printerConnected': 'الطابعة متصلة',
      'noPrinterSelected': 'لم يتم اختيار طابعة',
      'printingInProgress': 'جاري الطباعة...',
      'printSuccess': 'تمت الطباعة بنجاح',
      'printFailed': 'فشلت الطباعة',
      // Messages
      'invoiceCreated': 'تم إنشاء الفاتورة بنجاح',
      'invoiceUpdated': 'تم تحديث الفاتورة بنجاح',
      'invoiceSubmitted': 'تم إرسال الفاتورة بنجاح',
      'invoiceDeleted': 'تم حذف الفاتورة بنجاح',
      'connectionError': 'خطأ في الاتصال. يرجى التحقق من اتصال الإنترنت.',
      'serverError': 'خطأ في الخادم. يرجى المحاولة مرة أخرى لاحقاً.',
      'validationError': 'يرجى ملء جميع الحقول المطلوبة.',
      'confirmDelete': 'هل أنت متأكد من حذف هذه الفاتورة؟',
      'confirmLogout': 'هل أنت متأكد من تسجيل الخروج؟',
    },
  };

  String? get appTitle => _localizedValues[locale.languageCode]?['appTitle'];
  String? get appSubtitle => _localizedValues[locale.languageCode]?['appSubtitle'];
  String? get loginTitle => _localizedValues[locale.languageCode]?['loginTitle'];
  String? get email => _localizedValues[locale.languageCode]?['email'];
  String? get emailHint => _localizedValues[locale.languageCode]?['emailHint'];
  String? get emailRequired => _localizedValues[locale.languageCode]?['emailRequired'];
  String? get emailInvalid => _localizedValues[locale.languageCode]?['emailInvalid'];
  String? get password => _localizedValues[locale.languageCode]?['password'];
  String? get passwordHint => _localizedValues[locale.languageCode]?['passwordHint'];
  String? get passwordRequired => _localizedValues[locale.languageCode]?['passwordRequired'];
  String? get passwordTooShort => _localizedValues[locale.languageCode]?['passwordTooShort'];
  String? get rememberMe => _localizedValues[locale.languageCode]?['rememberMe'];
  String? get loginButton => _localizedValues[locale.languageCode]?['loginButton'];
  String? get forgotPassword => _localizedValues[locale.languageCode]?['forgotPassword'];
  String? get dontHaveAccount => _localizedValues[locale.languageCode]?['dontHaveAccount'];
  String? get register => _localizedValues[locale.languageCode]?['register'];
  String? get error => _localizedValues[locale.languageCode]?['error'];
  String? get ok => _localizedValues[locale.languageCode]?['ok'];
  String? get cancel => _localizedValues[locale.languageCode]?['cancel'];
  String? get save => _localizedValues[locale.languageCode]?['save'];
  String? get delete => _localizedValues[locale.languageCode]?['delete'];
  String? get edit => _localizedValues[locale.languageCode]?['edit'];
  String? get view => _localizedValues[locale.languageCode]?['view'];
  String? get print => _localizedValues[locale.languageCode]?['print'];
  String? get share => _localizedValues[locale.languageCode]?['share'];
  String? get featureComingSoon => _localizedValues[locale.languageCode]?['featureComingSoon'];

  // Dashboard
  String? get dashboard => _localizedValues[locale.languageCode]?['dashboard'];
  String? get welcome => _localizedValues[locale.languageCode]?['welcome'];
  String? get totalInvoices => _localizedValues[locale.languageCode]?['totalInvoices'];
  String? get submittedInvoices => _localizedValues[locale.languageCode]?['submittedInvoices'];
  String? get rejectedInvoices => _localizedValues[locale.languageCode]?['rejectedInvoices'];
  String? get draftInvoices => _localizedValues[locale.languageCode]?['draftInvoices'];
  String? get paidInvoices => _localizedValues[locale.languageCode]?['paidInvoices'];
  String? get recentInvoices => _localizedValues[locale.languageCode]?['recentInvoices'];
  String? get createInvoice => _localizedValues[locale.languageCode]?['createInvoice'];
  String? get searchInvoices => _localizedValues[locale.languageCode]?['searchInvoices'];
  String? get noInvoicesFound => _localizedValues[locale.languageCode]?['noInvoicesFound'];

  // Invoice
  String? get invoiceNumber => _localizedValues[locale.languageCode]?['invoiceNumber'];
  String? get invoiceDate => _localizedValues[locale.languageCode]?['invoiceDate'];
  String? get dueDate => _localizedValues[locale.languageCode]?['dueDate'];
  String? get customerName => _localizedValues[locale.languageCode]?['customerName'];
  String? get customerEmail => _localizedValues[locale.languageCode]?['customerEmail'];
  String? get customerPhone => _localizedValues[locale.languageCode]?['customerPhone'];
  String? get customerAddress => _localizedValues[locale.languageCode]?['customerAddress'];
  String? get customerTaxNumber => _localizedValues[locale.languageCode]?['customerTaxNumber'];
  String? get status => _localizedValues[locale.languageCode]?['status'];
  String? get paymentStatus => _localizedValues[locale.languageCode]?['paymentStatus'];
  String? get currency => _localizedValues[locale.languageCode]?['currency'];
  String? get items => _localizedValues[locale.languageCode]?['items'];
  String? get description => _localizedValues[locale.languageCode]?['description'];
  String? get quantity => _localizedValues[locale.languageCode]?['quantity'];
  String? get unitPrice => _localizedValues[locale.languageCode]?['unitPrice'];
  String? get tax => _localizedValues[locale.languageCode]?['tax'];
  String? get total => _localizedValues[locale.languageCode]?['total'];
  String? get subtotal => _localizedValues[locale.languageCode]?['subtotal'];
  String? get taxAmount => _localizedValues[locale.languageCode]?['taxAmount'];
  String? get totalAmount => _localizedValues[locale.languageCode]?['totalAmount'];
  String? get addItem => _localizedValues[locale.languageCode]?['addItem'];
  String? get removeItem => _localizedValues[locale.languageCode]?['removeItem'];
  String? get submitToJoFotara => _localizedValues[locale.languageCode]?['submitToJoFotara'];
  String? get downloadPdf => _localizedValues[locale.languageCode]?['downloadPdf'];
  String? get printInvoice => _localizedValues[locale.languageCode]?['printInvoice'];

  // Status values
  String? get draft => _localizedValues[locale.languageCode]?['draft'];
  String? get submitted => _localizedValues[locale.languageCode]?['submitted'];
  String? get rejected => _localizedValues[locale.languageCode]?['rejected'];
  String? get paid => _localizedValues[locale.languageCode]?['paid'];
  String? get pending => _localizedValues[locale.languageCode]?['pending'];
  String? get overdue => _localizedValues[locale.languageCode]?['overdue'];

  // Settings
  String? get settings => _localizedValues[locale.languageCode]?['settings'];
  String? get profile => _localizedValues[locale.languageCode]?['profile'];
  String? get language => _localizedValues[locale.languageCode]?['language'];
  String? get printerSettings => _localizedValues[locale.languageCode]?['printerSettings'];
  String? get logout => _localizedValues[locale.languageCode]?['logout'];
  String? get about => _localizedValues[locale.languageCode]?['about'];

  // Printer
  String? get selectPrinter => _localizedValues[locale.languageCode]?['selectPrinter'];
  String? get bluetoothPrinter => _localizedValues[locale.languageCode]?['bluetoothPrinter'];
  String? get networkPrinter => _localizedValues[locale.languageCode]?['networkPrinter'];
  String? get scanForPrinters => _localizedValues[locale.languageCode]?['scanForPrinters'];
  String? get connectPrinter => _localizedValues[locale.languageCode]?['connectPrinter'];
  String? get disconnectPrinter => _localizedValues[locale.languageCode]?['disconnectPrinter'];
  String? get printerConnected => _localizedValues[locale.languageCode]?['printerConnected'];
  String? get noPrinterSelected => _localizedValues[locale.languageCode]?['noPrinterSelected'];
  String? get printingInProgress => _localizedValues[locale.languageCode]?['printingInProgress'];
  String? get printSuccess => _localizedValues[locale.languageCode]?['printSuccess'];
  String? get printFailed => _localizedValues[locale.languageCode]?['printFailed'];

  // Messages
  String? get invoiceCreated => _localizedValues[locale.languageCode]?['invoiceCreated'];
  String? get invoiceUpdated => _localizedValues[locale.languageCode]?['invoiceUpdated'];
  String? get invoiceSubmitted => _localizedValues[locale.languageCode]?['invoiceSubmitted'];
  String? get invoiceDeleted => _localizedValues[locale.languageCode]?['invoiceDeleted'];
  String? get connectionError => _localizedValues[locale.languageCode]?['connectionError'];
  String? get serverError => _localizedValues[locale.languageCode]?['serverError'];
  String? get validationError => _localizedValues[locale.languageCode]?['validationError'];
  String? get confirmDelete => _localizedValues[locale.languageCode]?['confirmDelete'];
  String? get confirmLogout => _localizedValues[locale.languageCode]?['confirmLogout'];
}

class _AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) {
    return ['en', 'ar'].contains(locale.languageCode);
  }

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(AppLocalizations(locale));
  }

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}
