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
        Schema::table('workflow_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_steps', 'conditions')) {
                $table->json('conditions')->nullable()->after('configuration');
            }
            if (!Schema::hasColumn('workflow_steps', 'continue_on_failure')) {
                $table->boolean('continue_on_failure')->default(false)->after('conditions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_steps', 'conditions')) {
                $table->dropColumn('conditions');
            }
            if (Schema::hasColumn('workflow_steps', 'continue_on_failure')) {
                $table->dropColumn('continue_on_failure');
            }
        });
    }
};
