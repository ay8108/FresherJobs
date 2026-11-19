<?php

namespace App\Console\Commands;

use App\Models\JobPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportJobsData extends Command
{
    protected $signature = 'jobs:export-data
                            {--output= : Custom destination path for the exported JSON file}';

    protected $description = 'Safely export all local job_posts records to a JSON file for production sync';

    public function handle(): int
    {
        $this->info('Starting local job data export...');

        $destinationPath = $this->option('output')
            ?: base_path('database/data/local_jobs_export.json');

        $dir = dirname($destinationPath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $totalJobs = JobPost::count();
        if ($totalJobs === 0) {
            $this->warn('No job posts found in the local database.');
            return self::FAILURE;
        }

        $this->info("Fetching {$totalJobs} job records from database...");

        // Allowed columns in job_posts (sanitized whitelist - no credentials or API keys)
        $columns = [
            'id',
            'title',
            'company_name',
            'description',
            'location',
            'employment_type',
            'experience_level',
            'salary',
            'source_name',
            'source_url',
            'source_job_id',
            'ai_role',
            'ai_city',
            'ai_confidence',
            'ai_raw_response',
            'dedup_hash',
            'posted_at',
            'last_seen_at',
            'created_at',
            'updated_at',
        ];

        $jobs = JobPost::orderBy('id')->get($columns)->toArray();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'app_version' => '1.0.0',
            'total_records' => count($jobs),
            'columns' => $columns,
            'records' => $jobs,
        ];

        $jsonContent = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($jsonContent === false) {
            $this->error('Failed to encode job records to JSON: ' . json_last_error_msg());
            return self::FAILURE;
        }

        File::put($destinationPath, $jsonContent);

        $fileSizeKb = round(filesize($destinationPath) / 1024, 2);
        $sha256 = hash_file('sha256', $destinationPath);

        $this->newLine();
        $this->info('✓ Export completed successfully!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Records Exported', count($jobs)],
                ['Destination File', $destinationPath],
                ['File Size', "{$fileSizeKb} KB"],
                ['SHA256 Checksum', $sha256],
            ]
        );

        return self::SUCCESS;
    }
}
