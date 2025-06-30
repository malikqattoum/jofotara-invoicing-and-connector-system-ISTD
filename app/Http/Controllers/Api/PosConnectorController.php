<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosCustomer;
use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PosConnectorController extends Controller
{
    /**
     * Receive transactions from POS Connector
     */
    public function receiveTransactions(Request $request)
    {
        // Authenticate customer via API key
        $customer = $this->authenticateCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'transactions' => 'required|array|min:1',
            'transactions.*.transaction_id' => 'required|string',
            'transactions.*.transaction_date' => 'required|date',
            'transactions.*.total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($request->transactions as $transactionData) {
                try {
                    // Check if transaction already exists
                    $existingTransaction = PosTransaction::where([
                        'pos_customer_id' => $customer->id,
                        'transaction_id' => $transactionData['transaction_id']
                    ])->first();

                    if ($existingTransaction) {
                        $skippedCount++;
                        continue;
                    }

                    // Create new transaction
                    $transaction = $this->createTransaction($customer, $transactionData);

                    // Auto-create invoice if enabled
                    if (config('pos.auto_create_invoices', true)) {
                        $transaction->createInvoice();
                    }

                    $processedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'transaction_id' => $transactionData['transaction_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];

                    Log::error('Failed to process POS transaction', [
                        'customer_id' => $customer->customer_id,
                        'transaction_data' => $transactionData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            // Update customer heartbeat
            $customer->updateHeartbeat([
                'last_sync' => now()->toISOString(),
                'transactions_processed' => $processedCount,
                'transactions_skipped' => $skippedCount,
                'transactions_errors' => $errorCount
            ]);

            return response()->json([
                'status' => 'success',
                'processed' => $processedCount,
                'skipped' => $skippedCount,
                'errors' => $errorCount,
                'error_details' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to process POS transactions batch', [
                'customer_id' => $customer->customer_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to process transactions',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connector heartbeat - track connector status
     */
    public function heartbeat(Request $request)
    {
        $customer = $this->authenticateCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Update connector status
        $status = [
            'version' => $request->input('version'),
            'pos_systems' => $request->input('pos_systems', []),
            'transactions_pending' => $request->input('transactions_pending', 0),
            'last_sync' => $request->input('last_sync'),
            'system_info' => $request->input('system_info', [])
        ];

        $customer->updateHeartbeat($status);

        // Return any configuration updates
        return response()->json([
            'status' => 'ok',
            'config_updates' => [
                'sync_interval' => $customer->sync_interval,
                'debug_mode' => $customer->debug_mode,
            ],
            'server_time' => now()->toISOString()
        ]);
    }

    /**
     * Get connector configuration
     */
    public function getConfig(Request $request)
    {
        $customer = $this->authenticateCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        return response()->json($customer->getConnectorConfig());
    }

    /**
     * Test connection endpoint
     */
    public function testConnection(Request $request)
    {
        $customer = $this->authenticateCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        return response()->json([
            'status' => 'connection_ok',
            'customer' => $customer->customer_name,
            'customer_id' => $customer->customer_id,
            'server_time' => now()->toISOString(),
            'message' => 'POS Connector is successfully connected to JoFotara!'
        ]);
    }

    /**
     * Get customer transaction statistics
     */
    public function getStats(Request $request)
    {
        $customer = $this->authenticateCustomer($request);
        if (!$customer) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $stats = [
            'total_transactions' => $customer->transactions()->count(),
            'today_transactions' => $customer->todaysTransactions()->count(),
            'weekly_transactions' => $customer->weeklyTransactions()->count(),
            'total_revenue' => $customer->transactions()->sum('total_amount'),
            'invoices_created' => $customer->transactions()->where('invoice_created', true)->count(),
            'last_transaction' => $customer->transactions()->latest('transaction_date')->first()?->transaction_date,
            'connector_uptime' => $customer->last_seen?->diffForHumans(),
        ];

        return response()->json($stats);
    }

    /**
     * Authenticate customer by API key
     */
    private function authenticateCustomer(Request $request): ?PosCustomer
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$apiKey) {
            return null;
        }

        return PosCustomer::where('api_key', $apiKey)->first();
    }

    /**
     * Create POS transaction from data
     */
    private function createTransaction(PosCustomer $customer, array $data): PosTransaction
    {
        // Normalize transaction data
        $transactionData = [
            'pos_customer_id' => $customer->id,
            'transaction_id' => $data['transaction_id'],
            'source_pos_system' => $data['pos_system'] ?? $data['source_pos_system'] ?? null,
            'source_file' => $data['source_file'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'customer_name' => $data['customer_name'] ?? $data['customer'] ?? null,
            'customer_email' => $data['customer_email'] ?? $data['email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? $data['phone'] ?? null,
            'items' => $data['items'] ?? null,
            'subtotal' => $data['subtotal'] ?? null,
            'tax_amount' => $data['tax_amount'] ?? $data['tax'] ?? null,
            'total_amount' => $data['total_amount'] ?? $data['total'] ?? $data['amount'],
            'tip_amount' => $data['tip_amount'] ?? $data['tip'] ?? null,
            'payment_method' => $data['payment_method'] ?? $data['payment_type'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? $data['payment_id'] ?? null,
            'payment_status' => $data['payment_status'] ?? 'completed',
            'location' => $data['location'] ?? $data['table_number'] ?? $data['store_location'] ?? null,
            'employee' => $data['employee'] ?? $data['server_name'] ?? $data['cashier'] ?? null,
            'notes' => $data['notes'] ?? null,
            'raw_data' => $data, // Store original data
        ];

        return PosTransaction::create($transactionData);
    }
}
