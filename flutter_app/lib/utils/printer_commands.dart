import 'dart:typed_data';
import 'package:esc_pos_utils/esc_pos_utils.dart';
import '../models/invoice_model.dart';

class PrinterCommands {
  // ESC/POS Commands
  static const List<int> esc = [0x1B];
  static const List<int> gs = [0x1D];
  static const List<int> fs = [0x1C];
  
  // Text formatting
  static const List<int> bold = [0x1B, 0x45, 0x01];
  static const List<int> boldOff = [0x1B, 0x45, 0x00];
  static const List<int> doubleSize = [0x1D, 0x21, 0x11];
  static const List<int> normalSize = [0x1D, 0x21, 0x00];
  
  // Alignment
  static const List<int> alignLeft = [0x1B, 0x61, 0x00];
  static const List<int> alignCenter = [0x1B, 0x61, 0x01];
  static const List<int> alignRight = [0x1B, 0x61, 0x02];
  
  // Paper handling
  static const List<int> lineFeed = [0x0A];
  static const List<int> formFeed = [0x0C];
  static const List<int> carriageReturn = [0x0D];
  static const List<int> cut = [0x1D, 0x56, 0x00];
  static const List<int> cutPartial = [0x1D, 0x56, 0x01];
  
  // Cash drawer
  static const List<int> openDrawer = [0x1B, 0x70, 0x00, 0x19, 0xFA];
  
  // Initialize printer
  static const List<int> init = [0x1B, 0x40];
  
  // Character sets
  static const List<int> setArabic = [0x1B, 0x74, 0x12]; // Arabic character set
  static const List<int> setEnglish = [0x1B, 0x74, 0x00]; // English character set
  
