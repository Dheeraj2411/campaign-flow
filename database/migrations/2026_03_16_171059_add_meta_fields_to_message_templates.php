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
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('language', 10)->default('en_US');
            $table->string('category', 50)->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->string('meta_template_id')->nullable();
            $table->jsonb('content_structure')->nullable();
            $table->text('reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn([
                'language',
                'category',
                'status',
                'meta_template_id',
                'content_structure',
                'reason'
            ]);
        });
    }
};
