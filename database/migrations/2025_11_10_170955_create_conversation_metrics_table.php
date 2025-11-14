<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversation_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('avg_response_time_ms')->nullable();
            $table->unsignedInteger('total_messages')->nullable();
            $table->unsignedInteger('user_messages')->nullable();
            $table->unsignedInteger('bot_messages')->nullable();
            $table->unsignedInteger('total_duration_ms')->nullable();
            $table->enum('mode', ['bot', 'humano'])->default('bot');
            $table->boolean('escalated')->default(false);
            $table->unsignedTinyInteger('satisfaction')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_metrics');
    }
};
