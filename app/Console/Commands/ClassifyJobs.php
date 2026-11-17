<?php

namespace App\Console\Commands;

use App\Models\JobPost;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassifyJobs extends Command
{
    protected $signature = 'app:classify-jobs';

    protected $description = 'Classify unclassified jobs using Gemini AI';

    public function handle(GeminiService $gemini): int
    {
        $stats = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
        ];

        JobPost::whereNull('ai_role')
            ->chunkById(10, function ($jobs) use ($gemini, &$stats) {

                foreach ($jobs as $job) {

                    // Maintain 6-second gap between API requests
                    if ($stats['processed'] > 0) {
                        sleep(6);
                    }

                    $stats['processed']++;

                    try {
                        $gemini->classifyAndSave($job);

                        $stats['successful']++;

                        $this->info("Classified: Job ID {$job->id}");

                    } catch (Throwable $e) {
                        $stats['failed']++;

                        Log::error('Job classification failed', [
                            'job_id' => $job->id,
                            'error' => $e->getMessage(),
                        ]);

                        $this->error("Failed: Job ID {$job->id}");
                    }
                }
            });

        $this->newLine();

        $this->info('Job classification completed.');
        $this->info("Processed: {$stats['processed']}");
        $this->info("Successful: {$stats['successful']}");
        $this->info("Failed: {$stats['failed']}");

        return self::SUCCESS;
    }
}
