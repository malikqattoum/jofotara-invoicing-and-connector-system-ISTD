import 'package:flutter/foundation.dart';
import '../models/invoice_model.dart';
import '../services/api_service.dart';

class InvoiceProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<InvoiceModel> _invoices = [];
  InvoiceModel? _currentInvoice;
  bool _isLoading = false;
  String? _error;

  // Pagination
  int _currentPage = 1;
  bool _hasMore = true;
  String? _currentStatus;
  String? _currentSearch;

  // Dashboard stats
  Map<String, int> _dashboardStats = {
    'total': 0,
    'submitted': 0,
    'rejected': 0,
    'draft': 0,
    'paid': 0,
  };

  // Getters
  List<InvoiceModel> get invoices => _invoices;
  InvoiceModel? get currentInvoice => _currentInvoice;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get hasMore => _hasMore;
  Map<String, int> get dashboardStats => _dashboardStats;

  // Set loading state
  void _setLoading(bool loading) {
    _isLoading = loading;
    notifyListeners();
  }

  // Set error
  void _setError(String? error) {
    _error = error;
    notifyListeners();
  }

  // Clear error
  void clearError() {
    _error = null;
    notifyListeners();
  }

  // Load invoices
  Future<void> loadInvoices({
    bool refresh = false,
    String? status,
    String? search,
  }) async {
    if (refresh) {
      _currentPage = 1;
      _hasMore = true;
      _invoices.clear();
    }

    if (_isLoading || !_hasMore) return;

    _setLoading(true);
    _setError(null);

    try {
      final response = await _apiService.getInvoices(
        page: _currentPage,
        status: status,
        search: search,
      );

      final List<dynamic> invoiceData = response['data'] ?? response['invoices'] ?? [];
      final List<InvoiceModel> newInvoices = invoiceData
          .map((json) => InvoiceModel.fromJson(json))
          .toList();

      if (refresh) {
        _invoices = newInvoices;
      } else {
        _invoices.addAll(newInvoices);
      }

      _currentPage++;
      _hasMore = newInvoices.length >= 20; // Assuming 20 items per page
      _currentStatus = status;
      _currentSearch = search;

      _setLoading(false);
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
    }
  }

  // Load more invoices
  Future<void> loadMoreInvoices() async {
    if (!_hasMore || _isLoading) return;

    await loadInvoices(
      status: _currentStatus,
      search: _currentSearch,
    );
  }

  // Refresh invoices
  Future<void> refreshInvoices() async {
    await loadInvoices(
      refresh: true,
      status: _currentStatus,
      search: _currentSearch,
    );
  }

  // Load single invoice
  Future<void> loadInvoice(int id) async {
    _setLoading(true);
    _setError(null);

    try {
      final response = await _apiService.getInvoice(id);
      _currentInvoice = InvoiceModel.fromJson(response);
      _setLoading(false);
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
    }
  }

  // Create invoice
  Future<InvoiceModel?> createInvoice(Map<String, dynamic> invoiceData) async {
    _setLoading(true);
    _setError(null);

    try {
      final response = await _apiService.createInvoice(invoiceData);
      final newInvoice = InvoiceModel.fromJson(response);

      // Add to the beginning of the list
      _invoices.insert(0, newInvoice);

      // Update dashboard stats
      _dashboardStats['total'] = (_dashboardStats['total'] ?? 0) + 1;
      _dashboardStats[newInvoice.status] = (_dashboardStats[newInvoice.status] ?? 0) + 1;

      _setLoading(false);
      return newInvoice;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return null;
    }
  }

  // Update invoice
  Future<InvoiceModel?> updateInvoice(int id, Map<String, dynamic> invoiceData) async {
    _setLoading(true);
    _setError(null);

    try {
      final response = await _apiService.updateInvoice(id, invoiceData);
      final updatedInvoice = InvoiceModel.fromJson(response);

      // Update in the list
      final index = _invoices.indexWhere((invoice) => invoice.id == id);
      if (index != -1) {
        _invoices[index] = updatedInvoice;
      }

      // Update current invoice if it's the same
      if (_currentInvoice?.id == id) {
        _currentInvoice = updatedInvoice;
      }

      _setLoading(false);
      return updatedInvoice;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return null;
    }
  }

  // Submit invoice to JoFotara
  Future<bool> submitInvoice(int id) async {
    _setLoading(true);
    _setError(null);

    try {
      final response = await _apiService.submitInvoice(id);

      if (response['success'] == true) {
        // Update invoice status in the list
        final index = _invoices.indexWhere((invoice) => invoice.id == id);
        if (index != -1) {
          _invoices[index] = _invoices[index].copyWith(status: 'submitted');
        }

        // Update current invoice if it's the same
        if (_currentInvoice?.id == id) {
          _currentInvoice = _currentInvoice!.copyWith(status: 'submitted');
        }

        // Update dashboard stats
        _dashboardStats['submitted'] = (_dashboardStats['submitted'] ?? 0) + 1;
        _dashboardStats['draft'] = (_dashboardStats['draft'] ?? 0) - 1;

        _setLoading(false);
        return true;
      } else {
        _setError(response['message'] ?? 'Failed to submit invoice');
        _setLoading(false);
        return false;
      }
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  // Load dashboard statistics
  Future<void> loadDashboardStats() async {
    try {
      final response = await _apiService.getDashboardStats();
      _dashboardStats = Map<String, int>.from(response['stats'] ?? {});
      notifyListeners();
    } catch (e) {
      debugPrint('Error loading dashboard stats: $e');
    }
  }

  // Download invoice PDF
  Future<List<int>?> downloadInvoicePdf(int id) async {
    _setLoading(true);
    _setError(null);

    try {
      final pdfBytes = await _apiService.downloadInvoicePdf(id);
      _setLoading(false);
      return pdfBytes;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return null;
    }
  }

  // Search invoices
  Future<void> searchInvoices(String query) async {
    await loadInvoices(
      refresh: true,
      search: query,
      status: _currentStatus,
    );
  }

  // Filter invoices by status
  Future<void> filterInvoices(String? status) async {
    await loadInvoices(
      refresh: true,
      status: status,
      search: _currentSearch,
    );
  }

  // Clear current invoice
  void clearCurrentInvoice() {
    _currentInvoice = null;
    notifyListeners();
  }

  // Get invoice by ID from the list
  InvoiceModel? getInvoiceById(int id) {
    try {
      return _invoices.firstWhere((invoice) => invoice.id == id);
    } catch (e) {
      return null;
    }
  }

  // Calculate totals for creating invoice
  Map<String, double> calculateTotals(List<InvoiceItemModel> items) {
    double subtotal = 0;
    double totalTax = 0;

    for (final item in items) {
      final itemTotal = item.quantity * item.price;
      subtotal += itemTotal;
      totalTax += (itemTotal * item.tax / 100);
    }

    final total = subtotal + totalTax;

    return {
      'subtotal': subtotal,
      'tax': totalTax,
      'total': total,
    };
  }
}
