import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:bluetooth_print/bluetooth_print.dart';
import 'package:network_info_plus/network_info_plus.dart';
import 'package:printing/printing.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:esc_pos_utils/esc_pos_utils.dart';
import '../models/invoice_model.dart';
import '../services/pdf_service.dart';

class PrinterProvider extends ChangeNotifier {
  final BluetoothPrint _bluetoothPrint = BluetoothPrint.instance;
  final NetworkInfo _networkInfo = NetworkInfo();
  final PdfService _pdfService = PdfService();

  List<BluetoothDevice> _bluetoothDevices = [];
  List<Map<String, String>> _networkPrinters = [];
  BluetoothDevice? _selectedBluetoothPrinter;
  Map<String, String>? _selectedNetworkPrinter;
  bool _isScanning = false;
  bool _isPrinting = false;
  String? _error;

  // Getters
  List<BluetoothDevice> get bluetoothDevices => _bluetoothDevices;
  List<Map<String, String>> get networkPrinters => _networkPrinters;
  BluetoothDevice? get selectedBluetoothPrinter => _selectedBluetoothPrinter;
  Map<String, String>? get selectedNetworkPrinter => _selectedNetworkPrinter;
  bool get isScanning => _isScanning;
  bool get isPrinting => _isPrinting;
  String? get error => _error;

  // Initialize printer services
  Future<void> initialize() async {
    await _requestPermissions();
    await _initializeBluetooth();
  }

  // Request necessary permissions
  Future<void> _requestPermissions() async {
    if (Platform.isAndroid) {
      await [
        Permission.bluetooth,
        Permission.bluetoothScan,
        Permission.bluetoothConnect,
        Permission.location,
      ].request();
    }
  }

  // Initialize Bluetooth
  Future<void> _initializeBluetooth() async {
    try {
      await _bluetoothPrint.startScan(timeout: const Duration(seconds: 4));
      _bluetoothPrint.scanResults.listen((devices) {
        _bluetoothDevices = devices;
        notifyListeners();
      });
    } catch (e) {
      _setError('Failed to initialize Bluetooth: $e');
    }
  }

  // Scan for Bluetooth devices
  Future<void> scanBluetoothDevices() async {
    _setScanning(true);
    _setError(null);

    try {
      await _bluetoothPrint.startScan(timeout: const Duration(seconds: 10));
    } catch (e) {
      _setError('Failed to scan Bluetooth devices: $e');
    } finally {
      _setScanning(false);
    }
  }

  // Connect to Bluetooth printer
  Future<bool> connectBluetoothPrinter(BluetoothDevice device) async {
    try {
      await _bluetoothPrint.connect(device);
      _selectedBluetoothPrinter = device;
      notifyListeners();
      return true;
    } catch (e) {
      _setError('Failed to connect to Bluetooth printer: $e');
      return false;
    }
  }

  // Disconnect Bluetooth printer
  Future<void> disconnectBluetoothPrinter() async {
    try {
      await _bluetoothPrint.disconnect();
      _selectedBluetoothPrinter = null;
      notifyListeners();
    } catch (e) {
      _setError('Failed to disconnect Bluetooth printer: $e');
    }
  }

  // Scan for network printers
  Future<void> scanNetworkPrinters() async {
    _setScanning(true);
    _setError(null);

    try {
      final wifiIP = await _networkInfo.getWifiIP();
      if (wifiIP != null) {
        final subnet = wifiIP.substring(0, wifiIP.lastIndexOf('.'));
        _networkPrinters.clear();

        // Simple network scanning for common printer ports
        for (int i = 1; i <= 254; i++) {
          final ip = '$subnet.$i';
          try {
            final socket = await Socket.connect(ip, 9100, timeout: const Duration(seconds: 1));
            _networkPrinters.add({
              'name': 'Network Printer',
              'ip': ip,
              'port': '9100',
            });
            socket.destroy();
          } catch (e) {
            // Connection failed, continue scanning
          }
        }
      }
    } catch (e) {
      _setError('Failed to scan network printers: $e');
    } finally {
      _setScanning(false);
    }
  }

  // Select network printer
  void selectNetworkPrinter(Map<String, String> printer) {
    _selectedNetworkPrinter = printer;
    notifyListeners();
  }

  // Print invoice via Bluetooth
  Future<bool> printInvoiceViaBluetooth(InvoiceModel invoice) async {
    if (_selectedBluetoothPrinter == null) {
      _setError('No Bluetooth printer selected');
      return false;
    }

    _setPrinting(true);
    _setError(null);

    try {
      final receipt = await _generateReceiptData(invoice);
      await _bluetoothPrint.printReceipt(receipt);
      _setPrinting(false);
      return true;
    } catch (e) {
      _setError('Failed to print via Bluetooth: $e');
      _setPrinting(false);
      return false;
    }
  }

