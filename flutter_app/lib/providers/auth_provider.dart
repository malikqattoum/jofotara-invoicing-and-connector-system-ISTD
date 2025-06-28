import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../models/user_model.dart';
import '../utils/constants.dart';

class AuthProvider extends ChangeNotifier {
  final SharedPreferences _prefs;
  final ApiService _apiService = ApiService();

  UserModel? _user;
  String? _token;
  bool _isLoading = false;
  String? _error;

  AuthProvider(this._prefs) {
    _loadUserData();
  }

  // Getters
  UserModel? get user => _user;
  String? get token => _token;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _token != null && _user != null;

  // Load user data from shared preferences
  void _loadUserData() {
    _token = _prefs.getString(AppConstants.tokenKey);
    final userData = _prefs.getString(AppConstants.userKey);

    if (userData != null) {
      try {
        final userJson = jsonDecode(userData);
        _user = UserModel.fromJson(userJson);
      } catch (e) {
        debugPrint('Error loading user data: $e');
        _clearUserData();
      }
    }

    if (_token != null) {
      _apiService.setAuthToken(_token!);
    }

    notifyListeners();
  }

  // Save user data to shared preferences
  Future<void> _saveUserData() async {
    if (_token != null) {
      await _prefs.setString(AppConstants.tokenKey, _token!);
    }
    if (_user != null) {
      await _prefs.setString(AppConstants.userKey, jsonEncode(_user!.toJson()));
    }
  }

  // Clear user data from shared preferences
  Future<void> _clearUserData() async {
    await _prefs.remove(AppConstants.tokenKey);
    await _prefs.remove(AppConstants.userKey);
    _token = null;
    _user = null;
    _apiService.clearAuthToken();
  }

  // Login method
  Future<bool> login(String email, String password) async {
    _setLoading(true);
    _error = null;

    try {
      final response = await _apiService.login(email, password);

      if (response['token'] != null && response['user'] != null) {
        _token = response['token'];
        _user = UserModel.fromJson(response['user']);

        _apiService.setAuthToken(_token!);
        await _saveUserData();

        _setLoading(false);
        notifyListeners();
        return true;
      } else {
        _error = 'Invalid response from server';
        _setLoading(false);
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _setLoading(false);
      return false;
    }
  }

  // Logout method
  Future<void> logout() async {
    _setLoading(true);

    try {
      // Call logout API if available
      // await _apiService.logout();
    } catch (e) {
      debugPrint('Error during logout: $e');
    }

    await _clearUserData();
    _setLoading(false);
    notifyListeners();
  }

  // Update profile
  Future<bool> updateProfile(Map<String, dynamic> profileData) async {
    _setLoading(true);
    _error = null;

    try {
      final response = await _apiService.updateProfile(profileData);

      if (response['user'] != null) {
        _user = UserModel.fromJson(response['user']);
        await _saveUserData();
        _setLoading(false);
        notifyListeners();
        return true;
      } else {
        _error = 'Failed to update profile';
        _setLoading(false);
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _setLoading(false);
      return false;
    }
  }

  // Check if token is valid
  Future<bool> checkTokenValidity() async {
    if (_token == null) return false;

    try {
      final response = await _apiService.getProfile();
      if (response['user'] != null) {
        _user = UserModel.fromJson(response['user']);
        await _saveUserData();
        notifyListeners();
        return true;
      }
    } catch (e) {
      debugPrint('Token validation failed: $e');
    }

    await _clearUserData();
    notifyListeners();
    return false;
  }

  // Refresh user data
  Future<void> refreshUserData() async {
    if (_token == null) return;

    try {
      final response = await _apiService.getProfile();
      if (response['user'] != null) {
        _user = UserModel.fromJson(response['user']);
        await _saveUserData();
        notifyListeners();
      }
    } catch (e) {
      debugPrint('Error refreshing user data: $e');
    }
  }

  // Set loading state
  void _setLoading(bool loading) {
    _isLoading = loading;
    notifyListeners();
  }

  // Clear error
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
