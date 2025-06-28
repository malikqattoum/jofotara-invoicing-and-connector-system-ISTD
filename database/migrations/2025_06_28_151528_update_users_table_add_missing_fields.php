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
        Schema::table('users', function (Blueprint $table) {
            // Add missing fields that are used in the application
            $table->string('company_name')->nullable()->after('name');
            $table->text('address')->nullable()->after('email');
            $table->string('phone')->nullable()->after('address');
            $table->json('settings')->nullable()->after('phone');
            $table->string('role')->default('user')->after('settings');
            $table->boolean('is_admin')->default(false)->after('is_active');
            $table->unsignedBigInteger('organization_id')->nullable()->after('is_admin');

            // Add index for performance
            $table->index(['is_admin', 'is_active']);
            $table->index('organization_id');

            // Add foreign key if organizations table exists
            if (Schema::hasTable('organizations')) {
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key if it exists
            if (Schema::hasTable('organizations')) {
                $table->dropForeign(['organization_id']);
            }

            $table->dropIndex(['is_admin', 'is_active']);
            $table->dropIndex(['organization_id']);

            $table->dropColumn([
                'company_name',
                'address',
                'phone',
                'settings',
                'role',
                'is_admin',
                'organization_id'
            ]);
        });
    }
};
