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

class FreshersvoiceSpider extends BasicSpider
{
    public array $startUrls = [
        'https://www.freshersvoice.com/',
        'https://www.freshersvoice.com/category/off-campus/',
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
        $links = $crawler->filter('h2 a, h3 a, .entry-title a, article a');

        $seenUrls = [];

        foreach ($links as $linkNode) {
            $url = $linkNode->getAttribute('href');
            $rawTitle = trim(preg_replace('/\s+/', ' ', $linkNode->textContent));

            if (empty($url) || empty($rawTitle) || isset($seenUrls[$url])) {
                continue;
            }

            // Must be an individual article URL
            if (!str_contains($url, 'freshersvoice.com/') || str_contains($url, '/category/') || str_contains($url, '/tag/') || str_contains($url, '/page/')) {
                continue;
            }

            $slug = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            $nonJobSlugs = [
                'about-us', 'contact-us', 'privacy-policy', 'disclaimer', 'alerts',
                'off-campus-drives', 'latest-walk-in-drives', 'it-software-jobs',
                'latest-freshers-internship', 'latest-government-jobs', 'latest-bank-jobs',
            ];

            if (empty($slug) || strlen($slug) < 3 || in_array($slug, $nonJobSlugs) || str_contains($slug, 'afcat') || str_contains($slug, 'tcs-nqt')) {
                continue;
            }

            // Only consider meaningful job titles
            if (strlen($rawTitle) < 12 || str_contains(strtolower($rawTitle), 'whatsapp') || str_contains(strtolower($rawTitle), 'browse all') || str_contains(strtolower($rawTitle), 'view all')) {
                continue;
            }

            // Must contain a job indicator keyword
            if (!preg_match('/\b(Recruitment|Walk-in|Drive|Off Campus|Hiring|Internship|Vacancies|Apprentice)\b/i', $rawTitle)) {
                continue;
            }

            $seenUrls[$url] = true;

            $company = 'Company';
            $title = $rawTitle;
            $location = 'India';

            // Examples:
            // "HPE Recruitment 2026 – Technical Support Engineer Associate"
            // "IndusInd Bank Walk-in Drive 2026: Business Executive | 23 November 2026"
            // "Genpact Walk-in Drive 2026 for Customer Service – Voice Role"
            $cleanTitle = preg_replace('/\|\s*\d+.*$/', '', $rawTitle);

            if (preg_match('/^(.*?)\s+(Recruitment|Walk-in|Drive|Off Campus|Hiring|Internship)\b(.*)$/i', $cleanTitle, $m)) {
                $company = trim($m[1]);
                $after = trim($m[3], " –-:|\t\n\r\0\x0B");
                $after = preg_replace('/^20\d\d\s*[-–:]*\s*/u', '', (string) $after);
                $after = preg_replace('/^for\s+/i', '', (string) $after);

                if (!empty($after)) {
                    $title = $after;
                }
            }

            if ($company === 'Company') {
                continue;
            }

            $isIntern = (bool) preg_match('/\b(intern|internship)\b/i', $rawTitle);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $title)
                || preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $rawTitle)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $title)
                || (preg_match('/\blead\b/i', $title) && !preg_match('/\blead\s*gen/i', $title)));

            // Exclude senior, staff, principal, head, director and manager roles
            if ($isSenior && !$isIntern) {
                continue;
            }

            $isFresher = (bool) preg_match('/\b(fresher|trainee|graduate|associate|junior|entry[- ]level|drive|off[- ]campus|entry program)\b/i', $rawTitle);
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
                'source_name' => 'Freshersvoice',
                'source_url' => $url,
                'source_job_id' => $slug,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
