<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class ImportJobsData extends Command
{
    protected $signature = 'jobs:import-data
                            {--file= : Path to the exported JSON dataset}
                            {--preserve-ids : Keep original IDs from the export dataset}
                            {--batch=100 : Number of records to insert per batch}';

    protected $description = 'Import exported job_posts dataset into the database with verification';

    public function handle(): int
    {
        $filePath = $this->option('file')
            ?: base_path('database/data/local_jobs_export.json');

        $this->info("Import source file: {$filePath}");

        if (!File::exists($filePath)) {
            $this->error("CRITICAL: Import file not found at: {$filePath}");
            return self::FAILURE;
        }

        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (!is_array($data) || !isset($data['records']) || !is_array($data['records'])) {
            $this->error('CRITICAL: Invalid JSON format or missing "records" array.');
            return self::FAILURE;
        }

        $records = $data['records'];
        $totalRecords = count($records);

        if ($totalRecords === 0) {
            $this->warn('Dataset contains 0 records. Nothing to import.');
            return self::SUCCESS;
        }

        $this->info("Found {$totalRecords} records in dataset. Preparing import...");

        $batchSize = max(1, min(500, (int) $this->option('batch') ?: 100));
        $chunks = array_chunk($records, $batchSize);
        $insertedCount = 0;
        $preserveIds = $this->hasOption('preserve-ids');

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($chunks as $chunk) {
                $batchData = [];

                foreach ($chunk as $job) {
                    $record = [
                        'title'            => $job['title'] ?? '',
                        'company_name'     => $job['company_name'] ?? null,
                        'description'      => $job['description'] ?? null,
                        'location'         => $job['location'] ?? null,
                        'employment_type'  => $job['employment_type'] ?? null,
                        'experience_level' => $job['experience_level'] ?? null,
                        'salary'           => $job['salary'] ?? null,
                        'source_name'      => $job['source_name'] ?? 'Unknown',
                        'source_url'       => $job['source_url'] ?? '',
                        'source_job_id'    => $job['source_job_id'] ?? null,
                        'ai_role'          => $job['ai_role'] ?? null,
                        'ai_city'          => $job['ai_city'] ?? null,
                        'ai_confidence'    => isset($job['ai_confidence']) && is_numeric($job['ai_confidence']) ? (float) $job['ai_confidence'] : null,
                        'ai_raw_response'  => $job['ai_raw_response'] ?? null,
                        'dedup_hash'       => $job['dedup_hash'] ?? md5(($job['title'] ?? '') . ($job['company_name'] ?? '')),
                        'posted_at'        => $this->formatDateTime($job['posted_at'] ?? null),
                        'last_seen_at'     => $this->formatDateTime($job['last_seen_at'] ?? null),
                        'created_at'       => $this->formatDateTime($job['created_at'] ?? null) ?: now()->format('Y-m-d H:i:s'),
                        'updated_at'       => $this->formatDateTime($job['updated_at'] ?? null) ?: now()->format('Y-m-d H:i:s'),
                    ];

                    if ($preserveIds && isset($job['id'])) {
                        $record['id'] = $job['id'];
                    }

                    $batchData[] = $record;
                }

                DB::table('job_posts')->insert($batchData);
                $insertedCount += count($batchData);
                $bar->advance(count($batchData));
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);

        } catch (Throwable $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Import failed during transaction rollback: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Post-import verification
        $actualDbCount = DB::table('job_posts')->count();
        $minId = DB::table('job_posts')->min('id');
        $maxId = DB::table('job_posts')->max('id');
        $withAiRole = DB::table('job_posts')->whereNotNull('ai_role')->count();

        $this->info('✓ Import finished and verified successfully!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Export Dataset Records', $totalRecords],
                ['Records Inserted', $insertedCount],
                ['Total DB Count After Import', $actualDbCount],
                ['Min Job ID', $minId],
                ['Max Job ID', $maxId],
                ['Jobs with AI Classification', "{$withAiRole} / {$actualDbCount}"],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Parse date value to standard MySQL DATETIME string format (Y-m-d H:i:s).
     */
    private function formatDateTime(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }
}
