<?php

namespace App\Console\Commands;

use App\Models\JobPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ResetProductionJobs extends Command
{
    protected $signature = 'jobs:reset-production-data
                            {--confirm= : Required explicit confirmation string (must match CONFIRM_RESET_JOBS)}
                            {--no-backup : Skip the automated pre-reset backup}';

    protected $description = 'Safely back up and clear existing job_posts records before dataset synchronization';

    public function handle(): int
    {
        $this->warn('=====================================================');
        $this->warn('   PRODUCTION JOB DATA RESET OPERATION');
        $this->warn('=====================================================');

        // Safety Requirement: Explicit confirmation token required
        $confirmation = $this->option('confirm');
        if ($confirmation !== 'CONFIRM_RESET_JOBS') {
            $this->error('OPERATION ABORTED: Missing or invalid confirmation token.');
            $this->line('To execute this destructive operation, you must provide:');
            $this->line('  --confirm=CONFIRM_RESET_JOBS');
            return self::FAILURE;
        }

        $existingCount = DB::table('job_posts')->count();
        $this->info("Current job_posts table count: {$existingCount} records.");

        // Step 1: Pre-reset backup procedure
        if (!$this->option('no-backup') && $existingCount > 0) {
            $this->info('Creating automatic pre-reset backup of existing production records...');

            $backupDir = storage_path('app/backups');
            if (!File::isDirectory($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_His');
            $backupFilename = "job_posts_pre_reset_backup_{$timestamp}_{$existingCount}jobs.json";
            $backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupFilename;

            $existingRecords = DB::table('job_posts')->orderBy('id')->get()->toArray();

            $backupPayload = [
                'backup_type' => 'pre_reset_production_backup',
                'created_at' => now()->toIso8601String(),
                'record_count' => count($existingRecords),
                'records' => $existingRecords,
            ];

            $encoded = json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                $this->error('CRITICAL: Failed to serialize existing job records for backup: ' . json_last_error_msg());
                $this->error('Aborting reset to prevent unbacked data loss.');
                return self::FAILURE;
            }

            File::put($backupPath, $encoded);

            if (!File::exists($backupPath) || filesize($backupPath) === 0) {
                $this->error('CRITICAL: Backup file was not written properly. Aborting reset.');
                return self::FAILURE;
            }

            $this->info("✓ Pre-reset backup saved to: {$backupPath} (" . round(filesize($backupPath) / 1024, 2) . " KB)");
        } else {
            $this->line('No existing records to back up or backup skipped via flag.');
        }

        // Step 2: Ensure only intended job-related table is affected
        // Do NOT drop tables or touch users, roles, sessions, cache
        $this->info('Clearing existing job_posts table records...');

        try {
            // Disable foreign key checks momentarily if required by driver, then truncate
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('job_posts')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $remainingCount = DB::table('job_posts')->count();

            if ($remainingCount === 0) {
                $this->info('✓ job_posts table successfully cleared (0 remaining records).');
                $this->info('Database is now clean and ready for synchronized data import.');
                return self::SUCCESS;
            } else {
                $this->error("Warning: {$remainingCount} records still remain in job_posts.");
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error('Failed to truncate job_posts: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
