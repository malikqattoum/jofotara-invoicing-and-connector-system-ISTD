import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../models/invoice_model.dart';
import '../utils/constants.dart';
import '../widgets/custom_text_field.dart';

class InvoiceItemForm extends StatefulWidget {
  final InvoiceItemModel item;
  final int itemIndex;
  final bool canDelete;
  final Function(InvoiceItemModel) onItemChanged;
  final VoidCallback onDelete;

  const InvoiceItemForm({
    Key? key,
    required this.item,
    required this.itemIndex,
    required this.canDelete,
    required this.onItemChanged,
    required this.onDelete,
  }) : super(key: key);

  @override
  State<InvoiceItemForm> createState() => _InvoiceItemFormState();
}

class _InvoiceItemFormState extends State<InvoiceItemForm> {
  late TextEditingController _descriptionController;
  late TextEditingController _quantityController;
  late TextEditingController _priceController;
  late TextEditingController _taxController;

  @override
  void initState() {
    super.initState();
    _descriptionController = TextEditingController(text: widget.item.description);
    _quantityController = TextEditingController(text: widget.item.quantity.toString());
    _priceController = TextEditingController(text: widget.item.price.toString());
    _taxController = TextEditingController(text: widget.item.tax.toString());

    _descriptionController.addListener(_updateItem);
    _quantityController.addListener(_updateItem);
    _priceController.addListener(_updateItem);
    _taxController.addListener(_updateItem);
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _quantityController.dispose();
    _priceController.dispose();
    _taxController.dispose();
    super.dispose();
  }

  void _updateItem() {
    final quantity = int.tryParse(_quantityController.text) ?? 0;
    final price = double.tryParse(_priceController.text) ?? 0.0;
    final tax = double.tryParse(_taxController.text) ?? 0.0;
    final total = quantity * price;

    final updatedItem = widget.item.copyWith(
      description: _descriptionController.text,
      quantity: quantity,
      price: price,
      tax: tax,
      total: total,
    );

    widget.onItemChanged(updatedItem);
  }

  @override
  Widget build(BuildContext context) {
    final total = widget.item.quantity * widget.item.price;

    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSizes.radiusMedium),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppSizes.paddingMedium),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Item ${widget.itemIndex}',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: AppColors.primaryColor,
                  ),
                ),
                if (widget.canDelete)
                  IconButton(
                    onPressed: widget.onDelete,
                    icon: const Icon(Icons.delete),
                    color: AppColors.error,
                    iconSize: 20,
                  ),
              ],
            ),

            const SizedBox(height: 16),

            // Description
            CustomTextField(
              controller: _descriptionController,
              labelText: 'Description',
              hintText: 'Enter item description',
              maxLines: 2,
            ),

            const SizedBox(height: 16),

            // Quantity, Price, Tax Row
            Row(
              children: [
                Expanded(
                  flex: 2,
                  child: CustomTextField(
                    controller: _quantityController,
                    labelText: 'Qty',
                    keyboardType: TextInputType.number,
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 3,
                  child: CustomTextField(
                    controller: _priceController,
                    labelText: 'Unit Price',
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: CustomTextField(
                    controller: _taxController,
                    labelText: 'Tax %',
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                    ],
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Calculations Row
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey[50],
                borderRadius: BorderRadius.circular(AppSizes.radiusSmall),
                border: Border.all(color: Colors.grey[200]!),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Subtotal',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Colors.grey[600],
                        ),
                      ),
                      Text(
                        total.toStringAsFixed(2),
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Tax Amount',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Colors.grey[600],
                        ),
                      ),
                      Text(
                        (total * widget.item.tax / 100).toStringAsFixed(2),
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Total',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Colors.grey[600],
                        ),
                      ),
                      Text(
                        (total + (total * widget.item.tax / 100)).toStringAsFixed(2),
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: AppColors.primaryColor,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
