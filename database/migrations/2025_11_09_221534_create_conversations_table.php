<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('set null');
            $table->string('channel')->default('whatsapp');
            $table->enum('mode', ['bot', 'humano'])->default('bot');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('first_response_ms')->nullable();
            $table->integer('total_duration_ms')->nullable();
            $table->boolean('escalated')->default(false);
            $table->boolean('concluded')->default(false);
            $table->tinyInteger('satisfaction')->nullable()->comment('1 a 5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
