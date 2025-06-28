import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/invoice_provider.dart';
import '../../providers/language_provider.dart';
import '../../l10n/app_localizations.dart';
import '../../models/invoice_model.dart';
import '../../utils/constants.dart';
import '../../widgets/custom_text_field.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/invoice_item_form.dart';

class CreateInvoiceScreen extends StatefulWidget {
  const CreateInvoiceScreen({Key? key}) : super(key: key);

  @override
  State<CreateInvoiceScreen> createState() => _CreateInvoiceScreenState();
}

class _CreateInvoiceScreenState extends State<CreateInvoiceScreen> {
  final _formKey = GlobalKey<FormState>();
  final _scrollController = ScrollController();

  // Form controllers
  final _invoiceNumberController = TextEditingController();
  final _customerNameController = TextEditingController();
  final _customerEmailController = TextEditingController();
  final _customerPhoneController = TextEditingController();
  final _customerAddressController = TextEditingController();
  final _customerTaxNumberController = TextEditingController();
  final _notesController = TextEditingController();

  // Form state
  DateTime _invoiceDate = DateTime.now();
  DateTime? _dueDate;
  String _currency = AppConstants.defaultCurrency;
  List<InvoiceItemModel> _items = [];

  // Calculated totals
  double _subtotal = 0.0;
  double _totalTax = 0.0;
  double _total = 0.0;

  @override
  void initState() {
    super.initState();
    _generateInvoiceNumber();
    _addNewItem();
  }

  @override
  void dispose() {
    _invoiceNumberController.dispose();
    _customerNameController.dispose();
    _customerEmailController.dispose();
    _customerPhoneController.dispose();
    _customerAddressController.dispose();
    _customerTaxNumberController.dispose();
    _notesController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _generateInvoiceNumber() {
    final now = DateTime.now();
    final invoiceNumber = 'INV-${now.year}${now.month.toString().padLeft(2, '0')}${now.day.toString().padLeft(2, '0')}-${now.millisecondsSinceEpoch.toString().substring(8)}';
    _invoiceNumberController.text = invoiceNumber;
  }

  void _addNewItem() {
    setState(() {
      _items.add(InvoiceItemModel(
        description: '',
        quantity: 1,
        price: 0.0,
        tax: 16.0, // Default VAT rate in Jordan
        total: 0.0,
      ));
    });
    _calculateTotals();
  }

  void _removeItem(int index) {
    if (_items.length > 1) {
      setState(() {
        _items.removeAt(index);
      });
      _calculateTotals();
    }
  }

  void _updateItem(int index, InvoiceItemModel item) {
    setState(() {
      _items[index] = item;
    });
    _calculateTotals();
  }

  void _calculateTotals() {
    double subtotal = 0.0;
    double totalTax = 0.0;

    for (final item in _items) {
      final itemTotal = item.quantity * item.price;
      subtotal += itemTotal;
      totalTax += (itemTotal * item.tax / 100);
    }

    setState(() {
      _subtotal = subtotal;
      _totalTax = totalTax;
      _total = subtotal + totalTax;
    });
  }

  Future<void> _selectDate(BuildContext context, {required bool isDueDate}) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: isDueDate ? (_dueDate ?? DateTime.now().add(const Duration(days: 30))) : _invoiceDate,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );

