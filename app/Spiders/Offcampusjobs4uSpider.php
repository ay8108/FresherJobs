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

class Offcampusjobs4uSpider extends BasicSpider
{
    public array $startUrls = [
        'https://offcampusjobs4u.com/',
        'https://offcampusjobs4u.com/category/freshers-jobs/',
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
        $links = $crawler->filter('h2.entry-title a, h3.entry-title a, h2 a, h3 a');

        $seenUrls = [];

        foreach ($links as $linkNode) {
            $url = $linkNode->getAttribute('href');
            $rawTitle = trim($linkNode->textContent);

            if (empty($url) || empty($rawTitle) || isset($seenUrls[$url])) {
                continue;
            }

            // Must be an individual post URL
            if (!str_contains($url, 'offcampusjobs4u.com/') || str_contains($url, '/category/') || str_contains($url, '/tag/')) {
                continue;
            }

            $slug = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            if (empty($slug)) {
                continue;
            }

            $seenUrls[$url] = true;

            // Clean headline format: "Company Recruitment/Drive 2026 – Job Role, Location"
            $company = 'Company';
            $title = $rawTitle;
            $location = 'India';

            if (preg_match('/^(.*?)\s+(Recruitment|Off Campus Drive|Off Campus|Work From Home Jobs|Work From Home|Hiring|Internship|Drive)\b(.*)$/iu', $rawTitle, $m)) {
                $company = trim($m[1]);
                $remainder = preg_replace('/^[\s\-\–\:\|\x{2013}\x{2014}]+/u', '', trim($m[3]));
                $remainder = preg_replace('/^20\d\d\s*/u', '', $remainder);
                $remainder = preg_replace('/^[\s\-\–\:\|\x{2013}\x{2014}]+/u', '', $remainder);

                // Check for location at the end (e.g. ", Pune" or ", Bangalore")
                if (preg_match('/^(.*?),\s*([A-Za-z\s]+)$/u', $remainder, $locMatch)) {
                    $title = trim($locMatch[1]);
                    $location = trim($locMatch[2]);
                } else {
                    $title = $remainder ?: $rawTitle;
                }
            }

            if (empty($title)) {
                $title = $rawTitle;
            }

            if ($company === 'Company') {
                continue;
            }

            $isIntern = (bool) preg_match('/\b(intern|internship)\b/i', $rawTitle);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $rawTitle)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $rawTitle)
                || (preg_match('/\blead\b/i', $rawTitle) && !preg_match('/\blead\s*gen/i', $rawTitle)));

            // Exclude senior, staff, principal, head, director and manager roles
            if ($isSenior && !$isIntern) {
                continue;
            }

            $isFresher = (bool) preg_match('/\b(fresher|trainee|graduate|associate|junior|entry[- ]level|off[- ]campus|drive|entry program)\b/i', $rawTitle);
            $employmentType = $isIntern ? 'Internship' : 'Full Time';
            $experienceLevel = $isIntern ? 'Internship' : ($isFresher ? 'Fresher' : null);

            $dedupHash = hash(
                'sha256',
                strtolower(trim($title . '|' . $company . '|' . $location))
            );

            yield $this->item([
                'title' => $title,
                'company_name' => $company,
                'description' => $rawTitle,
                'location' => $location,
                'employment_type' => $employmentType,
                'experience_level' => $experienceLevel,
                'salary' => null,
                'source_name' => 'Offcampusjobs4u',
                'source_url' => $url,
                'source_job_id' => $slug,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
