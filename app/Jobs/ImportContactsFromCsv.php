<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportContactsFromCsv implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $filePath,
        public int $workspaceId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $path = \Illuminate\Support\Facades\Storage::path($this->filePath);
        
        if (!file_exists($path)) {
            return;
        }

        $file = fopen($path, 'r');
        $header = fgetcsv($file);
        
        if (!$header) {
            fclose($file);
            return;
        }

        // Map common headers (case-insensitive)
        $normalizedHeader = array_map('strtolower', array_map('trim', $header));
        $nameIndex     = array_search('name', $normalizedHeader);
        $phoneIndex    = array_search('phone', $normalizedHeader);
        $telegramIndex = array_search('telegram_username', $normalizedHeader);

        // We require at least phone and name
        if ($nameIndex === false || $phoneIndex === false) {
            fclose($file);
            return; 
        }

        while (($row = fgetcsv($file)) !== false) {
            $phone = trim($row[$phoneIndex] ?? '');
            if (!$phone) continue;

            \App\Models\Contact::updateOrCreate(
                [
                    'workspace_id' => $this->workspaceId,
                    'phone'        => $phone,
                ],
                [
                    'name'              => trim($row[$nameIndex] ?? 'Unknown'),
                    'telegram_username' => $telegramIndex !== false ? (trim($row[$telegramIndex] ?? '') ?: null) : null,
                ]
            );
        }

        fclose($file);
        \Illuminate\Support\Facades\Storage::delete($this->filePath);
    }
}
