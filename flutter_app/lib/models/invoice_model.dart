class InvoiceModel {
  final int? id;
  final String invoiceNumber;
  final DateTime invoiceDate;
  final DateTime? dueDate;
  final String customerName;
  final String? customerEmail;
  final String? customerPhone;
  final String? customerAddress;
  final String? customerTaxNumber;
  final double totalAmount;
  final double netAmount;
  final double taxAmount;
  final double discountAmount;
  final String status;
  final String paymentStatus;
  final DateTime? paidAt;
  final String? paymentMethod;
  final String? paymentReference;
  final String uuid;
  final String currency;
  final String? qrCode;
  final String? hash;
  final List<InvoiceItemModel> items;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  InvoiceModel({
    this.id,
    required this.invoiceNumber,
    required this.invoiceDate,
    this.dueDate,
    required this.customerName,
    this.customerEmail,
    this.customerPhone,
    this.customerAddress,
    this.customerTaxNumber,
    required this.totalAmount,
    required this.netAmount,
    required this.taxAmount,
    this.discountAmount = 0.0,
    required this.status,
    required this.paymentStatus,
    this.paidAt,
    this.paymentMethod,
    this.paymentReference,
    required this.uuid,
    this.currency = 'JOD',
    this.qrCode,
    this.hash,
    required this.items,
    this.createdAt,
    this.updatedAt,
  });

  factory InvoiceModel.fromJson(Map<String, dynamic> json) {
    return InvoiceModel(
      id: json['id'],
      invoiceNumber: json['invoice_number'] ?? '',
      invoiceDate: DateTime.parse(json['invoice_date'] ?? DateTime.now().toIso8601String()),
      dueDate: json['due_date'] != null ? DateTime.parse(json['due_date']) : null,
      customerName: json['customer_name'] ?? '',
      customerEmail: json['customer_email'],
      customerPhone: json['customer_phone'],
      customerAddress: json['customer_address'],
      customerTaxNumber: json['customer_tax_number'],
      totalAmount: double.parse(json['total_amount']?.toString() ?? '0'),
      netAmount: double.parse(json['net_amount']?.toString() ?? '0'),
      taxAmount: double.parse(json['tax_amount']?.toString() ?? '0'),
      discountAmount: double.parse(json['discount_amount']?.toString() ?? '0'),
      status: json['status'] ?? 'draft',
      paymentStatus: json['payment_status'] ?? 'pending',
      paidAt: json['paid_at'] != null ? DateTime.parse(json['paid_at']) : null,
      paymentMethod: json['payment_method'],
      paymentReference: json['payment_reference'],
      uuid: json['uuid'] ?? '',
      currency: json['currency'] ?? 'JOD',
      qrCode: json['qr_code'],
      hash: json['hash'],
      items: (json['items'] as List<dynamic>?)
          ?.map((item) => InvoiceItemModel.fromJson(item))
          .toList() ?? [],
      createdAt: json['created_at'] != null ? DateTime.parse(json['created_at']) : null,
      updatedAt: json['updated_at'] != null ? DateTime.parse(json['updated_at']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'invoice_number': invoiceNumber,
      'invoice_date': invoiceDate.toIso8601String(),
      'due_date': dueDate?.toIso8601String(),
      'customer_name': customerName,
      'customer_email': customerEmail,
      'customer_phone': customerPhone,
      'customer_address': customerAddress,
      'customer_tax_number': customerTaxNumber,
      'total_amount': totalAmount,
      'net_amount': netAmount,
      'tax_amount': taxAmount,
      'discount_amount': discountAmount,
      'status': status,
      'payment_status': paymentStatus,
      'paid_at': paidAt?.toIso8601String(),
      'payment_method': paymentMethod,
      'payment_reference': paymentReference,
      'uuid': uuid,
      'currency': currency,
      'qr_code': qrCode,
      'hash': hash,
      'items': items.map((item) => item.toJson()).toList(),
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  InvoiceModel copyWith({
    int? id,
    String? invoiceNumber,
    DateTime? invoiceDate,
    DateTime? dueDate,
    String? customerName,
    String? customerEmail,
    String? customerPhone,
    String? customerAddress,
    String? customerTaxNumber,
    double? totalAmount,
    double? netAmount,
    double? taxAmount,
    double? discountAmount,
    String? status,
    String? paymentStatus,
    DateTime? paidAt,
    String? paymentMethod,
    String? paymentReference,
    String? uuid,
    String? currency,
    String? qrCode,
    String? hash,
    List<InvoiceItemModel>? items,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return InvoiceModel(
      id: id ?? this.id,
      invoiceNumber: invoiceNumber ?? this.invoiceNumber,
      invoiceDate: invoiceDate ?? this.invoiceDate,
      dueDate: dueDate ?? this.dueDate,
      customerName: customerName ?? this.customerName,
      customerEmail: customerEmail ?? this.customerEmail,
      customerPhone: customerPhone ?? this.customerPhone,
      customerAddress: customerAddress ?? this.customerAddress,
      customerTaxNumber: customerTaxNumber ?? this.customerTaxNumber,
      totalAmount: totalAmount ?? this.totalAmount,
      netAmount: netAmount ?? this.netAmount,
      taxAmount: taxAmount ?? this.taxAmount,
      discountAmount: discountAmount ?? this.discountAmount,
      status: status ?? this.status,
      paymentStatus: paymentStatus ?? this.paymentStatus,
      paidAt: paidAt ?? this.paidAt,
      paymentMethod: paymentMethod ?? this.paymentMethod,
      paymentReference: paymentReference ?? this.paymentReference,
      uuid: uuid ?? this.uuid,
      currency: currency ?? this.currency,
      qrCode: qrCode ?? this.qrCode,
      hash: hash ?? this.hash,
      items: items ?? this.items,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }
}

class InvoiceItemModel {
  final int? id;
  final String description;
  final int quantity;
  final double price;
  final double tax;
  final double total;

  InvoiceItemModel({
    this.id,
    required this.description,
    required this.quantity,
    required this.price,
    this.tax = 0.0,
    required this.total,
  });

  factory InvoiceItemModel.fromJson(Map<String, dynamic> json) {
    return InvoiceItemModel(
      id: json['id'],
      description: json['description'] ?? '',
      quantity: json['quantity'] ?? 1,
      price: double.parse(json['price']?.toString() ?? '0'),
      tax: double.parse(json['tax']?.toString() ?? '0'),
      total: double.parse(json['total']?.toString() ?? '0'),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'description': description,
      'quantity': quantity,
      'price': price,
      'tax': tax,
      'total': total,
    };
  }

  InvoiceItemModel copyWith({
    int? id,
    String? description,
    int? quantity,
    double? price,
    double? tax,
    double? total,
  }) {
    return InvoiceItemModel(
      id: id ?? this.id,
      description: description ?? this.description,
      quantity: quantity ?? this.quantity,
      price: price ?? this.price,
      tax: tax ?? this.tax,
      total: total ?? this.total,
    );
  }
}
