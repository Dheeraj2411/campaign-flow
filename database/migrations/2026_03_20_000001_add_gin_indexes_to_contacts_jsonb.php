<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add GIN indexes on jsonb columns for fast containment queries.
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS contacts_tags_gin ON contacts USING GIN (tags)');
        DB::statement('CREATE INDEX IF NOT EXISTS contacts_custom_attributes_gin ON contacts USING GIN (custom_attributes)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS contacts_tags_gin');
        DB::statement('DROP INDEX IF EXISTS contacts_custom_attributes_gin');
    }
};
