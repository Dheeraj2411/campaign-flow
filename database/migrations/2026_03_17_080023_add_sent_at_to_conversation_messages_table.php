<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('conversation_messages', 'sent_at')) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->timestampTz('sent_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('conversation_messages', 'sent_at')) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->dropColumn('sent_at');
            });
        }
    }
};
