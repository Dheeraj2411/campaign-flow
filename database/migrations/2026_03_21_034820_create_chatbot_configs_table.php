<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->text('openai_api_key')->nullable();
            $table->string('model')->default('gpt-4o-mini');
            $table->text('system_prompt')->nullable();
            $table->jsonb('trigger_keywords')->nullable();
            $table->boolean('off_hours_only')->default(false);
            $table->string('escalate_keyword')->default('human');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('chatbot_configs');
    }
};
