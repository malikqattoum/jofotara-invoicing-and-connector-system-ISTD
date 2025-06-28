<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->decimal('value', 15, 6);
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at');
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->timestamps();

            $table->foreign('integration_id')->references('id')->on('integration_settings')->onDelete('set null');
            $table->index(['type', 'recorded_at']);
            $table->index(['integration_id', 'type']);
            $table->index(['recorded_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_metrics');
    }
};
