<?php

namespace App\ItemProcessors;

use App\Models\JobPost;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;

class SaveJobProcessor implements ItemProcessorInterface
{
    public function configure(array $options): void
    {
        //
    }

    public function processItem(ItemInterface $item): ItemInterface
    {
        $job = $item->all();

        if (empty($job['title']) || empty($job['source_job_id'])) {
            return $item;
        }

        $existing = JobPost::where('source_name', $job['source_name'])
            ->where('source_job_id', $job['source_job_id'])
            ->first();

        if ($existing) {
            $existing->update([
                'last_seen_at' => now(),
            ]);

            return $item;
        }

        $exists = JobPost::where('dedup_hash', $job['dedup_hash'])
            ->exists();

        if ($exists) {
            return $item;
        }

        // Automatic Experience Level & Freshness Detection
        $titleLower = strtolower($job['title'] ?? '');
        $urlLower = strtolower($job['source_url'] ?? '');

        if (
            (str_contains($urlLower, '-2025') || str_contains($urlLower, '-2024') || str_contains($titleLower, '2025') || str_contains($titleLower, '2024'))
            && !str_contains($titleLower, '2026') && !str_contains($titleLower, '2027')
        ) {
            $job['experience_level'] = 'Expired';
        } elseif (
            preg_match('/\b(senior|sr\.|sr\b|lead|principal|staff|director|head of|manager|vice president|vp\b|architect)\b/i', $job['title']) &&
            !str_contains($titleLower, 'trainee') &&
            !str_contains($titleLower, 'entry') &&
            !str_contains($titleLower, 'fresher') &&
            !str_contains($titleLower, 'intern')
        ) {
            $job['experience_level'] = 'Experienced';
        }

        JobPost::create($job);

        return $item;
    }
}