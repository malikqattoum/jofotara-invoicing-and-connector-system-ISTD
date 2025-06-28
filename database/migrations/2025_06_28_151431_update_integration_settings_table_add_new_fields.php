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
        Schema::table('integration_settings', function (Blueprint $table) {
            // Add missing fields
            $table->unsignedBigInteger('user_id')->nullable()->after('organization_id');
            $table->unsignedBigInteger('vendor_id')->nullable()->after('user_id');
            $table->string('vendor_name')->nullable()->after('vendor_id');
            $table->string('integration_type')->nullable()->after('vendor_name');
            $table->string('sync_frequency')->default('manual')->after('last_sync_at');
            $table->boolean('auto_sync_enabled')->default(false)->after('sync_frequency');
            $table->string('status')->default('pending')->after('auto_sync_enabled');
            $table->timestamp('last_sync_started_at')->nullable()->after('last_sync_at');
            $table->timestamp('last_tested_at')->nullable()->after('last_sync_started_at');

            // Add indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'sync_status']);

            // Add foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integration_settings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['vendor_id', 'status']);
            $table->dropIndex(['status', 'sync_status']);

            $table->dropColumn([
                'user_id',
                'vendor_id',
                'vendor_name',
                'integration_type',
                'sync_frequency',
                'auto_sync_enabled',
                'status',
                'last_sync_started_at',
                'last_tested_at'
            ]);
        });
    }
};
