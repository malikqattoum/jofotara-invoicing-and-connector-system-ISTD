<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix existing invoices that might not have proper vendor_id associations

        // First, let's update invoices that have organization_id but no vendor_id
        // We'll try to match them with users who have the same organization_id
        DB::statement("
            UPDATE invoices
            SET vendor_id = (
                SELECT id
                FROM users
                WHERE users.organization_id = invoices.organization_id
                LIMIT 1
            )
            WHERE vendor_id IS NULL
            AND organization_id IS NOT NULL
            AND EXISTS (
                SELECT 1
                FROM users
                WHERE users.organization_id = invoices.organization_id
            )
        ");

        // For invoices that still don't have vendor_id, let's assign them to the first user
        // This is a fallback for orphaned invoices
        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId) {
            DB::table('invoices')
                ->whereNull('vendor_id')
                ->update(['vendor_id' => $firstUserId]);
        }

        // Update organization_id for invoices that have vendor_id but no organization_id
        DB::statement("
            UPDATE invoices
            SET organization_id = COALESCE(
                (SELECT organization_id FROM users WHERE users.id = invoices.vendor_id),
                1
            )
            WHERE organization_id IS NULL
            AND vendor_id IS NOT NULL
        ");

        // Log the changes
        $updatedCount = DB::table('invoices')->whereNotNull('vendor_id')->count();
        \Illuminate\Support\Facades\Log::info("Fixed invoice vendor associations. Total invoices with vendor_id: {$updatedCount}");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration fixes data, so we don't reverse it
        // as it could cause data loss
    }
};
