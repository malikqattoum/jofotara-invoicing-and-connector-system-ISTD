<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pipeline_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('data_pipelines')->onDelete('cascade');
            $table->enum('status', ['running', 'completed', 'failed']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->integer('records_processed')->default(0);
            $table->integer('records_success')->default(0);
            $table->integer('records_failed')->default(0);
            $table->json('metrics')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['pipeline_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pipeline_executions');
    }
};
