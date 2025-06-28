import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';

class ApiService {
  static String? _authToken;

  // Set authentication token
  void setAuthToken(String token) {
    _authToken = token;
  }

  // Clear authentication token
  void clearAuthToken() {
    _authToken = null;
  }

  // Get headers with authentication
  Map<String, String> get _headers {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (_authToken != null) {
      headers['Authorization'] = 'Bearer $_authToken';
    }

    return headers;
  }

  // Handle API response
  Map<String, dynamic> _handleResponse(http.Response response) {
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return jsonDecode(response.body);
    } else if (response.statusCode == 401) {
      throw Exception('Unauthorized - Please login again');
    } else if (response.statusCode == 422) {
      final errorData = jsonDecode(response.body);
      final errors = errorData['errors'] as Map<String, dynamic>?;
      if (errors != null) {
        final firstError = errors.values.first;
        throw Exception(firstError is List ? firstError.first : firstError);
      }
      throw Exception(errorData['message'] ?? 'Validation error');
    } else {
      final errorData = jsonDecode(response.body);
      throw Exception(errorData['message'] ?? 'Server error occurred');
    }
  }

  // Login
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.loginEndpoint}'),
        headers: _headers,
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } on HttpException {
      throw Exception('Network error occurred');
    } catch (e) {
      throw Exception('Login failed: ${e.toString()}');
    }
  }

  // Get user profile
  Future<Map<String, dynamic>> getProfile() async {
    try {
      final response = await http.get(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.profileEndpoint}'),
        headers: _headers,
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to fetch profile: ${e.toString()}');
    }
  }

  // Update profile
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> data) async {
    try {
      final response = await http.put(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.profileEndpoint}'),
        headers: _headers,
        body: jsonEncode(data),
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to update profile: ${e.toString()}');
    }
  }

  // Get invoices
  Future<Map<String, dynamic>> getInvoices({
    int page = 1,
    int limit = 20,
    String? status,
    String? search,
  }) async {
    try {
      var url = '${AppConstants.baseUrl}${AppConstants.invoicesEndpoint}?page=$page&limit=$limit';

      if (status != null && status.isNotEmpty) {
        url += '&status=$status';
      }

      if (search != null && search.isNotEmpty) {
        url += '&search=$search';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: _headers,
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to fetch invoices: ${e.toString()}');
    }
  }

  // Get single invoice
  Future<Map<String, dynamic>> getInvoice(int id) async {
    try {
      final response = await http.get(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.invoicesEndpoint}/$id'),
        headers: _headers,
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to fetch invoice: ${e.toString()}');
    }
  }

  // Create invoice
  Future<Map<String, dynamic>> createInvoice(Map<String, dynamic> invoiceData) async {
    try {
      final response = await http.post(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.invoicesEndpoint}'),
        headers: _headers,
        body: jsonEncode(invoiceData),
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to create invoice: ${e.toString()}');
    }
  }

  // Update invoice
  Future<Map<String, dynamic>> updateInvoice(int id, Map<String, dynamic> invoiceData) async {
    try {
      final response = await http.put(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.invoicesEndpoint}/$id'),
        headers: _headers,
        body: jsonEncode(invoiceData),
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to update invoice: ${e.toString()}');
    }
  }

  // Submit invoice to JoFotara
  Future<Map<String, dynamic>> submitInvoice(int id) async {
    try {
      final url = AppConstants.submitInvoiceEndpoint.replaceAll('{id}', id.toString());
      final response = await http.post(
        Uri.parse('${AppConstants.baseUrl}$url'),
        headers: _headers,
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to submit invoice: ${e.toString()}');
    }
  }

  // Download invoice PDF
  Future<List<int>> downloadInvoicePdf(int id) async {
    try {
      final response = await http.get(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.invoicesEndpoint}/$id/pdf'),
        headers: _headers,
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      if (response.statusCode == 200) {
        return response.bodyBytes;
      } else {
        throw Exception('Failed to download PDF');
      }
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to download PDF: ${e.toString()}');
    }
  }

  // Get dashboard statistics
  Future<Map<String, dynamic>> getDashboardStats() async {
    try {
      final response = await http.get(
        Uri.parse('${AppConstants.baseUrl}/dashboard/stats'),
        headers: _headers,
      ).timeout(const Duration(milliseconds: AppConstants.defaultTimeout));

      return _handleResponse(response);
    } on SocketException {
      throw Exception('No internet connection');
    } catch (e) {
      throw Exception('Failed to fetch dashboard stats: ${e.toString()}');
    }
  }
}
