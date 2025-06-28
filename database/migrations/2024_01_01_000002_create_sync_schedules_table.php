<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sync_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_setting_id')->constrained()->onDelete('cascade');
            $table->enum('sync_type', ['invoices', 'customers', 'all']);
            $table->enum('frequency', ['hourly', 'daily', 'weekly', 'monthly', 'custom']);
            $table->integer('frequency_value')->nullable(); // For hourly intervals
            $table->time('time_of_day')->nullable(); // For daily/weekly/monthly
            $table->tinyInteger('day_of_week')->nullable(); // 0=Sunday, 1=Monday, etc.
            $table->tinyInteger('day_of_month')->nullable(); // 1-31
            $table->string('timezone')->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->json('filters')->nullable(); // Additional sync filters
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['integration_setting_id', 'is_active']);
            $table->index(['next_run_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sync_schedules');
    }
};