    if (picked != null) {
      setState(() {
        if (isDueDate) {
          _dueDate = picked;
        } else {
          _invoiceDate = picked;
        }
      });
    }
  }

  Future<void> _saveInvoice({bool isDraft = true}) async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    // Validate items
    for (int i = 0; i < _items.length; i++) {
      if (_items[i].description.isEmpty || _items[i].price <= 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Please complete item ${i + 1}'),
            backgroundColor: AppColors.error,
          ),
        );
        return;
      }
    }

    final invoiceData = {
      'invoice_number': _invoiceNumberController.text,
      'invoice_date': _invoiceDate.toIso8601String(),
      'due_date': _dueDate?.toIso8601String(),
      'customer_name': _customerNameController.text,
      'customer_email': _customerEmailController.text.isEmpty ? null : _customerEmailController.text,
      'customer_phone': _customerPhoneController.text.isEmpty ? null : _customerPhoneController.text,
      'customer_address': _customerAddressController.text.isEmpty ? null : _customerAddressController.text,
      'customer_tax_number': _customerTaxNumberController.text.isEmpty ? null : _customerTaxNumberController.text,
      'total_amount': _total,
      'net_amount': _subtotal,
      'tax_amount': _totalTax,
      'discount_amount': 0.0,
      'status': isDraft ? 'draft' : 'submitted',
      'payment_status': 'pending',
      'currency': _currency,
      'items': _items.map((item) => {
        'description': item.description,
        'quantity': item.quantity,
        'price': item.price,
        'tax': item.tax,
        'total': item.quantity * item.price,
      }).toList(),
    };

    final invoiceProvider = Provider.of<InvoiceProvider>(context, listen: false);
    final createdInvoice = await invoiceProvider.createInvoice(invoiceData);

    if (createdInvoice != null) {
      final l10n = AppLocalizations.of(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n?.invoiceCreated ?? 'Invoice created successfully'),
          backgroundColor: AppColors.success,
        ),
      );

      Navigator.of(context).pop();
    } else {
      final error = invoiceProvider.error ?? 'Failed to create invoice';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final languageProvider = Provider.of<LanguageProvider>(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n?.createInvoice ?? 'Create Invoice'),
        actions: [
          TextButton(
            onPressed: () => _saveInvoice(isDraft: true),
            child: Text(
              'Save Draft',
              style: TextStyle(color: Colors.white),
            ),
          ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                controller: _scrollController,
                padding: const EdgeInsets.all(AppSizes.paddingMedium),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Invoice Information Section
                    _buildSectionTitle(context, 'Invoice Information'),
                    const SizedBox(height: 16),

                    Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: CustomTextField(
                            controller: _invoiceNumberController,
                            labelText: l10n?.invoiceNumber ?? 'Invoice Number',
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Invoice number is required';
                              }
                              return null;
                            },
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _currency,
                            decoration: const InputDecoration(
                              labelText: 'Currency',
                            ),
                            items: ['JOD', 'USD', 'EUR'].map((currency) {
                              return DropdownMenuItem(
                                value: currency,
                                child: Text(currency),
                              );
                            }).toList(),
                            onChanged: (value) {
                              if (value != null) {
                                setState(() {
                                  _currency = value;
                                });
                              }
                            },
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 16),

                    Row(
                      children: [
                        Expanded(
                          child: CustomTextField(
                            controller: TextEditingController(
                              text: '${_invoiceDate.day}/${_invoiceDate.month}/${_invoiceDate.year}',
                            ),
                            labelText: l10n?.invoiceDate ?? 'Invoice Date',
                            readOnly: true,
                            onTap: () => _selectDate(context, isDueDate: false),
                            suffixIcon: const Icon(Icons.calendar_today),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: CustomTextField(
                            controller: TextEditingController(
                              text: _dueDate != null ? '${_dueDate!.day}/${_dueDate!.month}/${_dueDate!.year}' : '',
                            ),
                            labelText: l10n?.dueDate ?? 'Due Date (Optional)',
                            readOnly: true,
                            onTap: () => _selectDate(context, isDueDate: true),
                            suffixIcon: const Icon(Icons.calendar_today),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 30),

                    // Customer Information Section
                    _buildSectionTitle(context, 'Customer Information'),
                    const SizedBox(height: 16),

                    CustomTextField(
                      controller: _customerNameController,
                      labelText: l10n?.customerName ?? 'Customer Name',
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Customer name is required';
                        }
                        return null;
                      },
                    ),

                    const SizedBox(height: 16),

                    Row(
                      children: [
                        Expanded(
                          child: CustomTextField(
                            controller: _customerEmailController,
                            labelText: l10n?.customerEmail ?? 'Email (Optional)',
                            keyboardType: TextInputType.emailAddress,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: CustomTextField(
                            controller: _customerPhoneController,
                            labelText: l10n?.customerPhone ?? 'Phone (Optional)',
                            keyboardType: TextInputType.phone,
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 16),

                    CustomTextField(
                      controller: _customerAddressController,
                      labelText: l10n?.customerAddress ?? 'Address (Optional)',
                      maxLines: 2,
                    ),

                    const SizedBox(height: 16),

                    CustomTextField(
                      controller: _customerTaxNumberController,
                      labelText: l10n?.customerTaxNumber ?? 'Tax Number (Optional)',
                    ),

                    const SizedBox(height: 30),

                    // Items Section
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        _buildSectionTitle(context, l10n?.items ?? 'Items'),
                        IconButton(
                          onPressed: _addNewItem,
                          icon: const Icon(Icons.add_circle),
                          color: AppColors.primaryColor,
                          iconSize: 32,
                        ),
                      ],
                    ),

                    const SizedBox(height: 16),

                    // Items List
                    ...List.generate(_items.length, (index) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 16),
                        child: InvoiceItemForm(
                          item: _items[index],
                          itemIndex: index + 1,
                          canDelete: _items.length > 1,
                          onItemChanged: (item) => _updateItem(index, item),
                          onDelete: () => _removeItem(index),
                        ),
                      );
                    }),

                    const SizedBox(height: 30),

                    // Totals Section
                    _buildTotalsSection(context),

                    const SizedBox(height: 30),
                  ],
                ),
              ),
            ),

            // Bottom Actions
            Container(
              padding: const EdgeInsets.all(AppSizes.paddingMedium),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 4,
                    offset: const Offset(0, -2),
                  ),
                ],
              ),
              child: Consumer<InvoiceProvider>(
                builder: (context, invoiceProvider, child) {
                  return Row(
                    children: [
                      Expanded(
                        child: CustomButton(
                          text: 'Save as Draft',
                          isOutlined: true,
                          isLoading: invoiceProvider.isLoading,
                          onPressed: () => _saveInvoice(isDraft: true),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: CustomButton(
                          text: 'Create & Submit',
                          isLoading: invoiceProvider.isLoading,
                          onPressed: () => _saveInvoice(isDraft: false),
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionTitle(BuildContext context, String title) {
    return Text(
      title,
      style: Theme.of(context).textTheme.titleLarge?.copyWith(
        fontWeight: FontWeight.bold,
        color: AppColors.primaryColor,
      ),
    );
  }

  Widget _buildTotalsSection(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(AppSizes.paddingLarge),
        child: Column(
          children: [
            _buildTotalRow(
              context,
              l10n?.subtotal ?? 'Subtotal',
              '${_subtotal.toStringAsFixed(2)} $_currency',
            ),
            const SizedBox(height: 8),
            _buildTotalRow(
              context,
              l10n?.taxAmount ?? 'Tax',
              '${_totalTax.toStringAsFixed(2)} $_currency',
            ),
            const Divider(),
            _buildTotalRow(
              context,
              l10n?.totalAmount ?? 'Total',
              '${_total.toStringAsFixed(2)} $_currency',
              isTotal: true,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTotalRow(BuildContext context, String label, String value, {bool isTotal = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            fontWeight: isTotal ? FontWeight.bold : FontWeight.w500,
            fontSize: isTotal ? 16 : 14,
          ),
        ),
        Text(
          value,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            fontWeight: FontWeight.bold,
            fontSize: isTotal ? 16 : 14,
            color: isTotal ? AppColors.primaryColor : null,
          ),
        ),
      ],
    );
  }
}
