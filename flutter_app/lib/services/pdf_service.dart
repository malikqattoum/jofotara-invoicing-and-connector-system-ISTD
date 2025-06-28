import 'dart:convert';
import 'dart:typed_data';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:flutter/services.dart';
import '../models/invoice_model.dart';

class PdfService {
  // Generate invoice PDF
  Future<Uint8List> generateInvoicePdf(InvoiceModel invoice) async {
    final pdf = pw.Document();

    // Load Arabic font
    final arabicFont = await rootBundle.load('assets/fonts/Cairo-Regular.ttf');
    final arabicBoldFont = await rootBundle.load('assets/fonts/Cairo-Bold.ttf');
    final ttfArabic = pw.Font.ttf(arabicFont);
    final ttfArabicBold = pw.Font.ttf(arabicBoldFont);

    // Create PDF page
    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4,
        textDirection: pw.TextDirection.rtl,
        build: (pw.Context context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              // Header
              _buildHeader(invoice, ttfArabicBold, ttfArabic),
              pw.SizedBox(height: 20),

              // Invoice details
              _buildInvoiceDetails(invoice, ttfArabicBold, ttfArabic),
              pw.SizedBox(height: 20),

              // Customer details
              _buildCustomerDetails(invoice, ttfArabicBold, ttfArabic),
              pw.SizedBox(height: 20),

              // Items table
              _buildItemsTable(invoice, ttfArabicBold, ttfArabic),
              pw.SizedBox(height: 20),

              // Totals
              _buildTotals(invoice, ttfArabicBold, ttfArabic),

              // QR Code
              if (invoice.qrCode != null) ...[
                pw.SizedBox(height: 20),
                _buildQrCode(invoice, ttfArabic),
              ],

              // Footer
              pw.Spacer(),
              _buildFooter(ttfArabic),
            ],
          );
        },
      ),
    );

    return pdf.save();
  }

  // Build header section
  pw.Widget _buildHeader(InvoiceModel invoice, pw.Font boldFont, pw.Font regularFont) {
    return pw.Row(
      mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
      children: [
        pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.start,
          children: [
            pw.Text(
              'فاتورة ضريبية',
              style: pw.TextStyle(
                font: boldFont,
                fontSize: 24,
                color: PdfColors.green,
              ),
            ),
            pw.Text(
              'Tax Invoice',
              style: pw.TextStyle(
                font: regularFont,
                fontSize: 18,
                color: PdfColors.green,
              ),
            ),
          ],
        ),
        pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.end,
          children: [
            pw.Text(
              'نظام الفوترة الإلكترونية',
              style: pw.TextStyle(
                font: boldFont,
                fontSize: 16,
              ),
            ),
            pw.Text(
              'JoFotara E-Invoicing System',
              style: pw.TextStyle(
                font: regularFont,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ],
    );
  }

  // Build invoice details section
  pw.Widget _buildInvoiceDetails(InvoiceModel invoice, pw.Font boldFont, pw.Font regularFont) {
    return pw.Container(
      padding: const pw.EdgeInsets.all(10),
      decoration: pw.BoxDecoration(
        border: pw.Border.all(color: PdfColors.grey300),
        borderRadius: pw.BorderRadius.circular(5),
      ),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              _buildDetailRow('رقم الفاتورة:', invoice.invoiceNumber, boldFont, regularFont),
              _buildDetailRow('تاريخ الإصدار:', _formatDate(invoice.invoiceDate), boldFont, regularFont),
              if (invoice.dueDate != null)
                _buildDetailRow('تاريخ الاستحقاق:', _formatDate(invoice.dueDate!), boldFont, regularFont),
            ],
          ),
          pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              _buildDetailRow('الحالة:', _getStatusInArabic(invoice.status), boldFont, regularFont),
              _buildDetailRow('حالة الدفع:', _getPaymentStatusInArabic(invoice.paymentStatus), boldFont, regularFont),
              _buildDetailRow('العملة:', invoice.currency, boldFont, regularFont),
            ],
          ),
        ],
      ),
    );
  }

  // Build customer details section
  pw.Widget _buildCustomerDetails(InvoiceModel invoice, pw.Font boldFont, pw.Font regularFont) {
    return pw.Container(
      padding: const pw.EdgeInsets.all(10),
      decoration: pw.BoxDecoration(
        border: pw.Border.all(color: PdfColors.grey300),
        borderRadius: pw.BorderRadius.circular(5),
      ),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text(
            'بيانات العميل',
            style: pw.TextStyle(font: boldFont, fontSize: 14),
          ),
          pw.SizedBox(height: 10),
          _buildDetailRow('الاسم:', invoice.customerName, boldFont, regularFont),
          if (invoice.customerEmail != null)
            _buildDetailRow('البريد الإلكتروني:', invoice.customerEmail!, boldFont, regularFont),
          if (invoice.customerPhone != null)
            _buildDetailRow('رقم الهاتف:', invoice.customerPhone!, boldFont, regularFont),
          if (invoice.customerAddress != null)
            _buildDetailRow('العنوان:', invoice.customerAddress!, boldFont, regularFont),
          if (invoice.customerTaxNumber != null)
            _buildDetailRow('الرقم الضريبي:', invoice.customerTaxNumber!, boldFont, regularFont),
        ],
      ),
    );
  }

  // Build items table
  pw.Widget _buildItemsTable(InvoiceModel invoice, pw.Font boldFont, pw.Font regularFont) {
    return pw.Table(
      border: pw.TableBorder.all(color: PdfColors.grey300),
      children: [
        // Header
        pw.TableRow(
          decoration: const pw.BoxDecoration(color: PdfColors.grey100),
          children: [
            _buildTableCell('الوصف', boldFont, isHeader: true),
            _buildTableCell('الكمية', boldFont, isHeader: true),
            _buildTableCell('السعر', boldFont, isHeader: true),
            _buildTableCell('الضريبة', boldFont, isHeader: true),
            _buildTableCell('المجموع', boldFont, isHeader: true),
          ],
        ),
        // Items
        ...invoice.items.map((item) => pw.TableRow(
          children: [
            _buildTableCell(item.description, regularFont),
            _buildTableCell(item.quantity.toString(), regularFont),
            _buildTableCell(item.price.toStringAsFixed(2), regularFont),
            _buildTableCell('${item.tax.toStringAsFixed(1)}%', regularFont),
            _buildTableCell(item.total.toStringAsFixed(2), regularFont),
          ],
        )),
      ],
    );
  }

  // Build totals section
  pw.Widget _buildTotals(InvoiceModel invoice, pw.Font boldFont, pw.Font regularFont) {
    return pw.Container(
      width: 200,
      padding: const pw.EdgeInsets.all(10),
      decoration: pw.BoxDecoration(
        border: pw.Border.all(color: PdfColors.grey300),
        borderRadius: pw.BorderRadius.circular(5),
      ),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          _buildDetailRow('المجموع الفرعي:', '${invoice.netAmount.toStringAsFixed(2)} ${invoice.currency}', boldFont, regularFont),
          _buildDetailRow('الضريبة:', '${invoice.taxAmount.toStringAsFixed(2)} ${invoice.currency}', boldFont, regularFont),
          if (invoice.discountAmount > 0)
            _buildDetailRow('الخصم:', '${invoice.discountAmount.toStringAsFixed(2)} ${invoice.currency}', boldFont, regularFont),
          pw.Divider(),
          _buildDetailRow('المجموع الكلي:', '${invoice.totalAmount.toStringAsFixed(2)} ${invoice.currency}', boldFont, regularFont, isTotal: true),
        ],
      ),
    );
  }

  // Build QR code section
  pw.Widget _buildQrCode(InvoiceModel invoice, pw.Font regularFont) {
    // Decode base64 QR code
    final qrBytes = base64Decode(invoice.qrCode!);

    return pw.Row(
      mainAxisAlignment: pw.MainAxisAlignment.center,
      children: [
        pw.Column(
          children: [
            pw.Text(
              'رمز الاستجابة السريعة',
              style: pw.TextStyle(font: regularFont, fontSize: 12),
            ),
            pw.SizedBox(height: 10),
            pw.Image(
              pw.MemoryImage(qrBytes),
              width: 100,
              height: 100,
            ),
          ],
        ),
      ],
    );
  }

  // Build footer section
  pw.Widget _buildFooter(pw.Font regularFont) {
    return pw.Container(
      padding: const pw.EdgeInsets.all(10),
      child: pw.Center(
        child: pw.Column(
          children: [
            pw.Text(
              'شكراً لتعاملكم معنا',
              style: pw.TextStyle(font: regularFont, fontSize: 12),
            ),
            pw.Text(
              'مدعوم بنظام الفوترة الإلكترونية الأردني - JoFotara',
              style: pw.TextStyle(font: regularFont, fontSize: 10),
            ),
          ],
        ),
      ),
    );
  }

  // Helper method to build detail row
  pw.Widget _buildDetailRow(String label, String value, pw.Font boldFont, pw.Font regularFont, {bool isTotal = false}) {
    return pw.Row(
      children: [
        pw.Text(
          label,
          style: pw.TextStyle(
            font: boldFont,
            fontSize: isTotal ? 14 : 12,
          ),
        ),
        pw.SizedBox(width: 10),
        pw.Text(
          value,
          style: pw.TextStyle(
            font: regularFont,
            fontSize: isTotal ? 14 : 12,
            color: isTotal ? PdfColors.green : PdfColors.black,
          ),
        ),
      ],
    );
  }

  // Helper method to build table cell
  pw.Widget _buildTableCell(String text, pw.Font font, {bool isHeader = false}) {
    return pw.Container(
      padding: const pw.EdgeInsets.all(8),
      child: pw.Text(
        text,
        style: pw.TextStyle(
          font: font,
          fontSize: isHeader ? 12 : 10,
        ),
        textAlign: pw.TextAlign.center,
      ),
    );
  }

  // Helper method to format date
  String _formatDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }

  // Helper method to get status in Arabic
  String _getStatusInArabic(String status) {
    switch (status) {
      case 'draft':
        return 'مسودة';
      case 'submitted':
        return 'مرسلة';
      case 'rejected':
        return 'مرفوضة';
      case 'paid':
        return 'مدفوعة';
      default:
        return status;
    }
  }

  // Helper method to get payment status in Arabic
  String _getPaymentStatusInArabic(String status) {
    switch (status) {
      case 'pending':
        return 'في الانتظار';
      case 'paid':
        return 'مدفوع';
      case 'overdue':
        return 'متأخر';
      default:
        return status;
    }
  }
}
