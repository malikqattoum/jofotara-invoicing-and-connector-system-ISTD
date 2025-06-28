<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor_type'); // xero, quickbooks, sap, etc.
            $table->json('configuration')->nullable(); // API credentials, tokens, etc.
            $table->json('field_mappings')->nullable(); // Custom field mappings
            $table->string('sync_status')->default('pending'); // pending, running, completed, failed
            $table->text('last_error')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integration_settings');
            $table->string('invoice_number');
            $table->string('status'); // success, failed, failed_permanently
            $table->json('request_data');
            $table->json('response_data');
            $table->timestamps();
            $table->index(['integration_id', 'invoice_number']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
        Schema::dropIfExists('integration_settings');
    }
};
