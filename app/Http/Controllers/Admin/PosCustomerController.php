<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosCustomer;
use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PosCustomerController extends Controller
{
    /**
     * Display POS customers dashboard
     */
    public function index(Request $request)
    {
        $query = PosCustomer::with(['transactions' => function($q) {
            $q->whereDate('created_at', today());
        }]);

        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->recentlyActive();
                    break;
                case 'inactive':
                    $query->where(function($q) {
                        $q->whereNull('last_seen')
                          ->orWhere('last_seen', '<', now()->subMinutes(10));
                    });
                    break;
                case 'never_connected':
                    $query->whereNull('last_seen');
                    break;
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_id', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(15);

        // Dashboard statistics
        $stats = [
            'total_customers' => PosCustomer::count(),
            'active_connectors' => PosCustomer::recentlyActive()->count(),
            'today_transactions' => PosTransaction::whereDate('created_at', today())->count(),
            'total_revenue_today' => PosTransaction::whereDate('created_at', today())->sum('total_amount'),
        ];

        return view('admin.pos-customers.index', compact('customers', 'stats'));
    }

    /**
     * Show create customer form
     */
    public function create()
    {
        return view('admin.pos-customers.create');
    }

    /**
     * Store new POS customer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'business_type' => 'nullable|string|max:100',
            'email' => 'required|email|unique:pos_customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'sync_interval' => 'integer|min:60|max:3600',
            'debug_mode' => 'boolean',
            'auto_start' => 'boolean',
            'support_contact' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer = PosCustomer::create([
            ...$validated,
            'customer_id' => PosCustomer::generateCustomerId(),
            'api_key' => PosCustomer::generateApiKey(),
            'sync_interval' => $validated['sync_interval'] ?? 300,
            'debug_mode' => $validated['debug_mode'] ?? false,
            'auto_start' => $validated['auto_start'] ?? true,
        ]);

        return redirect()
            ->route('admin.pos-customers.show', $customer)
            ->with('success', 'POS Customer created successfully! You can now generate their connector package.');
    }

    /**
     * Show customer details
     */
    public function show(PosCustomer $posCustomer)
    {
        $posCustomer->load(['transactions' => function($q) {
            $q->latest()->take(10);
        }]);

        // Statistics
        $stats = [
            'total_transactions' => $posCustomer->transactions()->count(),
            'today_transactions' => $posCustomer->todaysTransactions()->count(),
            'weekly_transactions' => $posCustomer->weeklyTransactions()->count(),
            'total_revenue' => $posCustomer->transactions()->sum('total_amount'),
            'avg_transaction' => $posCustomer->transactions()->avg('total_amount'),
            'last_sync' => $posCustomer->last_transaction_sync,
        ];

        return view('admin.pos-customers.show', compact('posCustomer', 'stats'));
    }

    /**
     * Show edit form
     */
    public function edit(PosCustomer $posCustomer)
    {
        return view('admin.pos-customers.edit', compact('posCustomer'));
    }

    /**
     * Update customer
     */
    public function update(Request $request, PosCustomer $posCustomer)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'business_type' => 'nullable|string|max:100',
            'email' => 'required|email|unique:pos_customers,email,' . $posCustomer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'sync_interval' => 'integer|min:60|max:3600',
            'debug_mode' => 'boolean',
            'auto_start' => 'boolean',
            'support_contact' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $posCustomer->update($validated);

        return redirect()
            ->route('admin.pos-customers.show', $posCustomer)
            ->with('success', 'Customer updated successfully!');
    }

    /**
     * Delete customer
     */
    public function destroy(PosCustomer $posCustomer)
    {
        $customerName = $posCustomer->customer_name;
        $posCustomer->delete();

        return redirect()
            ->route('admin.pos-customers.index')
            ->with('success', "Customer '{$customerName}' deleted successfully!");
    }

    /**
     * Generate connector package for customer
     */
    public function generatePackage(PosCustomer $posCustomer)
    {
        try {
            $packagePath = $this->createConnectorPackage($posCustomer);

            return response()->download($packagePath)->deleteFileAfterSend();

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate package: ' . $e->getMessage());
        }
    }

    /**
     * Regenerate API key
     */
    public function regenerateApiKey(PosCustomer $posCustomer)
    {
        $posCustomer->update([
            'api_key' => PosCustomer::generateApiKey($posCustomer->customer_id)
        ]);

        return back()->with('success', 'API key regenerated successfully! Customer will need to reinstall the connector.');
    }

    /**
     * View customer transactions
     */
    public function transactions(PosCustomer $posCustomer, Request $request)
    {
        $query = $posCustomer->transactions();

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by processing status
        if ($request->filled('processed')) {
            $query->where('invoice_created', $request->processed === 'yes');
        }

        $transactions = $query->latest('transaction_date')->paginate(20);

        return view('admin.pos-customers.transactions', compact('posCustomer', 'transactions'));
    }

    /**
     * Process unprocessed transactions
     */
    public function processTransactions(PosCustomer $posCustomer)
    {
        $unprocessed = $posCustomer->transactions()->unprocessed()->get();
        $processed = 0;
        $errors = 0;

        foreach ($unprocessed as $transaction) {
            if ($transaction->createInvoice()) {
                $processed++;
            } else {
                $errors++;
            }
        }

        $message = "Processed {$processed} transactions";
        if ($errors > 0) {
            $message .= " ({$errors} errors)";
        }

        return back()->with('success', $message);
    }

    /**
     * Create connector package for customer
     */
    private function createConnectorPackage(PosCustomer $posCustomer): string
    {
        $tempDir = storage_path('app/temp/pos-packages');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $packageDir = $tempDir . '/' . $posCustomer->customer_id;
        if (is_dir($packageDir)) {
            $this->deleteDirectory($packageDir);
        }
        mkdir($packageDir, 0755, true);

        // Create customer config
        $config = $posCustomer->getConnectorConfig();
        file_put_contents(
            $packageDir . '/customer_config.json',
            json_encode($config, JSON_PRETTY_PRINT)
        );

        // Copy connector executable (if exists)
        $exePath = base_path('pos-connector/dist/JoFotara_POS_Connector.exe');
        if (file_exists($exePath)) {
            copy($exePath, $packageDir . '/JoFotara_POS_Connector.exe');
        }

        // Create installer script
        $installerScript = $this->generateInstallerScript($posCustomer);
        file_put_contents($packageDir . '/install.bat', $installerScript);

        // Create README
        $readme = $this->generateReadme($posCustomer);
        file_put_contents($packageDir . '/README.txt', $readme);

        // Create ZIP package
        $zipPath = $tempDir . "/JoFotara_POS_Connector_{$posCustomer->customer_id}.zip";
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($packageDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($packageDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
        }

        // Clean up temp directory
        $this->deleteDirectory($packageDir);

        return $zipPath;
    }

    /**
     * Generate installer script for customer
     */
    private function generateInstallerScript(PosCustomer $posCustomer): string
    {
        return "@echo off
title JoFotara POS Connector - {$posCustomer->customer_name} Installation

echo ========================================
echo   JoFotara POS Connector Installer
echo   Customer: {$posCustomer->customer_name}
echo   ID: {$posCustomer->customer_id}
echo ========================================
echo.

set INSTALL_DIR=C:\\JoFotara\\POS_Connector
set PROGRAM_NAME=JoFotara_POS_Connector.exe
set CUSTOMER_CONFIG=customer_config.json

echo Installing JoFotara POS Connector for {$posCustomer->customer_name}...
echo Installation directory: %INSTALL_DIR%
echo.

:: Check for admin rights
net session >nul 2>&1
if %errorLevel% == 0 (
    echo ✅ Administrator privileges confirmed
) else (
    echo ❌ ERROR: This installer must be run as Administrator
    echo Right-click install.bat and select \"Run as administrator\"
    echo.
    pause
    exit /b 1
)

:: Create installation directory
echo Creating installation directory...
if not exist \"%INSTALL_DIR%\" mkdir \"%INSTALL_DIR%\"

:: Copy executable
echo Copying JoFotara POS Connector...
copy \"%~dp0%PROGRAM_NAME%\" \"%INSTALL_DIR%\\%PROGRAM_NAME%\"

if errorlevel 1 (
    echo ❌ ERROR: Failed to copy executable
    pause
    exit /b 1
)

:: Copy customer configuration
echo Configuring for {$posCustomer->customer_name}...
copy \"%~dp0%CUSTOMER_CONFIG%\" \"%INSTALL_DIR%\\%CUSTOMER_CONFIG%\"

if errorlevel 1 (
    echo ❌ ERROR: Failed to copy configuration
    pause
    exit /b 1
)

echo.
echo 🎉 Installation completed successfully!
echo.
echo The JoFotara POS Connector will now automatically:
echo - Detect your POS systems
echo - Sync transaction data
echo - Connect to JoFotara invoicing system
echo.
echo For support, contact us at:
echo Email: " . config('mail.from.address') . "
echo.
pause";
    }

    /**
     * Generate README for customer
     */
    private function generateReadme(PosCustomer $posCustomer): string
    {
        return "========================================
  JoFotara POS Connector - {$posCustomer->customer_name}
========================================

CUSTOMER INFORMATION:
- Customer Name: {$posCustomer->customer_name}
- Customer ID: {$posCustomer->customer_id}
- API Key: {$posCustomer->api_key}
- Created: " . $posCustomer->created_at->format('Y-m-d H:i:s') . "

INSTALLATION INSTRUCTIONS:
1. Right-click \"install.bat\" and select \"Run as administrator\"
2. Follow the installation prompts
3. The connector will install to: C:\\JoFotara\\POS_Connector
4. The connector will automatically start detecting your POS systems

WHAT THIS CONNECTOR DOES:
✅ Automatically detects your POS systems
✅ Extracts transaction data securely
✅ Syncs data to JoFotara invoicing system
✅ Works with ANY POS software (universal compatibility)
✅ Runs continuously in the background
✅ No manual intervention required

SUPPORT INFORMATION:
- Email: " . config('mail.from.address') . "
- Created: " . now()->format('Y-m-d H:i:s') . "

© 2025 JoFotara - Universal POS Integration System";
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