  /// Generate complete receipt for thermal printer
  static Future<List<int>> generateReceiptCommands(
    InvoiceModel invoice, {
    PaperSize paperSize = PaperSize.mm80,
    bool isArabic = false,
  }) async {
    final profile = await CapabilityProfile.load();
    final generator = Generator(paperSize, profile);
    final List<int> commands = [];
    
    // Initialize printer
    commands.addAll(init);
    
    // Set character set based on language
    if (isArabic) {
      commands.addAll(setArabic);
    } else {
      commands.addAll(setEnglish);
    }
    
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
    
    // Company info (if available)
    if (invoice.companyName != null) {
      commands.addAll(generator.text(
        invoice.companyName!,
        styles: const PosStyles(
          align: PosAlign.center,
          bold: true,
        ),
      ));
    }
    
    commands.addAll(generator.feed(1));
    
    // Invoice information
    commands.addAll(generator.row([
      PosColumn(
        text: isArabic ? 'رقم الفاتورة:' : 'Invoice #:',
        width: 6,
        styles: const PosStyles(bold: true),
      ),
      PosColumn(
        text: invoice.invoiceNumber,
        width: 6,
        styles: const PosStyles(align: PosAlign.right),
      ),
    ]));
    
    commands.addAll(generator.row([
      PosColumn(
        text: isArabic ? 'التاريخ:' : 'Date:',
        width: 6,
        styles: const PosStyles(bold: true),
      ),
      PosColumn(
        text: _formatDate(invoice.invoiceDate, isArabic),
        width: 6,
        styles: const PosStyles(align: PosAlign.right),
      ),
    ]));
    
    if (invoice.dueDate != null) {
      commands.addAll(generator.row([
        PosColumn(
          text: isArabic ? 'تاريخ الاستحقاق:' : 'Due Date:',
          width: 6,
          styles: const PosStyles(bold: true),
        ),
        PosColumn(
          text: _formatDate(invoice.dueDate!, isArabic),
          width: 6,
          styles: const PosStyles(align: PosAlign.right),
        ),
      ]));
    }
    
    commands.addAll(generator.hr());
    
    // Customer information
    commands.addAll(generator.text(
      isArabic ? 'معلومات العميل' : 'Customer Information',
      styles: const PosStyles(bold: true),
    ));
    
    commands.addAll(generator.text(
      '${isArabic ? 'الاسم:' : 'Name:'} ${invoice.customerName}',
    ));
    
    if (invoice.customerPhone != null) {
      commands.addAll(generator.text(
        '${isArabic ? 'الهاتف:' : 'Phone:'} ${invoice.customerPhone}',
      ));
    }
    
    if (invoice.customerEmail != null) {
      commands.addAll(generator.text(
        '${isArabic ? 'البريد:' : 'Email:'} ${invoice.customerEmail}',
      ));
    }
    
    if (invoice.customerTaxNumber != null) {
      commands.addAll(generator.text(
        '${isArabic ? 'الرقم الضريبي:' : 'Tax No:'} ${invoice.customerTaxNumber}',
      ));
    }
    
    commands.addAll(generator.hr());
    
    // Items header
    commands.addAll(generator.text(
      isArabic ? 'الأصناف' : 'Items',
      styles: const PosStyles(bold: true),
    ));
    
    commands.addAll(generator.feed(1));
    
    // Items table header
    if (paperSize == PaperSize.mm80) {
      commands.addAll(generator.row([
        PosColumn(
          text: isArabic ? 'الوصف' : 'Description',
          width: 6,
          styles: const PosStyles(bold: true),
        ),
        PosColumn(
          text: isArabic ? 'كمية' : 'Qty',
          width: 2,
          styles: const PosStyles(bold: true, align: PosAlign.center),
        ),
        PosColumn(
          text: isArabic ? 'السعر' : 'Price',
          width: 2,
          styles: const PosStyles(bold: true, align: PosAlign.right),
        ),
        PosColumn(
          text: isArabic ? 'المجموع' : 'Total',
          width: 2,
          styles: const PosStyles(bold: true, align: PosAlign.right),
        ),
      ]));
    }
    
    commands.addAll(generator.hr(ch: '-'));
    
    // Items
    for (final item in invoice.items) {
      if (paperSize == PaperSize.mm80) {
        commands.addAll(generator.row([
          PosColumn(
            text: item.description,
            width: 6,
          ),
          PosColumn(
            text: item.quantity.toString(),
            width: 2,
            styles: const PosStyles(align: PosAlign.center),
          ),
          PosColumn(
            text: item.price.toStringAsFixed(2),
            width: 2,
            styles: const PosStyles(align: PosAlign.right),
          ),
          PosColumn(
            text: item.total.toStringAsFixed(2),
            width: 2,
            styles: const PosStyles(align: PosAlign.right),
          ),
        ]));
      } else {
        // 58mm paper - stack vertically
        commands.addAll(generator.text(
          item.description,
          styles: const PosStyles(bold: true),
        ));
        commands.addAll(generator.text(
          '${item.quantity} x ${item.price.toStringAsFixed(2)} = ${item.total.toStringAsFixed(2)}',
        ));
        commands.addAll(generator.feed(1));
      }
    }
    
    commands.addAll(generator.hr());
    
    // Totals
    commands.addAll(generator.row([
      PosColumn(
        text: isArabic ? 'المجموع الفرعي:' : 'Subtotal:',
        width: 8,
        styles: const PosStyles(bold: true),
      ),
      PosColumn(
        text: '${invoice.netAmount.toStringAsFixed(2)} ${invoice.currency}',
        width: 4,
        styles: const PosStyles(align: PosAlign.right, bold: true),
      ),
    ]));
    
    if (invoice.taxAmount > 0) {
      commands.addAll(generator.row([
        PosColumn(
          text: isArabic ? 'الضريبة:' : 'Tax:',
          width: 8,
          styles: const PosStyles(bold: true),
        ),
        PosColumn(
          text: '${invoice.taxAmount.toStringAsFixed(2)} ${invoice.currency}',
          width: 4,
          styles: const PosStyles(align: PosAlign.right, bold: true),
        ),
      ]));
    }
    
    if (invoice.discountAmount > 0) {
      commands.addAll(generator.row([
        PosColumn(
          text: isArabic ? 'الخصم:' : 'Discount:',
          width: 8,
          styles: const PosStyles(bold: true),
        ),
        PosColumn(
          text: '-${invoice.discountAmount.toStringAsFixed(2)} ${invoice.currency}',
          width: 4,
          styles: const PosStyles(align: PosAlign.right, bold: true),
        ),
      ]));
    }
    
    commands.addAll(generator.hr(ch: '='));
    
    commands.addAll(generator.row([
      PosColumn(
        text: isArabic ? 'الإجمالي:' : 'TOTAL:',
        width: 8,
        styles: const PosStyles(
          bold: true,
          height: PosTextSize.size2,
        ),
      ),
      PosColumn(
        text: '${invoice.totalAmount.toStringAsFixed(2)} ${invoice.currency}',
        width: 4,
        styles: const PosStyles(
          align: PosAlign.right,
          bold: true,
          height: PosTextSize.size2,
        ),
      ),
    ]));
    
    commands.addAll(generator.hr());
    
    // Payment status
    String paymentStatus = '';
    switch (invoice.paymentStatus) {
      case 'paid':
        paymentStatus = isArabic ? 'مدفوع' : 'PAID';
        break;
      case 'pending':
        paymentStatus = isArabic ? 'في الانتظار' : 'PENDING';
        break;
      case 'overdue':
        paymentStatus = isArabic ? 'متأخر' : 'OVERDUE';
        break;
    }
    
    if (paymentStatus.isNotEmpty) {
      commands.addAll(generator.text(
        '${isArabic ? 'حالة الدفع:' : 'Payment Status:'} $paymentStatus',
        styles: const PosStyles(
          align: PosAlign.center,
          bold: true,
        ),
      ));
      commands.addAll(generator.feed(1));
    }
    
    // QR Code section
    if (invoice.qrCode != null && invoice.qrCode!.isNotEmpty) {
      commands.addAll(generator.text(
        isArabic ? 'رمز الاستجابة السريعة:' : 'QR Code:',
        styles: const PosStyles(align: PosAlign.center),
      ));
      
      try {
        commands.addAll(generator.qrcode(
          invoice.qrCode!,
          size: QRSize.Size4,
          cor: QRCorrection.M,
        ));
      } catch (e) {
        // Fallback to text if QR generation fails
        commands.addAll(generator.text(
          invoice.qrCode!,
          styles: const PosStyles(
            align: PosAlign.center,
            width: PosTextSize.size1,
            height: PosTextSize.size1,
          ),
        ));
      }
      
      commands.addAll(generator.feed(1));
    }
    
    // Footer
    commands.addAll(generator.text(
      isArabic ? 'شكراً لتعاملكم معنا!' : 'Thank you for your business!',
      styles: const PosStyles(align: PosAlign.center, bold: true),
    ));
    
    commands.addAll(generator.text(
      isArabic ? 'نظام الفوترة الإلكترونية الأردني' : 'Powered by JoFotara',
      styles: const PosStyles(align: PosAlign.center),
    ));
    
    commands.addAll(generator.feed(3));
    commands.addAll(generator.cut());
    
    return commands;
  }
  
