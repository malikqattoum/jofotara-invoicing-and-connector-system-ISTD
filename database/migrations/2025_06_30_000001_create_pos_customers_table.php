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
        Schema::create('pos_customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->unique();
            $table->string('customer_name');
            $table->string('business_type')->nullable(); // restaurant, retail, medical, etc.
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            // POS Connector Configuration
            $table->string('api_key')->unique();
            $table->string('pos_connector_version')->nullable();
            $table->json('pos_systems_detected')->nullable(); // Array of detected POS systems
            $table->integer('sync_interval')->default(300); // seconds
            $table->boolean('debug_mode')->default(false);
            $table->boolean('auto_start')->default(true);
            $table->boolean('connector_active')->default(false);

            // Status Tracking
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('last_transaction_sync')->nullable();
            $table->integer('total_transactions_synced')->default(0);
            $table->json('connector_status')->nullable(); // Latest status info

            // Support Information
            $table->string('support_contact')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['api_key']);
            $table->index(['connector_active']);
            $table->index(['last_seen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_customers');
    }
};
