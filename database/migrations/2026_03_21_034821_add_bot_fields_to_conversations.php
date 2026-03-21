<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('bot_active')->default(true);
            $table->timestampTz('bot_escalated_at')->nullable();
        });
    }

    public function down(): void {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['bot_active', 'bot_escalated_at']);
        });
    }
};