  /// Generate simple text receipt for basic printers
  static List<int> generateSimpleReceipt(
    InvoiceModel invoice, {
    bool isArabic = false,
    int lineWidth = 32,
  }) {
    final commands = <int>[];
    
    // Initialize
    commands.addAll(init);
    
    // Header
    final header = 'JoFotara Invoice';
    commands.addAll(_centerText(header, lineWidth));
    commands.addAll(lineFeed);
    commands.addAll(_generateLine('=', lineWidth));
    commands.addAll(lineFeed);
    
    // Invoice info
    commands.addAll(_formatLine(
      isArabic ? 'رقم الفاتورة:' : 'Invoice #:',
      invoice.invoiceNumber,
      lineWidth,
    ));
    commands.addAll(lineFeed);
    
    commands.addAll(_formatLine(
      isArabic ? 'التاريخ:' : 'Date:',
      _formatDate(invoice.invoiceDate, isArabic),
      lineWidth,
    ));
    commands.addAll(lineFeed);
    
    // Customer
    commands.addAll(_generateLine('-', lineWidth));
    commands.addAll(lineFeed);
    commands.addAll((isArabic ? 'العميل: ' : 'Customer: ').codeUnits);
    commands.addAll(invoice.customerName.codeUnits);
    commands.addAll(lineFeed);
    
    // Items
    commands.addAll(_generateLine('-', lineWidth));
    commands.addAll(lineFeed);
    for (final item in invoice.items) {
      commands.addAll(item.description.codeUnits);
      commands.addAll(lineFeed);
      final itemLine = '${item.quantity} x ${item.price.toStringAsFixed(2)} = ${item.total.toStringAsFixed(2)}';
      commands.addAll(itemLine.codeUnits);
      commands.addAll(lineFeed);
    }
    
    // Totals
    commands.addAll(_generateLine('=', lineWidth));
    commands.addAll(lineFeed);
    commands.addAll(_formatLine(
      isArabic ? 'المجموع:' : 'Total:',
      '${invoice.totalAmount.toStringAsFixed(2)} ${invoice.currency}',
      lineWidth,
    ));
    commands.addAll(lineFeed);
    
    // Footer
    commands.addAll(_generateLine('-', lineWidth));
    commands.addAll(lineFeed);
    final footer = isArabic ? 'شكراً لكم' : 'Thank you!';
    commands.addAll(_centerText(footer, lineWidth));
    commands.addAll(lineFeed);
    commands.addAll(lineFeed);
    commands.addAll(lineFeed);
    
    return commands;
  }
  
