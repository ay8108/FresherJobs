<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;
use Symfony\Component\DomCrawler\Crawler;
use App\ItemProcessors\SaveJobProcessor;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Downloader\Middleware\HttpErrorMiddleware;
use RoachPHP\Downloader\Middleware\UserAgentMiddleware;

class PlacementIndiaSpider extends BasicSpider
{
    public array $startUrls = [
        'https://www.placementindia.com/job-search/software-developer-jobs.htm',
        'https://www.placementindia.com/job-search/freshers-jobs.htm',
    ];

    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
        [
            UserAgentMiddleware::class,
            ['userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'],
        ],
        HttpErrorMiddleware::class,
    ];

    public array $itemProcessors = [
        SaveJobProcessor::class,
    ];

    public int $concurrency = 1;

    public int $requestDelay = 2;

    /**
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        $crawler = new Crawler((string) $response->getBody());
        $links = $crawler->filter('a[href*="/job-detail/"]');

        $seenIds = [];

        foreach ($links as $linkNode) {
            $url = $linkNode->getAttribute('href');
            $rawText = trim(preg_replace('/\s+/', ' ', $linkNode->textContent));

            if (empty($url) || empty($rawText)) {
                continue;
            }

            // Pattern: /job-detail/{role}-in-{company}-at-{location}-{id}.htm
            if (preg_match('#/job-detail/(.*?)-in-(.*?)-at-(.*?)-(\d+)\.htm#i', $url, $matches)) {
                $roleSlug = str_replace('-', ' ', $matches[1]);
                $compSlug = ucwords(str_replace('-', ' ', $matches[2]));
                $locSlug = ucwords(str_replace('-', ' ', $matches[3]));
                $jobId = $matches[4];

                $title = !empty($rawText) && strlen($rawText) > 5 ? $rawText : ucwords($roleSlug);
                $company = $compSlug ?: 'Employer';
                $location = $locSlug ?: 'India';
            } elseif (preg_match('/-(\d+)\.htm$/', $url, $m)) {
                $jobId = $m[1];
                $title = $rawText;
                $company = 'PlacementIndia Employer';
                $location = 'India';
            } else {
                continue;
            }

            if (isset($seenIds[$jobId])) {
                continue;
            }
            $seenIds[$jobId] = true;

            if (!str_starts_with($url, 'http')) {
                $url = 'https://www.placementindia.com/' . ltrim($url, '/');
            }

            $isIntern = (bool) preg_match('/\b(intern|internship)\b/i', $title);
            $isFresher = (bool) preg_match('/\b(fresher|junior|trainee|graduate|entry[- ]level)\b/i', $title);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $title)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $title)
                || (preg_match('/\blead\b/i', $title) && !preg_match('/\blead\s*gen/i', $title)));

            // Exclude senior, staff, principal, head, director and manager roles
            if ($isSenior && !$isIntern) {
                continue;
            }

            // Keep India-based and India-eligible roles (exclude foreign overseas recruitment)
            if (preg_match('/\b(australia|melbourne|sydney|dubai|uae|kuwait|singapore|qatar|saudi|bahrain|canada)\b/i', $location) && !preg_match('/\b(india|remote)\b/i', $location)) {
                continue;
            }

            $isFromFresherSearch = str_contains($response->getRequest()->getUri(), 'freshers-jobs');
            $experienceLevel = $isIntern ? 'Internship' : ($isFresher ? 'Fresher' : ($isFromFresherSearch ? 'Fresher' : null));
            $employmentType = $isIntern ? 'Internship' : 'Full Time';

            $dedupHash = hash(
                'sha256',
                strtolower(trim($title . '|' . $company . '|' . $location))
            );

            yield $this->item([
                'title' => $title,
                'company_name' => $company,
                'description' => "Job listing for {$title} at {$company} in {$location}. Apply on PlacementIndia.",
                'location' => $location,
                'employment_type' => $employmentType,
                'experience_level' => $experienceLevel,
                'salary' => null,
                'source_name' => 'PlacementIndia',
                'source_url' => $url,
                'source_job_id' => (string) $jobId,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
