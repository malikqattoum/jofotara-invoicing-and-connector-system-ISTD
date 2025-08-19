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
            if (!Schema::hasColumn('sync_logs', 'integration_id')) {
                $table->unsignedBigInteger('integration_id')->nullable()->after('invoice_id');
                $table->foreign('integration_id')->references('id')->on('integration_settings')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sync_logs', 'integration_id')) {
                $table->dropForeign(['integration_id']);
                $table->dropColumn('integration_id');
            }
        });
    }
};
