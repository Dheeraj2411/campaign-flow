<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->index(['campaign_id', 'status', 'created_at'], 'ml_campaign_status_created');
        });

        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at'], 'cm_conversation_created');
        });

        Schema::table('message_status_history', function (Blueprint $table) {
            $table->index(['message_log_id', 'occurred_at'], 'msh_log_occurred');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['workspace_id', 'status', 'updated_at'], 'conv_workspace_status_updated');
            $table->index(['assigned_to', 'status'], 'conv_assigned_status');
        });

        Schema::table('contacts', function (Blueprint $table) {
            DB::statement('CREATE INDEX IF NOT EXISTS contacts_tags_gin ON contacts USING GIN (tags)');
            DB::statement('CREATE INDEX IF NOT EXISTS contacts_custom_attrs_gin ON contacts USING GIN (custom_attributes)');
            $table->index(['workspace_id', 'created_at'], 'contacts_workspace_created');
        });

        Schema::table('workflows', function (Blueprint $table) {
            $table->index(['workspace_id', 'trigger', 'is_active'], 'wf_workspace_trigger_active');
            DB::statement('CREATE INDEX IF NOT EXISTS workflows_actions_gin ON workflows USING GIN (actions)');
        });

        Schema::table('workspaces', function (Blueprint $table) {
            DB::statement('CREATE INDEX IF NOT EXISTS workspaces_settings_gin ON workspaces USING GIN (settings)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            DB::statement('DROP INDEX IF EXISTS workspaces_settings_gin');
        });

        Schema::table('workflows', function (Blueprint $table) {
            DB::statement('DROP INDEX IF EXISTS workflows_actions_gin');
            $table->dropIndex('wf_workspace_trigger_active');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_workspace_created');
            DB::statement('DROP INDEX IF EXISTS contacts_custom_attrs_gin');
            DB::statement('DROP INDEX IF EXISTS contacts_tags_gin');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conv_assigned_status');
            $table->dropIndex('conv_workspace_status_updated');
        });

        Schema::table('message_status_history', function (Blueprint $table) {
            $table->dropIndex('msh_log_occurred');
        });

        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->dropIndex('cm_conversation_created');
        });

        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropIndex('ml_campaign_status_created');
        });
    }
};
