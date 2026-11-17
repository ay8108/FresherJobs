<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\JobPost;
use App\Models\JobSource;

class AshbyService
{
    public function fetchJobs(string $boardName): array
    {
        $url = "https://api.ashbyhq.com/posting-api/job-board/{$boardName}";

        $response = Http::timeout(30)->get($url);

        $response->throw();

        return $response->json('jobs', []);
    }



    public function formatJob(array $job, string $companyName): array
    {
        $formatted = [
            'title' => $job['title'] ?? null,
            'company_name' => $companyName,
            'description' => strip_tags($job['descriptionPlain'] ?? $job['descriptionHtml'] ?? ''),
            'location' => $job['location'] ?? null,
            'source_name' => 'ashby',
            'source_url' => $job['jobUrl'] ?? null,
            'source_job_id' => $job['id'] ?? null,
            'posted_at' => $job['publishedAt'] ?? null,
            'last_seen_at' => now(),
        ];

        $formatted['dedup_hash'] = hash('sha256', strtolower(
            trim($formatted['company_name']) . '|' .
            trim($formatted['title']) . '|' .
            trim($formatted['location'] ?? '')
        ));

        return $formatted;
    }


    public function saveJob(array $job): array
    {
        $jobPost = JobPost::firstOrNew([
            'dedup_hash' => $job['dedup_hash'],
        ]);

        $isNew = ! $jobPost->exists;

        $jobPost->fill($job);
        $jobPost->save();

        return [
            'job' => $jobPost,
            'is_new' => $isNew,
        ];
    }


    public function syncJobs(): array
    {
        $sources = JobSource::where('source_type', 'ashby')
            ->where('is_active', true)
            ->get();

        $stats = [
            'processed' => 0,
            'new' => 0,
            'updated' => 0,
        ];

        foreach ($sources as $source) {
            $jobs = $this->fetchJobs($source->board_token);

            foreach ($jobs as $job) {
                $formattedJob = $this->formatJob(
                    $job,
                    $source->company_name
                );

                $result = $this->saveJob($formattedJob);

                $stats['processed']++;

                if ($result['is_new']) {
                    $stats['new']++;
                } else {
                    $stats['updated']++;
                }
            }

            $source->update([
                'last_fetched_at' => now(),
            ]);
        }

        return $stats;
    }

}
