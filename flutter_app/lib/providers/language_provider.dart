import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class LanguageProvider extends ChangeNotifier {
  final SharedPreferences _prefs;

  Locale _currentLocale = const Locale('ar', 'JO');
  bool _isRTL = true;

  LanguageProvider(this._prefs);

  Locale get currentLocale => _currentLocale;
  bool get isRTL => _isRTL;
  String get currentLanguageCode => _currentLocale.languageCode;

  // Load saved language preference
  Future<void> loadLanguage() async {
    final savedLanguage = _prefs.getString(AppConstants.languageKey);
    if (savedLanguage != null) {
      await setLanguage(savedLanguage);
    }
  }

  // Set language
  Future<void> setLanguage(String languageCode) async {
    switch (languageCode) {
      case 'ar':
        _currentLocale = const Locale('ar', 'JO');
        _isRTL = true;
        break;
      case 'en':
        _currentLocale = const Locale('en', 'US');
        _isRTL = false;
        break;
      default:
        _currentLocale = const Locale('ar', 'JO');
        _isRTL = true;
    }

    await _prefs.setString(AppConstants.languageKey, languageCode);
    notifyListeners();
  }

  // Toggle between Arabic and English
  Future<void> toggleLanguage() async {
    final newLanguage = _currentLocale.languageCode == 'ar' ? 'en' : 'ar';
    await setLanguage(newLanguage);
  }

  // Get available languages
  List<Map<String, String>> get availableLanguages => [
    {'code': 'ar', 'name': 'العربية', 'flag': '🇯🇴'},
    {'code': 'en', 'name': 'English', 'flag': '🇺🇸'},
  ];

  // Get current language info
  Map<String, String> get currentLanguageInfo {
    return availableLanguages.firstWhere(
      (lang) => lang['code'] == _currentLocale.languageCode,
      orElse: () => availableLanguages.first,
    );
  }
}
