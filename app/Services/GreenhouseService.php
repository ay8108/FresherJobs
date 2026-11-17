<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\JobPost;
use App\Models\JobSource;

class GreenhouseService
{
    public function fetchJobs(string $boardToken): array
    {
        $url = "https://boards-api.greenhouse.io/v1/boards/{$boardToken}/jobs";

        $response = Http::timeout(30)
            ->get($url);

        $response->throw();

        return $response->json('jobs', []);
    }


  
    public function formatJob(array $job): array
    {
        $formatted = [
            'title' => $job['title'] ?? 'Unknown',
            'company_name' => $job['company_name'] ?? null,
            'location' => $job['location']['name'] ?? null,
            'source_name' => 'greenhouse',
            'source_url' => $job['absolute_url'],
            'source_job_id' => (string) $job['id'],
            'posted_at' => $job['first_published'] ?? null,
            'last_seen_at' => now(),
        ];

        $formatted['dedup_hash'] = hash(
            'sha256',
            strtolower(
                trim($formatted['company_name'] ?? '') . '|' .
                trim($formatted['title']) . '|' .
                trim($formatted['location'] ?? '')
            )
        );

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
        $sources = JobSource::where('source_type', 'greenhouse')
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
                $formattedJob = $this->formatJob($job);

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