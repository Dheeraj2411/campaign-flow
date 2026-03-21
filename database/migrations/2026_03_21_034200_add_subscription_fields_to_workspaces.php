<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('subscription_status')->default('trial');
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('subscription_ends_at')->nullable();
            $table->integer('monthly_message_limit')->default(1000);
            $table->integer('messages_sent_this_month')->default(0);
            $table->timestampTz('limit_reset_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'plan_id',
                'subscription_status',
                'trial_ends_at',
                'subscription_ends_at',
                'monthly_message_limit',
                'messages_sent_this_month',
                'limit_reset_at'
            ]);
        });
    }
};