  // Helper methods
  static List<int> _centerText(String text, int lineWidth) {
    final padding = (lineWidth - text.length) ~/ 2;
    final centeredText = ' ' * padding + text;
    return centeredText.codeUnits;
  }
  
  static List<int> _formatLine(String label, String value, int lineWidth) {
    final availableSpace = lineWidth - label.length;
    final truncatedValue = value.length > availableSpace 
        ? value.substring(0, availableSpace)
        : value;
    final padding = lineWidth - label.length - truncatedValue.length;
    final line = label + ' ' * padding + truncatedValue;
    return line.codeUnits;
  }
  
  static List<int> _generateLine(String char, int width) {
    return (char * width).codeUnits;
  }
  
  static String _formatDate(DateTime date, bool isArabic) {
    if (isArabic) {
      return '${date.day}/${date.month}/${date.year}';
    } else {
      return '${date.day}/${date.month}/${date.year}';
    }
  }
  
  /// Test printer connection with simple print
  static List<int> generateTestPrint({bool isArabic = false}) {
    final commands = <int>[];
    
    commands.addAll(init);
    commands.addAll(alignCenter);
    commands.addAll(bold);
    commands.addAll('PRINTER TEST'.codeUnits);
    commands.addAll(boldOff);
    commands.addAll(lineFeed);
    commands.addAll(lineFeed);
    
    commands.addAll('JoFotara E-Invoicing System'.codeUnits);
    commands.addAll(lineFeed);
    
    if (isArabic) {
      commands.addAll(setArabic);
      commands.addAll('نظام الفوترة الإلكترونية'.codeUnits);
      commands.addAll(lineFeed);
    }
    
    commands.addAll(lineFeed);
    commands.addAll('Test completed successfully!'.codeUnits);
    commands.addAll(lineFeed);
    commands.addAll(lineFeed);
    commands.addAll(lineFeed);
    commands.addAll(cut);
    
    return commands;
  }
}