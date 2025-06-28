<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('streamed_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('stream_name');
            $table->string('event_type');
            $table->json('event_data');
            $table->json('metadata')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->foreign('stream_name')->references('name')->on('event_streams')->onDelete('cascade');
            $table->index(['stream_name', 'event_type']);
            $table->index(['stream_name', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('streamed_events');
    }
};
