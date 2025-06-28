import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/printer_provider.dart';
import '../l10n/app_localizations.dart';
import '../utils/constants.dart';
import '../widgets/custom_button.dart';

class PrinterSelectionDialog extends StatefulWidget {
  const PrinterSelectionDialog({Key? key}) : super(key: key);

  @override
  State<PrinterSelectionDialog> createState() => _PrinterSelectionDialogState();
}

class _PrinterSelectionDialogState extends State<PrinterSelectionDialog>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);

    // Initialize printer scanning
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final printerProvider = Provider.of<PrinterProvider>(context, listen: false);
      printerProvider.scanBluetoothDevices();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Dialog(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSizes.radiusLarge),
      ),
      child: Container(
        width: MediaQuery.of(context).size.width * 0.9,
        height: MediaQuery.of(context).size.height * 0.7,
        padding: const EdgeInsets.all(AppSizes.paddingMedium),
        child: Column(
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  l10n?.selectPrinter ?? 'Select Printer',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.of(context).pop(),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Tabs
            TabBar(
              controller: _tabController,
              tabs: [
                Tab(
                  icon: const Icon(Icons.bluetooth),
                  text: l10n?.bluetoothPrinter ?? 'Bluetooth',
                ),
                Tab(
                  icon: const Icon(Icons.wifi),
                  text: l10n?.networkPrinter ?? 'Network',
                ),
                Tab(
                  icon: const Icon(Icons.print),
                  text: 'PDF',
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Tab Content
            Expanded(
              child: TabBarView(
                controller: _tabController,
                children: [
                  _buildBluetoothTab(context),
                  _buildNetworkTab(context),
                  _buildPdfTab(context),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBluetoothTab(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Consumer<PrinterProvider>(
      builder: (context, printerProvider, child) {
        return Column(
          children: [
            // Scan Button
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Bluetooth Devices',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                CustomButton(
                  text: l10n?.scanForPrinters ?? 'Scan',
                  icon: Icons.search,
                  isLoading: printerProvider.isScanning,
                  onPressed: () => printerProvider.scanBluetoothDevices(),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Current Selection
            if (printerProvider.selectedBluetoothPrinter != null) ...[
              Container(
                padding: const EdgeInsets.all(AppSizes.paddingMedium),
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(AppSizes.radiusMedium),
                  border: Border.all(color: AppColors.success),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.check_circle, color: AppColors.success),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Connected',
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: AppColors.success,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            printerProvider.selectedBluetoothPrinter!.name ?? 'Unknown Device',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                    TextButton(
                      onPressed: () => printerProvider.disconnectBluetoothPrinter(),
                      child: Text(l10n?.disconnectPrinter ?? 'Disconnect'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Device List
            Expanded(
              child: printerProvider.bluetoothDevices.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(
                            Icons.bluetooth_disabled,
                            size: 64,
                            color: Colors.grey,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            'No Bluetooth devices found',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: Colors.grey[600],
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Make sure your printer is turned on and in pairing mode',
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Colors.grey[500],
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      itemCount: printerProvider.bluetoothDevices.length,
                      itemBuilder: (context, index) {
                        final device = printerProvider.bluetoothDevices[index];
                        final isSelected = printerProvider.selectedBluetoothPrinter?.address == device.address;

                        return Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          child: ListTile(
                            leading: Icon(
                              Icons.print,
                              color: isSelected ? AppColors.success : Colors.grey,
                            ),
                            title: Text(device.name ?? 'Unknown Device'),
                            subtitle: Text(device.address ?? ''),
                            trailing: isSelected
                                ? const Icon(Icons.check_circle, color: AppColors.success)
                                : const Icon(Icons.chevron_right),
                            onTap: () async {
                              final success = await printerProvider.connectBluetoothPrinter(device);
                              if (success) {
                                Navigator.of(context).pop();
                              }
                            },
                          ),
                        );
                      },
                    ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildNetworkTab(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Consumer<PrinterProvider>(
      builder: (context, printerProvider, child) {
        return Column(
          children: [
            // Scan Button
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Network Printers',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                CustomButton(
                  text: l10n?.scanForPrinters ?? 'Scan',
                  icon: Icons.search,
                  isLoading: printerProvider.isScanning,
                  onPressed: () => printerProvider.scanNetworkPrinters(),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Current Selection
            if (printerProvider.selectedNetworkPrinter != null) ...[
              Container(
                padding: const EdgeInsets.all(AppSizes.paddingMedium),
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(AppSizes.radiusMedium),
                  border: Border.all(color: AppColors.success),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.check_circle, color: AppColors.success),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Selected',
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: AppColors.success,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            '${printerProvider.selectedNetworkPrinter!['name']} (${printerProvider.selectedNetworkPrinter!['ip']})',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Manual IP Entry
            Card(
              child: Padding(
                padding: const EdgeInsets.all(AppSizes.paddingMedium),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Manual Setup',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            decoration: const InputDecoration(
                              labelText: 'IP Address',
                              hintText: '192.168.1.100',
                              border: OutlineInputBorder(),
                            ),
                            onSubmitted: (ip) {
                              if (ip.isNotEmpty) {
                                printerProvider.selectNetworkPrinter({
                                  'name': 'Manual Printer',
                                  'ip': ip,
                                  'port': '9100',
                                });
                              }
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                        SizedBox(
                          width: 80,
                          child: TextField(
                            decoration: const InputDecoration(
                              labelText: 'Port',
                              hintText: '9100',
                              border: OutlineInputBorder(),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Network Printer List
            Expanded(
              child: printerProvider.networkPrinters.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(
                            Icons.wifi_off,
                            size: 64,
                            color: Colors.grey,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            'No network printers found',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: Colors.grey[600],
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Make sure your printer is connected to the same network',
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Colors.grey[500],
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      itemCount: printerProvider.networkPrinters.length,
                      itemBuilder: (context, index) {
                        final printer = printerProvider.networkPrinters[index];
                        final isSelected = printerProvider.selectedNetworkPrinter?['ip'] == printer['ip'];

                        return Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          child: ListTile(
                            leading: Icon(
                              Icons.print,
                              color: isSelected ? AppColors.success : Colors.grey,
                            ),
                            title: Text(printer['name'] ?? 'Network Printer'),
                            subtitle: Text('${printer['ip']}:${printer['port']}'),
                            trailing: isSelected
                                ? const Icon(Icons.check_circle, color: AppColors.success)
                                : const Icon(Icons.chevron_right),
                            onTap: () {
                              printerProvider.selectNetworkPrinter(printer);
                              Navigator.of(context).pop();
                            },
                          ),
                        );
                      },
                    ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildPdfTab(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(
            Icons.picture_as_pdf,
            size: 64,
            color: AppColors.primaryColor,
          ),
          const SizedBox(height: 16),
          Text(
            'PDF Printing',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Generate a PDF version of the invoice for printing or sharing',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          CustomButton(
            text: 'Use PDF Printing',
            icon: Icons.picture_as_pdf,
            onPressed: () => Navigator.of(context).pop(),
          ),
        ],
      ),
    );
  }
}
