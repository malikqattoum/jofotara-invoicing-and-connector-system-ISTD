<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_setting_id')->constrained()->onDelete('cascade');
            $table->string('sync_type'); // 'invoices', 'customers', 'all'
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->integer('records_processed')->default(0);
            $table->decimal('duration_seconds', 8, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional sync details
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['integration_setting_id', 'status']);
            $table->index(['integration_setting_id', 'sync_type']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sync_logs');
    }
};
