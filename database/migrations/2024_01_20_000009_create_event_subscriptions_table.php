<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_id')->unique();
            $table->string('stream_name');
            $table->json('filters')->nullable();
            $table->enum('status', ['active', 'inactive', 'paused']);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('stream_name')->references('name')->on('event_streams')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['stream_name', 'status']);
            $table->index(['created_by', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_subscriptions');
    }
};
