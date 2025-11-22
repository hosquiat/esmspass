<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--force : Force backup even if one was recently created}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup and optionally upload to Google Drive';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Starting backup process...');

        try {
            $results = $backupService->runBackup();

            if ($results['success']) {
                $this->info('✓ Database backup created successfully');
                $this->line('  File: ' . basename($results['backup_file']));

                if ($results['google_drive']) {
                    $this->info('✓ Backup uploaded to Google Drive');
                }

                if (!empty($results['errors'])) {
                    $this->warn('Warnings:');
                    foreach ($results['errors'] as $error) {
                        $this->line('  - ' . $error);
                    }
                }

                return Command::SUCCESS;
            } else {
                $this->error('✗ Backup failed');
                foreach ($results['errors'] as $error) {
                    $this->line('  - ' . $error);
                }

                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('✗ Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
