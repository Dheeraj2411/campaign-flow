<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportContactsFromCsv implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $path,
        protected int $workspaceId
    ) {}

    public function handle(): void
    {
        try {
            $this->process();
        } catch (\Throwable $e) {
            Log::error("Import Job Error: " . $e->getMessage(), [
                'exception' => $e,
                'path' => $this->path,
                'workspace_id' => $this->workspaceId
            ]);
            
            // Update workspace with last error for UI feedback
            \App\Models\Workspace::find($this->workspaceId)?->update([
                'last_import_error' => $e->getMessage()
            ]);
            
            throw $e; // Re-throw to mark job as failed
        }
    }

    protected function process(): void
    {
        $fullPath = Storage::path($this->path);
        
        if (!file_exists($fullPath)) {
            $msg = "Import failed: File not found at {$fullPath}";
            Log::error($msg);
            throw new \Exception($msg);
        }

        $file = fopen($fullPath, 'r');
        $header = fgetcsv($file);
        if (!$header) {
            fclose($file);
            return;
        }

        // Map common headers (case-insensitive)
        $normalizedHeader = array_map(function($h) {
            // Remove BOM and non-ASCII / non-printable characters
            $h = preg_replace('/[^\x20-\x7E]/', '', $h);
            $h = strtolower(trim($h));
            return str_replace([' ', '_', '-'], '', $h);
        }, $header);

        $nameIndex = $this->findIndex($normalizedHeader, ['name', 'fullname', 'contactname', 'displayname']);
        $phoneIndex = $this->findIndex($normalizedHeader, ['phone', 'phonenumber', 'number', 'whatsapp', 'whatsappnumber', 'mobile', 'mobilenumber']);
        $telegramIndex = $this->findIndex($normalizedHeader, ['telegram', 'telegramusername', 'username', 'tg']);
        $tagsIndex = $this->findIndex($normalizedHeader, ['tags', 'tag', 'label', 'labels']);

        // We require at least phone and name
        if ($nameIndex === false || $phoneIndex === false) {
            fclose($file);
            $cleanedHeaders = array_map('trim', $header);
            $msg = "Import failed: CSV headers must include 'name' and 'phone'. Headers found: " . implode(', ', $cleanedHeaders);
            Log::error($msg);
            throw new \Exception($msg);
        }

        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            $phone = trim($row[$phoneIndex] ?? '');
            if (!$phone) {
                Log::debug("Import skip: Empty phone at row " . ($count + 2));
                continue;
            }

            $tags = $tagsIndex !== false ? array_map('trim', explode(',', $row[$tagsIndex] ?? '')) : [];
            $tags = array_filter($tags); // Remove empty strings

            try {
                Contact::updateOrCreate(
                    [
                        'workspace_id' => $this->workspaceId,
                        'phone'        => $phone,
                    ],
                    [
                        'name'              => trim($row[$nameIndex] ?? 'Unknown'),
                        'telegram_username' => $telegramIndex !== false ? (trim($row[$telegramIndex] ?? '') ?: null) : null,
                        'tags'              => count($tags) > 0 ? $tags : null,
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                Log::error("Import error at row " . ($count + 2) . ": " . $e->getMessage());
            }
        }

        Log::info("Import completed: {$count} contacts processed for workspace {$this->workspaceId}");

        Storage::delete($this->path);

        // Clear last error on success
        \App\Models\Workspace::find($this->workspaceId)?->update([
            'last_import_error' => null
        ]);
    }

    private function findIndex(array $headers, array $needles): int|bool
    {
        foreach ($needles as $needle) {
            $index = array_search($needle, $headers);
            if ($index !== false) return $index;
        }
        return false;
    }
}
