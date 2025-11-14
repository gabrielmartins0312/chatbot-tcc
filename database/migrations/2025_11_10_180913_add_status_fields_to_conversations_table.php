<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'sender')) {
                $table->string('sender')->nullable()->after('business_id');
            }

            if (!Schema::hasColumn('conversations', 'awaiting_satisfaction')) {
                $table->boolean('awaiting_satisfaction')->default(false)->after('concluded');
            }

            if (!Schema::hasColumn('conversations', 'awaiting_closure_confirmation')) {
                $table->boolean('awaiting_closure_confirmation')->default(false)->after('awaiting_satisfaction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['sender', 'awaiting_satisfaction', 'awaiting_closure_confirmation']);
        });
    }
};
