<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\PosCustomer;
use App\Models\PosTransaction;
use Carbon\Carbon;

class PosConnectorTest extends TestCase
{
    use RefreshDatabase;

    private PosCustomer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test customer
        $this->customer = PosCustomer::create([
            'customer_id' => 'TEST_001',
            'customer_name' => 'Test Restaurant',
            'business_type' => 'restaurant',
            'email' => 'test@restaurant.com',
            'api_key' => 'test-api-key-123',
            'sync_interval' => 300,
        ]);
    }

    /** @test */
    public function can_receive_transactions_from_connector()
    {
        $transactionData = [
            'transactions' => [
                [
                    'transaction_id' => 'TXN001',
                    'transaction_date' => now()->toISOString(),
                    'customer_name' => 'John Doe',
                    'total_amount' => 25.99,
                    'payment_method' => 'Credit Card',
                    'items' => [
                        [
                            'description' => 'Pizza Margherita',
                            'quantity' => 1,
                            'unit_price' => 18.99,
                            'total' => 18.99
                        ],
                        [
                            'description' => 'Coke',
                            'quantity' => 1,
                            'unit_price' => 7.00,
                            'total' => 7.00
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->withHeaders([
            'X-API-Key' => $this->customer->api_key,
        ])->postJson('/api/pos-connector/transactions', $transactionData);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'processed' => 1,
                    'skipped' => 0,
                    'errors' => 0
                ]);

        // Check transaction was created
        $this->assertDatabaseHas('pos_transactions', [
            'pos_customer_id' => $this->customer->id,
            'transaction_id' => 'TXN001',
            'total_amount' => 25.99
        ]);
    }

    /** @test */
    public function rejects_transactions_with_invalid_api_key()
    {
        $transactionData = [
            'transactions' => [
                [
                    'transaction_id' => 'TXN001',
                    'transaction_date' => now()->toISOString(),
                    'total_amount' => 25.99,
                ]
            ]
        ];

        $response = $this->withHeaders([
            'X-API-Key' => 'invalid-api-key',
        ])->postJson('/api/pos-connector/transactions', $transactionData);

        $response->assertStatus(401)
                ->assertJson(['error' => 'Invalid API key']);
    }

    /** @test */
    public function handles_duplicate_transactions()
    {
        // Create existing transaction
        PosTransaction::create([
            'pos_customer_id' => $this->customer->id,
            'transaction_id' => 'TXN001',
            'transaction_date' => now(),
            'total_amount' => 25.99,
        ]);

        $transactionData = [
            'transactions' => [
                [
                    'transaction_id' => 'TXN001',
                    'transaction_date' => now()->toISOString(),
                    'total_amount' => 25.99,
                ]
            ]
        ];

        $response = $this->withHeaders([
            'X-API-Key' => $this->customer->api_key,
        ])->postJson('/api/pos-connector/transactions', $transactionData);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'processed' => 0,
                    'skipped' => 1,
                    'errors' => 0
                ]);
    }

    /** @test */
    public function can_send_heartbeat()
    {
        $heartbeatData = [
            'version' => '2.0.0',
            'pos_systems' => ['Restaurant POS v1.0'],
            'transactions_pending' => 5,
            'system_info' => ['os' => 'Windows 11']
        ];

        $response = $this->withHeaders([
            'X-API-Key' => $this->customer->api_key,
        ])->postJson('/api/pos-connector/heartbeat', $heartbeatData);

        $response->assertStatus(200)
                ->assertJson(['status' => 'ok']);

        // Check customer was updated
        $this->customer->refresh();
        $this->assertNotNull($this->customer->last_seen);
        $this->assertTrue($this->customer->connector_active);
    }

    /** @test */
    public function can_test_connection()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->customer->api_key,
        ])->getJson('/api/pos-connector/test');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'connection_ok',
                    'customer' => $this->customer->customer_name,
                    'customer_id' => $this->customer->customer_id
                ]);
    }

    /** @test */
    public function can_get_connector_config()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->customer->api_key,
        ])->getJson('/api/pos-connector/config');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'customer_id',
                    'customer_name',
                    'api_url',
                    'api_key',
                    'sync_interval',
                    'debug_mode',
                    'auto_start'
                ]);
    }

    /** @test */
    public function can_get_stats()
    {
        // Create some test transactions
        PosTransaction::create([
            'pos_customer_id' => $this->customer->id,
            'transaction_id' => 'TXN001',
            'transaction_date' => now(),
            'total_amount' => 25.99,
        ]);

        PosTransaction::create([
            'pos_customer_id' => $this->customer->id,
            'transaction_id' => 'TXN002',
            'transaction_date' => now(),
            'total_amount' => 15.50,
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->customer->api_key,
        ])->getJson('/api/pos-connector/stats');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_transactions',
                    'today_transactions',
                    'weekly_transactions',
                    'total_revenue',
                    'invoices_created'
                ]);
    }
}
