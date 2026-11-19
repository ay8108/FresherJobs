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

class EnggwaveSpider extends BasicSpider
{
    public array $startUrls = [
        'https://www.enggwave.com/',
        'https://www.enggwave.com/category/off-campus-drive/',
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
        $links = $crawler->filter('a[href]');

        $seenIds = [];

        foreach ($links as $linkNode) {
            $url = $linkNode->getAttribute('href');
            $rawText = trim(preg_replace('/\s+/', ' ', $linkNode->textContent));

            if (empty($url) || empty($rawText)) {
                continue;
            }

            // Enggwave post URLs have pattern: enggwave.com/{slug}/{id}
            if (!preg_match('#enggwave\.com/([^/]+)/(\d+)#i', $url, $matches)) {
                continue;
            }

            $jobId = $matches[2];
            if (isset($seenIds[$jobId])) {
                continue;
            }

            if (strlen($rawText) < 12 || str_contains(strtolower($rawText), 'read more')) {
                continue;
            }

            // Filter out non-job blog articles / comparisons
            if (!preg_match('/\b(Recruitment|Off Campus|Drive|Hiring|Internship|Trainee|Engineer|Vacancies|NQT|Associate|Developer)\b/i', $rawText)) {
                continue;
            }

            $seenIds[$jobId] = true;

            // Pattern: "Company Off Campus Drive 2026 | Role | Location | Apply Online"
            $parts = explode('|', $rawText);
            $company = 'Company';
            $title = $rawText;
            $location = 'India';

            $firstPart = trim($parts[0] ?? '');
            if (preg_match('/^TCS\s+NQT/i', $firstPart)) {
                $company = 'TCS';
            } elseif (preg_match('/^(.*?)\s+(Off Campus|Recruitment|Drive|Hiring|Internship|NQT)\b/i', $firstPart, $m)) {
                $company = trim($m[1]);
            }

            if (count($parts) >= 2) {
                $secondPart = trim($parts[1]);
                if (strtolower($secondPart) === 'freshers' && isset($parts[2])) {
                    $title = trim($parts[2]);
                    if (isset($parts[3]) && !str_contains(strtolower($parts[3]), 'apply')) {
                        $location = trim($parts[3]);
                    }
                } else {
                    $title = $secondPart;
                    if (isset($parts[2]) && !str_contains(strtolower($parts[2]), 'apply') && !str_contains(strtolower($parts[2]), 'lpa')) {
                        $location = trim($parts[2]);
                    }
                }
            } else {
                $title = $firstPart;
            }

            if (empty($title)) {
                $title = $rawText;
            }

            if ($company === 'Company' || str_contains(strtolower($title), 'registration open')) {
                continue;
            }

            $isIntern = (bool) preg_match('/\b(intern|internship)\b/i', $rawText);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $title)
                || preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $rawText)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $title)
                || (preg_match('/\blead\b/i', $title) && !preg_match('/\blead\s*gen/i', $title)));

            // Exclude senior, staff, principal, head, director and manager roles from fresher feed
            if ($isSenior && !$isIntern) {
                continue;
            }

            $isFresher = (bool) preg_match('/\b(fresher|entry[- ]level|trainee|graduate|junior|engineer trainee|off[- ]campus)\b/i', $rawText);
            $employmentType = $isIntern ? 'Internship' : 'Full Time';
            $experienceLevel = $isIntern ? 'Internship' : ($isFresher ? 'Fresher' : null);

            $dedupHash = hash(
                'sha256',
                strtolower(trim($title . '|' . $company . '|' . $location))
            );

            yield $this->item([
                'title' => $title,
                'company_name' => $company,
                'description' => $rawText,
                'location' => $location,
                'employment_type' => $employmentType,
                'experience_level' => $experienceLevel,
                'salary' => null,
                'source_name' => 'Enggwave',
                'source_url' => $url,
                'source_job_id' => (string) $jobId,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
