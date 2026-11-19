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

class HasjobSpider extends BasicSpider
{
    public array $startUrls = [
        'https://hasjob.co/',
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

        // Find all job links on the HTML board
        $links = $crawler->filter('a[href]');

        foreach ($links as $linkNode) {
            $href = $linkNode->getAttribute('href');

            // Hasjob job URLs have pattern: /domain/jobid (e.g. /indiatourpackage.co.uk/ekhec)
            if (!preg_match('#^/([a-zA-Z0-9._-]+)/([a-zA-Z0-9]+)$#', $href, $matches)) {
                continue;
            }

            if (str_contains($href, 'login') || str_contains($href, 'static')) {
                continue;
            }

            $jobId = $matches[2];
            $aCrawler = new Crawler($linkNode);

            // Extract title
            $titleNode = $aCrawler->filter('.headline');
            $title = $titleNode->count() > 0 ? trim($titleNode->text('')) : '';

            if (empty($title)) {
                continue;
            }

            // Extract company
            $compNode = $aCrawler->filter('.company-name');
            $company = $compNode->count() > 0 ? trim($compNode->text('')) : 'Hasjob Employer';

            // Extract location
            $locNode = $aCrawler->filter('.annotation.top-left');
            $location = $locNode->count() > 0 ? trim($locNode->text('')) : 'India';

            $fullUrl = 'https://hasjob.co' . $href;

            $isIntern = (bool) preg_match('/\b(intern|internship)\b/i', $title);
            $isFresher = (bool) preg_match('/\b(junior|entry[- ]level|fresher|graduate|trainee)\b/i', $title);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $title)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $title)
                || (preg_match('/\blead\b/i', $title) && !preg_match('/\blead\s*gen/i', $title)));

            // Exclude senior, staff, principal, head, director and manager roles from fresher feed
            if ($isSenior && !$isIntern) {
                continue;
            }

            $employmentType = $isIntern ? 'Internship' : 'Full Time';
            // Do not blindly classify jobs as Fresher or Entry-level when experience information is missing
            $experienceLevel = $isIntern ? 'Internship' : ($isFresher ? 'Fresher' : null);

            $dedupHash = hash(
                'sha256',
                strtolower(trim($title . '|' . $company . '|' . $location))
            );

            yield $this->item([
                'title' => $title,
                'company_name' => $company,
                'description' => "Job opening for {$title} at {$company} ({$location}) via Hasgeek Hasjob portal.",
                'location' => $location,
                'employment_type' => $employmentType,
                'experience_level' => $experienceLevel,
                'salary' => null,
                'source_name' => 'Hasjob',
                'source_url' => $fullUrl,
                'source_job_id' => (string) $jobId,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
