import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/invoice_provider.dart';
import '../../providers/language_provider.dart';
import '../../l10n/app_localizations.dart';
import '../../utils/constants.dart';
import '../../widgets/dashboard_stats_card.dart';
import '../../widgets/invoice_list_item.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/language_selector.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({Key? key}) : super(key: key);

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final _searchController = TextEditingController();
  String? _selectedStatus;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _initializeData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _initializeData() async {
    final invoiceProvider = Provider.of<InvoiceProvider>(context, listen: false);
    await Future.wait([
      invoiceProvider.loadInvoices(refresh: true),
      invoiceProvider.loadDashboardStats(),
    ]);
  }

  Future<void> _refreshData() async {
    final invoiceProvider = Provider.of<InvoiceProvider>(context, listen: false);
    await Future.wait([
      invoiceProvider.refreshInvoices(),
      invoiceProvider.loadDashboardStats(),
    ]);
  }

  void _onSearchChanged(String query) {
    final invoiceProvider = Provider.of<InvoiceProvider>(context, listen: false);
    invoiceProvider.searchInvoices(query);
  }

  void _onStatusFilterChanged(String? status) {
    setState(() {
      _selectedStatus = status;
    });
    final invoiceProvider = Provider.of<InvoiceProvider>(context, listen: false);
    invoiceProvider.filterInvoices(status);
  }

  void _showLogoutDialog() {
    final l10n = AppLocalizations.of(context);
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n?.logout ?? 'Logout'),
        content: Text(l10n?.confirmLogout ?? 'Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(l10n?.cancel ?? 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).pop();
              _logout();
            },
            child: Text(l10n?.logout ?? 'Logout'),
          ),
        ],
      ),
    );
  }

  void _logout() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    await authProvider.logout();
    Navigator.of(context).pushReplacementNamed('/login');
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final languageProvider = Provider.of<LanguageProvider>(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n?.dashboard ?? 'Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refreshData,
            tooltip: 'Refresh',
          ),
          const LanguageSelector(isCompact: true),
          const SizedBox(width: 8),
          PopupMenuButton<String>(
            onSelected: (value) {
              switch (value) {
                case 'profile':
                  Navigator.of(context).pushNamed('/settings');
                  break;
                case 'logout':
                  _showLogoutDialog();
                  break;
              }
            },
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 'profile',
                child: Row(
                  children: [
                    const Icon(Icons.person),
                    const SizedBox(width: 8),
                    Text(l10n?.profile ?? 'Profile'),
                  ],
                ),
              ),
              PopupMenuItem(
                value: 'logout',
                child: Row(
                  children: [
                    const Icon(Icons.logout),
                    const SizedBox(width: 8),
                    Text(l10n?.logout ?? 'Logout'),
                  ],
                ),
              ),
            ],
            child: Consumer<AuthProvider>(
              builder: (context, authProvider, child) {
                return CircleAvatar(
                  backgroundColor: Colors.white,
                  child: Text(
                    authProvider.user?.name.substring(0, 1).toUpperCase() ?? 'U',
                    style: const TextStyle(
                      color: AppColors.primaryColor,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                );
              },
            ),
          ),
          const SizedBox(width: 16),
        ],
        bottom: TabBar(
          controller: _tabController,
          tabs: [
            Tab(
              icon: const Icon(Icons.dashboard),
              text: l10n?.dashboard ?? 'Dashboard',
            ),
            Tab(
              icon: const Icon(Icons.receipt_long),
              text: l10n?.recentInvoices ?? 'Invoices',
            ),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildDashboardTab(),
          _buildInvoicesTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.of(context).pushNamed('/create-invoice'),
        icon: const Icon(Icons.add),
        label: Text(l10n?.createInvoice ?? 'Create Invoice'),
        backgroundColor: AppColors.primaryColor,
      ),
    );
  }

  Widget _buildDashboardTab() {
    final l10n = AppLocalizations.of(context);

    return RefreshIndicator(
      onRefresh: _refreshData,
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSizes.paddingMedium),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Welcome Section
            Consumer<AuthProvider>(
              builder: (context, authProvider, child) {
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(AppSizes.paddingLarge),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 30,
                          backgroundColor: AppColors.primaryColor,
                          child: Text(
                            authProvider.user?.name?.substring(0, 1).toUpperCase() ?? 'U',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '${l10n?.welcome ?? 'Welcome'}, ${authProvider.user?.name ?? ''}',
                                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              if (authProvider.user?.companyName != null)
                                Text(
                                  authProvider.user!.companyName!,
                                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    color: Colors.grey[600],
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),

            const SizedBox(height: 20),

            // Statistics Cards
            Consumer<InvoiceProvider>(
              builder: (context, invoiceProvider, child) {
                final stats = invoiceProvider.dashboardStats;

                return Column(
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: DashboardStatsCard(
                            title: l10n?.totalInvoices ?? 'Total',
                            value: stats['total']?.toString() ?? '0',
                            icon: Icons.receipt_long,
                            color: AppColors.info,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: DashboardStatsCard(
                            title: l10n?.submittedInvoices ?? 'Submitted',
                            value: stats['submitted']?.toString() ?? '0',
                            icon: Icons.send,
                            color: AppColors.success,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: DashboardStatsCard(
                            title: l10n?.draftInvoices ?? 'Draft',
                            value: stats['draft']?.toString() ?? '0',
                            icon: Icons.draft,
                            color: AppColors.warning,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: DashboardStatsCard(
                            title: l10n?.rejectedInvoices ?? 'Rejected',
                            value: stats['rejected']?.toString() ?? '0',
                            icon: Icons.error,
                            color: AppColors.error,
                          ),
                        ),
                      ],
                    ),
                  ],
                );
              },
            ),

            const SizedBox(height: 30),

            // Quick Actions
            Text(
              'Quick Actions',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),

            Row(
              children: [
                Expanded(
                  child: CustomButton(
                    text: l10n?.createInvoice ?? 'Create Invoice',
                    icon: Icons.add,
                    onPressed: () => Navigator.of(context).pushNamed('/create-invoice'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: CustomButton(
                    text: l10n?.settings ?? 'Settings',
                    icon: Icons.settings,
                    isOutlined: true,
                    onPressed: () => Navigator.of(context).pushNamed('/settings'),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 30),

            // Recent Invoices Preview
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  l10n?.recentInvoices ?? 'Recent Invoices',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                TextButton(
                  onPressed: () => _tabController.animateTo(1),
                  child: const Text('View All'),
                ),
              ],
            ),
            const SizedBox(height: 12),

            Consumer<InvoiceProvider>(
              builder: (context, invoiceProvider, child) {
                final recentInvoices = invoiceProvider.invoices.take(5).toList();

                if (recentInvoices.isEmpty) {
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(AppSizes.paddingLarge),
                      child: Center(
                        child: Text(
                          l10n?.noInvoicesFound ?? 'No invoices found',
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Colors.grey[600],
                          ),
                        ),
                      ),
                    ),
                  );
                }

                return Column(
                  children: recentInvoices.map((invoice) {
                    return InvoiceListItem(
                      invoice: invoice.toJson(),
                      onTap: () => Navigator.of(context).pushNamed(
                        '/invoice-details',
                        arguments: invoice.toJson(),
                      ),
                    );
                  }).toList(),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInvoicesTab() {
    final l10n = AppLocalizations.of(context);

    return Column(
      children: [
        // Search and Filter Section
        Container(
          padding: const EdgeInsets.all(AppSizes.paddingMedium),
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.05),
                blurRadius: 4,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            children: [
              // Search Field
              TextField(
                controller: _searchController,
                decoration: InputDecoration(
                  hintText: l10n?.searchInvoices ?? 'Search invoices...',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear),
                          onPressed: () {
                            _searchController.clear();
                            _onSearchChanged('');
                          },
                        )
                      : null,
                ),
                onChanged: _onSearchChanged,
              ),

              const SizedBox(height: 12),

              // Status Filter
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    _buildFilterChip('All', null),
                    const SizedBox(width: 8),
                    _buildFilterChip(l10n?.draft ?? 'Draft', 'draft'),
                    const SizedBox(width: 8),
                    _buildFilterChip(l10n?.submitted ?? 'Submitted', 'submitted'),
                    const SizedBox(width: 8),
                    _buildFilterChip(l10n?.rejected ?? 'Rejected', 'rejected'),
                    const SizedBox(width: 8),
                    _buildFilterChip(l10n?.paid ?? 'Paid', 'paid'),
                  ],
                ),
              ),
            ],
          ),
        ),

        // Invoices List
        Expanded(
          child: Consumer<InvoiceProvider>(
            builder: (context, invoiceProvider, child) {
              if (invoiceProvider.isLoading && invoiceProvider.invoices.isEmpty) {
                return const Center(
                  child: CircularProgressIndicator(),
                );
              }

              if (invoiceProvider.error != null) {
                return Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(
                        Icons.error_outline,
                        size: 64,
                        color: AppColors.error,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        invoiceProvider.error!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: AppColors.error),
                      ),
                      const SizedBox(height: 16),
                      CustomButton(
                        text: 'Retry',
                        onPressed: _refreshData,
                      ),
                    ],
                  ),
                );
              }

              if (invoiceProvider.invoices.isEmpty) {
                return Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(
                        Icons.receipt_long,
                        size: 64,
                        color: Colors.grey,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        l10n?.noInvoicesFound ?? 'No invoices found',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          color: Colors.grey[600],
                        ),
                      ),
                      const SizedBox(height: 16),
                      CustomButton(
                        text: l10n?.createInvoice ?? 'Create Invoice',
                        onPressed: () => Navigator.of(context).pushNamed('/create-invoice'),
                      ),
                    ],
                  ),
                );
              }

              return RefreshIndicator(
                onRefresh: _refreshData,
                child: ListView.builder(
                  padding: const EdgeInsets.all(AppSizes.paddingMedium),
                  itemCount: invoiceProvider.invoices.length +
                             (invoiceProvider.hasMore ? 1 : 0),
                  itemBuilder: (context, index) {
                    if (index == invoiceProvider.invoices.length) {
                      // Load more indicator
                      return const Padding(
                        padding: EdgeInsets.all(AppSizes.paddingMedium),
                        child: Center(
                          child: CircularProgressIndicator(),
                        ),
                      );
                    }

                    final invoice = invoiceProvider.invoices[index];
                    return InvoiceListItem(
                      invoice: invoice.toJson(),
                      onTap: () => Navigator.of(context).pushNamed(
                        '/invoice-details',
                        arguments: invoice.toJson(),
                      ),
                    );
                  },
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildFilterChip(String label, String? value) {
    final isSelected = _selectedStatus == value;

    return FilterChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        _onStatusFilterChanged(selected ? value : null);
      },
      backgroundColor: Colors.grey[100],
      selectedColor: AppColors.primaryColor.withOpacity(0.2),
      checkmarkColor: AppColors.primaryColor,
    );
  }
}
