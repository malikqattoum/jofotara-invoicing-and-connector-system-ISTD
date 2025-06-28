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
        Schema::table('sync_logs', function (Blueprint $table) {
            // Add invoice_id field if it doesn't exist
            if (!Schema::hasColumn('sync_logs', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('integration_setting_id');
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
                $table->index(['invoice_id', 'status']);
            }

            // Rename integration_setting_id to integration_id if needed
            if (Schema::hasColumn('sync_logs', 'integration_setting_id') &&
                !Schema::hasColumn('sync_logs', 'integration_id')) {
                $table->renameColumn('integration_setting_id', 'integration_id');
            }

            // Add missing fields from the model
            if (!Schema::hasColumn('sync_logs', 'result_message')) {
                $table->text('result_message')->nullable()->after('error_message');
            }

            if (!Schema::hasColumn('sync_logs', 'error_details')) {
                $table->json('error_details')->nullable()->after('error_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            // Drop foreign keys and indexes
            if (Schema::hasColumn('sync_logs', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropIndex(['invoice_id', 'status']);
                $table->dropColumn('invoice_id');
            }

            // Rename back if needed
            if (Schema::hasColumn('sync_logs', 'integration_id') &&
                !Schema::hasColumn('sync_logs', 'integration_setting_id')) {
                $table->renameColumn('integration_id', 'integration_setting_id');
            }

            // Drop added fields
            if (Schema::hasColumn('sync_logs', 'result_message')) {
                $table->dropColumn('result_message');
            }

            if (Schema::hasColumn('sync_logs', 'error_details')) {
                $table->dropColumn('error_details');
            }
        });
    }
};
