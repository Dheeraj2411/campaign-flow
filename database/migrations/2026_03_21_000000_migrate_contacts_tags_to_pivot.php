<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Get all contacts that have tags in JSONB
            $contacts = DB::table('contacts')
                ->whereNotNull('tags')
                ->get(['id', 'tags', 'workspace_id']);

            foreach ($contacts as $contact) {
                $tagsArray = json_decode($contact->tags, true);
                if (is_array($tagsArray) && !empty($tagsArray)) {
                    foreach ($tagsArray as $tagName) {
                        // Find the tag id for this workspace
                        $tag = DB::table('tags')
                            ->where('workspace_id', $contact->workspace_id)
                            ->where('name', $tagName)
                            ->first();

                        if ($tag) {
                            // Insert into pivot
                            DB::table('contact_tag')->insertOrIgnore([
                                'contact_id' => $contact->id,
                                'tag_id' => $tag->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data migration
    }
};
