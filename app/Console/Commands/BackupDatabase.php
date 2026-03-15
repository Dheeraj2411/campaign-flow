<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-db';
    protected $description = 'Backup the PostgreSQL database and remove old backups';

    public function handle()
    {
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        $host = env('DB_HOST', '127.0.0.1');

        if (env('DB_CONNECTION') !== 'pgsql') {
            $this->warn("Skipping backup: Not using pgsql connection.");
            return;
        }

        $date = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$database}_{$date}.sql.gz";
        $directory = storage_path('app/backups');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory . '/' . $filename;
        
        $this->info("Starting backup for database: {$database}");
        
        // Use PGPASSWORD so we don't have to enter it manually
        $command = "PGPASSWORD=\"{$password}\" pg_dump -U {$username} -h {$host} {$database} | gzip > {$path}";
        
        $returnVar = null;
        $output = null;
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->info("Backup created successfully: {$filename}");
        } else {
            $this->error("Backup failed.");
        }

        // Clean up old backups (keep last 7 days)
        $files = glob($directory . '/*.sql.gz');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * 7) {
                    unlink($file);
                    $this->info("Deleted old backup: " . basename($file));
                }
            }
        }
    }
}
