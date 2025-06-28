<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type');
            $table->json('configuration');
            $table->json('conditions')->nullable();
            $table->integer('order');
            $table->boolean('continue_on_failure')->default(false);
            $table->timestamps();

            $table->index(['workflow_id', 'order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_steps');
    }
};
