<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_customer_id')->constrained('pos_customers')->onDelete('cascade');

            // Transaction Identification
            $table->string('transaction_id'); // Original POS transaction ID
            $table->string('source_pos_system')->nullable(); // Which POS system this came from
            $table->string('source_file')->nullable(); // Source file/database

            // Transaction Data
            $table->timestamp('transaction_date');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            // Items & Amounts
            $table->json('items')->nullable(); // Array of items/services
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('tip_amount', 10, 2)->nullable();

            // Payment Information
            $table->string('payment_method')->nullable(); // Cash, Credit Card, etc.
            $table->string('payment_reference')->nullable(); // Payment ID/Reference
            $table->string('payment_status')->default('completed');

            // Business Context
            $table->string('location')->nullable(); // Store location, table number, etc.
            $table->string('employee')->nullable(); // Server, cashier, etc.
            $table->text('notes')->nullable();

            // Processing Status
            $table->boolean('invoice_created')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->json('processing_errors')->nullable();

            // Raw Data
            $table->json('raw_data')->nullable(); // Original transaction data from POS

            $table->timestamps();

            // Indexes
            $table->index(['pos_customer_id', 'transaction_date']);
            $table->index(['transaction_id']);
            $table->index(['invoice_created']);
            $table->index(['transaction_date']);

            // Unique constraint for transaction_id per customer
            $table->unique(['pos_customer_id', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