  // Print invoice via Network
  Future<bool> printInvoiceViaNetwork(InvoiceModel invoice) async {
    if (_selectedNetworkPrinter == null) {
      _setError('No network printer selected');
      return false;
    }

    _setPrinting(true);
    _setError(null);

    try {
      final receipt = await _generateReceiptData(invoice);
      final ip = _selectedNetworkPrinter!['ip']!;
      final port = int.parse(_selectedNetworkPrinter!['port']!);

      final socket = await Socket.connect(ip, port);
      for (final command in receipt['commands']) {
        socket.add(command);
      }
      await socket.flush();
      socket.destroy();

      _setPrinting(false);
      return true;
    } catch (e) {
      _setError('Failed to print via network: $e');
      _setPrinting(false);
      return false;
    }
  }

  // Print invoice as PDF
  Future<bool> printInvoiceAsPdf(InvoiceModel invoice) async {
    _setPrinting(true);
    _setError(null);

    try {
      final pdfBytes = await _pdfService.generateInvoicePdf(invoice);
      await Printing.layoutPdf(
        onLayout: (PdfPageFormat format) async => pdfBytes,
      );
      _setPrinting(false);
      return true;
    } catch (e) {
      _setError('Failed to print PDF: $e');
      _setPrinting(false);
      return false;
    }
  }

  // Generate receipt data for thermal printers
  Future<Map<String, dynamic>> _generateReceiptData(InvoiceModel invoice) async {
    final profile = await CapabilityProfile.load();
    final generator = Generator(PaperSize.mm80, profile);
    final List<int> commands = [];

    // Header
    commands.addAll(generator.text(
      'JoFotara Invoice',
      styles: const PosStyles(
        align: PosAlign.center,
        height: PosTextSize.size2,
        width: PosTextSize.size2,
        bold: true,
      ),
    ));
    commands.addAll(generator.hr());

    // Invoice info
    commands.addAll(generator.text('Invoice #: ${invoice.invoiceNumber}'));
    commands.addAll(generator.text('Date: ${invoice.invoiceDate.toString().split(' ')[0]}'));
    commands.addAll(generator.text('Customer: ${invoice.customerName}'));
    if (invoice.customerPhone != null) {
      commands.addAll(generator.text('Phone: ${invoice.customerPhone}'));
    }
    commands.addAll(generator.hr());

    // Items
    commands.addAll(generator.text(
      'Items:',
      styles: const PosStyles(bold: true),
    ));

    for (final item in invoice.items) {
      commands.addAll(generator.text(
        '${item.description}',
        styles: const PosStyles(bold: true),
      ));
      commands.addAll(generator.text(
        '${item.quantity} x ${item.price.toStringAsFixed(2)} = ${item.total.toStringAsFixed(2)}',
      ));
    }

    commands.addAll(generator.hr());

    // Totals
    commands.addAll(generator.text('Subtotal: ${invoice.netAmount.toStringAsFixed(2)} ${invoice.currency}'));
    commands.addAll(generator.text('Tax: ${invoice.taxAmount.toStringAsFixed(2)} ${invoice.currency}'));
    commands.addAll(generator.text(
      'Total: ${invoice.totalAmount.toStringAsFixed(2)} ${invoice.currency}',
      styles: const PosStyles(
        bold: true,
        height: PosTextSize.size2,
      ),
    ));

    // QR Code
    if (invoice.qrCode != null) {
      commands.addAll(generator.hr());
      commands.addAll(generator.text(
        'QR Code:',
        styles: const PosStyles(align: PosAlign.center),
      ));
      // Note: QR code printing would require additional implementation
      // based on the specific printer capabilities
    }

    // Footer
    commands.addAll(generator.hr());
    commands.addAll(generator.text(
      'Thank you for your business!',
      styles: const PosStyles(align: PosAlign.center),
    ));
    commands.addAll(generator.text(
      'Powered by JoFotara',
      styles: const PosStyles(align: PosAlign.center),
    ));

    commands.addAll(generator.feed(3));
    commands.addAll(generator.cut());

    return {
      'commands': commands,
      'generator': generator,
    };
  }

  // Set scanning state
  void _setScanning(bool scanning) {
    _isScanning = scanning;
    notifyListeners();
  }

  // Set printing state
  void _setPrinting(bool printing) {
    _isPrinting = printing;
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

  // Get printer status
  String getPrinterStatus() {
    if (_selectedBluetoothPrinter != null) {
      return 'Bluetooth: ${_selectedBluetoothPrinter!.name}';
    } else if (_selectedNetworkPrinter != null) {
      return 'Network: ${_selectedNetworkPrinter!['ip']}';
    } else {
      return 'No printer selected';
    }
  }

  // Check if any printer is connected
  bool get hasPrinterConnected {
    return _selectedBluetoothPrinter != null || _selectedNetworkPrinter != null;
  }
}
