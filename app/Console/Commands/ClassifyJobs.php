<?php

namespace App\Console\Commands;

use App\Models\JobPost;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassifyJobs extends Command
{
    protected $signature = 'app:classify-jobs
                            {--limit= : Maximum number of jobs to classify}
                            {--batch=10 : Number of jobs per Gemini request batch}
                            {--source= : Only classify jobs from this source}
                            {--scrapers-only : Only classify jobs from active HTML scrapers}';

    protected $description = 'Classify unclassified jobs using Gemini AI';

    public function handle(GeminiService $gemini): int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $batchSize = max(1, min(20, (int) $this->option('batch') ?: 10));
        $source = $this->option('source');

        $query = JobPost::whereNull('ai_role');

        if ($this->option('scrapers-only')) {
            $activeSources = ['Internshala', 'Freshersworld', 'Hasjob', 'Offcampusjobs4u', 'Fresherscamp', 'PlacementIndia', 'Freshersvoice', 'Enggwave', 'WeWorkRemotely', 'PythonJobs'];
            $query->whereIn('source_name', $activeSources);
        } elseif (!empty($source)) {
            $query->where('source_name', $source);
        }

        $totalAvailable = (clone $query)->count();

        if ($totalAvailable === 0) {
            $this->info('No unclassified jobs found.');
            return self::SUCCESS;
        }

        $totalToProcess = $limit ? min($limit, $totalAvailable) : $totalAvailable;
        $this->info("Found {$totalAvailable} unclassified jobs. Processing up to {$totalToProcess} in batches of {$batchSize}...");

        $stats = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
        ];

        $page = 0;

        while ($stats['processed'] < $totalToProcess) {
            $currentLimit = min($batchSize, $totalToProcess - $stats['processed']);

            // Fetch next batch of unclassified jobs
            $batch = (clone $query)->take($currentLimit)->get();

            if ($batch->isEmpty()) {
                break;
            }

            if ($stats['processed'] > 0) {
                // Short 1.5s delay between batches to respect rate limits
                usleep(1500000);
            }

            try {
                $saved = $gemini->classifyAndSaveBatch($batch);
                $stats['processed'] += $batch->count();
                $stats['successful'] += $saved;
                $this->info("Classified batch of {$saved}/{$batch->count()} jobs (Total: {$stats['successful']}/{$totalToProcess})");
            } catch (Throwable $e) {
                $stats['processed'] += $batch->count();
                $stats['failed'] += $batch->count();

                Log::error('Batch classification failed', [
                    'count' => $batch->count(),
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed batch of {$batch->count()} jobs: " . substr($e->getMessage(), 0, 100));

                if (str_contains($e->getMessage(), '429')) {
                    $this->warn('Encountered 429 quota/rate limit. Pausing for 5 seconds...');
                    sleep(5);
                }
            }
        }

        $this->newLine();
        $this->info('Job classification completed.');
        $this->info("Processed: {$stats['processed']}");
        $this->info("Successful: {$stats['successful']}");
        $this->info("Failed: {$stats['failed']}");

        return self::SUCCESS;
    }
}
