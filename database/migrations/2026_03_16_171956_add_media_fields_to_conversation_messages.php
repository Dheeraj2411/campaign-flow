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
        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->string('type')->default('text')->after('direction');
            $table->text('media_url')->nullable()->after('body');
            $table->text('caption')->nullable()->after('media_url');
            $table->string('platform_message_id')->nullable()->index()->after('caption');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'media_url', 'caption', 'platform_message_id']);
        });
    }
};
