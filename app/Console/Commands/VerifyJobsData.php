<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class VerifyJobsData extends Command
{
    protected $signature = 'jobs:verify-data
                            {--file= : Custom path to exported JSON file}
                            {--scope=all : Verification scope: all, file, or database}';

    protected $description = 'Verify export dataset integrity and/or current database job counts';

    public function handle(): int
    {
        $scope = $this->option('scope') ?: 'all';
        $filePath = $this->option('file') ?: base_path('database/data/local_jobs_export.json');

        // File verification
        if ($scope === 'all' || $scope === 'file') {
            $this->info('--- EXPORT DATASET VERIFICATION ---');

            if (!File::exists($filePath)) {
                $this->error("Dataset file not found at: {$filePath}");
                if ($scope === 'file') {
                    return self::FAILURE;
                }
            } else {
                $fileSizeKb = round(filesize($filePath) / 1024, 2);
                $sha256 = hash_file('sha256', $filePath);
                $content = File::get($filePath);
                $json = json_decode($content, true);

                if (!is_array($json) || !isset($json['records'])) {
                    $this->error('Invalid JSON structure: missing "records" array.');
                    if ($scope === 'file') {
                        return self::FAILURE;
                    }
                } else {
                    $fileRecordCount = count($json['records']);
                    $this->table(
                        ['File Metric', 'Value'],
                        [
                            ['File Path', $filePath],
                            ['File Status', 'EXISTS & VALID JSON'],
                            ['File Size', "{$fileSizeKb} KB"],
                            ['Records in File', $fileRecordCount],
                            ['Exported At', $json['exported_at'] ?? 'N/A'],
                            ['SHA256 Checksum', $sha256],
                        ]
                    );
                }
            }
            $this->newLine();
        }

        // Database verification
        if ($scope === 'all' || $scope === 'database') {
            $this->info('--- DATABASE JOB POSTS VERIFICATION ---');

            try {
                $dbTotal = DB::table('job_posts')->count();
                $withRole = DB::table('job_posts')->whereNotNull('ai_role')->where('ai_role', '!=', '')->count();
                $withCity = DB::table('job_posts')->whereNotNull('ai_city')->where('ai_city', '!=', '')->count();
                $minId = DB::table('job_posts')->min('id') ?? 'N/A';
                $maxId = DB::table('job_posts')->max('id') ?? 'N/A';

                // Source distribution
                $sources = DB::table('job_posts')
                    ->select('source_name', DB::raw('count(*) as count'))
                    ->groupBy('source_name')
                    ->orderByDesc('count')
                    ->get();

                $sourceBreakdown = $sources->map(fn($s) => "{$s->source_name}: {$s->count}")->implode(', ');

                $this->table(
                    ['Database Metric', 'Value'],
                    [
                        ['Total job_posts in DB', $dbTotal],
                        ['Jobs with ai_role', "{$withRole} / {$dbTotal}"],
                        ['Jobs with ai_city', "{$withCity} / {$dbTotal}"],
                        ['ID Range', "Min: {$minId}, Max: {$maxId}"],
                        ['Source Breakdown', $sourceBreakdown ?: 'None'],
                    ]
                );
            } catch (\Throwable $e) {
                $this->error('Failed to query database: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
