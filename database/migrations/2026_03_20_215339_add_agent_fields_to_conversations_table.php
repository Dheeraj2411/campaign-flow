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
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            }
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('locked_at')->nullable();
            $table->foreignId('is_typing_agent_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropForeign(['is_typing_agent_id']);
            $table->dropColumn(['locked_by', 'locked_at', 'is_typing_agent_id']);
        });
    }
};
