<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->enum('severity', ['info', 'warning', 'critical']);
            $table->string('message');
            $table->decimal('value', 15, 6)->nullable();
            $table->decimal('threshold', 15, 6)->nullable();
            $table->json('metadata')->nullable();
            $table->enum('status', ['active', 'resolved', 'acknowledged'])->default('active');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'severity']);
            $table->index(['type', 'status']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_alerts');
    }
};
