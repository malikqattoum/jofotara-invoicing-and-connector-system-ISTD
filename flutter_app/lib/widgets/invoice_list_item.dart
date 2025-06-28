import 'package:flutter/material.dart';
import '../utils/constants.dart';
import '../l10n/app_localizations.dart';

class InvoiceListItem extends StatelessWidget {
  final Map<String, dynamic> invoice;
  final VoidCallback? onTap;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;
  final VoidCallback? onPrint;
  final bool showActions;

  const InvoiceListItem({
    Key? key,
    required this.invoice,
    this.onTap,
    this.onEdit,
    this.onDelete,
    this.onPrint,
    this.showActions = true,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Card(
      margin: const EdgeInsets.only(bottom: AppSizes.paddingMedium),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSizes.radiusLarge),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppSizes.radiusLarge),
        child: Padding(
          padding: const EdgeInsets.all(AppSizes.paddingMedium),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header Row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          invoice['invoice_number'] ?? '',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                            color: AppColors.primaryColor,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          invoice['customer_name'] ?? '',
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Colors.grey[700],
                          ),
                        ),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      _buildStatusChip(context, invoice['status'] ?? 'draft'),
                      const SizedBox(height: 4),
                      _buildPaymentStatusChip(context, invoice['payment_status'] ?? 'pending'),
                    ],
                  ),
                ],
              ),

              const SizedBox(height: 16),

              // Details Row
              Row(
                children: [
                  Expanded(
                    child: _buildDetailItem(
                      context,
                      Icons.calendar_today,
                      l10n?.invoiceDate ?? 'Date',
                      _formatDate(invoice['invoice_date']),
                    ),
                  ),
                  Expanded(
                    child: _buildDetailItem(
                      context,
                      Icons.money,
                      l10n?.total ?? 'Total',
                      '${_formatAmount(invoice['total_amount'])} ${invoice['currency'] ?? 'JOD'}',
                    ),
                  ),
                ],
              ),

              if (showActions) ...[
                const SizedBox(height: 16),
                const Divider(),

                // Actions Row
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    if (onEdit != null)
                      _buildActionButton(
                        context,
                        Icons.edit,
                        l10n?.edit ?? 'Edit',
                        onEdit!,
                        AppColors.warning,
                      ),
                    if (onPrint != null)
                      _buildActionButton(
                        context,
                        Icons.print,
                        l10n?.print ?? 'Print',
                        onPrint!,
                        AppColors.info,
                      ),
                    if (invoice['status'] == 'draft')
                      _buildActionButton(
                        context,
                        Icons.send,
                        'Submit',
                        () => _showSubmitDialog(context),
                        AppColors.success,
                      ),
                    if (onDelete != null)
                      _buildActionButton(
                        context,
                        Icons.delete,
                        l10n?.delete ?? 'Delete',
                        () => _showDeleteDialog(context),
                        AppColors.error,
                      ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusChip(BuildContext context, String status) {
    Color chipColor;
    String displayText;

    switch (status.toLowerCase()) {
      case 'submitted':
        chipColor = AppColors.success;
        displayText = AppLocalizations.of(context)?.submitted ?? 'Submitted';
        break;
      case 'rejected':
        chipColor = AppColors.error;
        displayText = AppLocalizations.of(context)?.rejected ?? 'Rejected';
        break;
      case 'paid':
        chipColor = AppColors.info;
        displayText = AppLocalizations.of(context)?.paid ?? 'Paid';
        break;
      case 'draft':
      default:
        chipColor = AppColors.warning;
        displayText = AppLocalizations.of(context)?.draft ?? 'Draft';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: 8,
        vertical: 4,
      ),
      decoration: BoxDecoration(
        color: chipColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppSizes.radiusSmall),
        border: Border.all(color: chipColor.withOpacity(0.3)),
      ),
      child: Text(
        displayText,
        style: TextStyle(
          color: chipColor,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  Widget _buildPaymentStatusChip(BuildContext context, String paymentStatus) {
    Color chipColor;
    String displayText;

    switch (paymentStatus.toLowerCase()) {
      case 'paid':
        chipColor = AppColors.success;
        displayText = AppLocalizations.of(context)?.paid ?? 'Paid';
        break;
      case 'overdue':
        chipColor = AppColors.error;
        displayText = AppLocalizations.of(context)?.overdue ?? 'Overdue';
        break;
      case 'pending':
      default:
        chipColor = AppColors.warning;
        displayText = AppLocalizations.of(context)?.pending ?? 'Pending';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: 6,
        vertical: 2,
      ),
      decoration: BoxDecoration(
        color: chipColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppSizes.radiusSmall),
        border: Border.all(color: chipColor.withOpacity(0.3)),
      ),
      child: Text(
        displayText,
        style: TextStyle(
          color: chipColor,
          fontSize: 10,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }

  Widget _buildDetailItem(BuildContext context, IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(
          icon,
          size: AppSizes.iconSmall,
          color: Colors.grey[600],
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Colors.grey[600],
                ),
              ),
              Text(
                value,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildActionButton(
    BuildContext context,
    IconData icon,
    String label,
    VoidCallback onPressed,
    Color color,
  ) {
    return InkWell(
      onTap: onPressed,
      borderRadius: BorderRadius.circular(AppSizes.radiusSmall),
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 8,
        ),
        child: Column(
          children: [
            Icon(
              icon,
              size: AppSizes.iconMedium,
              color: color,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showDeleteDialog(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n?.delete ?? 'Delete'),
        content: Text(l10n?.confirmDelete ?? 'Are you sure you want to delete this invoice?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(l10n?.cancel ?? 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).pop();
              onDelete?.call();
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.error,
            ),
            child: Text(l10n?.delete ?? 'Delete'),
          ),
        ],
      ),
    );
  }

  void _showSubmitDialog(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Submit Invoice'),
        content: Text('Are you sure you want to submit this invoice to JoFotara?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(l10n?.cancel ?? 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).pop();
              // TODO: Implement submit functionality
            },
            child: Text('Submit'),
          ),
        ],
      ),
    );
  }

  String _formatDate(dynamic date) {
    if (date == null) return '';

    try {
      DateTime dateTime;
      if (date is String) {
        dateTime = DateTime.parse(date);
      } else if (date is DateTime) {
        dateTime = date;
      } else {
        return date.toString();
      }

      return '${dateTime.day}/${dateTime.month}/${dateTime.year}';
    } catch (e) {
      return date.toString();
    }
  }

  String _formatAmount(dynamic amount) {
    if (amount == null) return '0.00';

    try {
      final double value = double.parse(amount.toString());
      return value.toStringAsFixed(2);
    } catch (e) {
      return amount.toString();
    }
  }
}
