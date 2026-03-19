<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->index(['campaign_id', 'status'], 'message_logs_campaign_status_idx');
            $table->index('contact_id', 'message_logs_contact_id_idx');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['workspace_id', 'contact_id', 'platform'], 'conversations_workspace_contact_platform_idx');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['workspace_id', 'status', 'scheduled_at'], 'campaigns_workspace_status_scheduled_idx');
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropIndex('message_logs_campaign_status_idx');
            $table->dropIndex('message_logs_contact_id_idx');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_workspace_contact_platform_idx');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_workspace_status_scheduled_idx');
        });
    }
};
