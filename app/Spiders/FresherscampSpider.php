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

class FresherscampSpider extends BasicSpider
{
    public array $startUrls = [
        'https://fresherscamp.com/',
        'https://fresherscamp.com/category/it-jobs/',
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
        $articles = $crawler->filter('article');

        foreach ($articles as $artNode) {
            $art = new Crawler($artNode);

            $titleNode = $art->filter('.entry-title a, h2 a, h3 a');
            if ($titleNode->count() === 0) {
                continue;
            }

            $rawTitle = trim($titleNode->first()->text(''));
            $url = $titleNode->first()->attr('href');

            if (empty($rawTitle) || empty($url)) {
                continue;
            }

            $slug = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            if (empty($slug)) {
                continue;
            }

            // Skip expired / outdated historical posts (2024, 2025)
            if ((str_contains($slug, '-2025') || str_contains($slug, '-2024') || str_contains($rawTitle, '2025') || str_contains($rawTitle, '2024'))
                && !str_contains($rawTitle, '2026') && !str_contains($rawTitle, '2027')
            ) {
                continue;
            }

            // Parse company and role from rawTitle
            // Examples:
            // "TCS Summer Internship 2026 : Exciting Opportunity for Freshers"
            // "Odoo Internship 2025 : Hiring for Freshers as Software Developer Intern"
            // "NTT DATA Off Campus Drive 2025 : Hiring for Freshers as Helpdesk Senior"
            $rawClean = str_replace("\xc2\xa0", ' ', $rawTitle);
            $company = 'Company';
            $title = $rawClean;

            if (preg_match('/^(.*?)\s+(Internship|Off Campus Drive|Off Campus|Recruitment|Hiring|Drive)\b(.*)$/iu', $rawClean, $m)) {
                $company = trim(preg_replace('/\s+Summer$/i', '', trim($m[1])));
                $postPart = preg_replace('/^[\s\-\–\:\|\x{2013}\x{2014}]+/u', '', trim($m[3]));
                $postPart = preg_replace('/^20\d\d\s*/u', '', $postPart);
                $postPart = preg_replace('/^[\s\-\–\:\|\x{2013}\x{2014}]+/u', '', $postPart);

                if (preg_match('/(?:as)\s+(.*)$/i', $postPart, $roleM)) {
                    $title = trim($roleM[1]);
                } elseif (!empty($postPart)) {
                    $title = $postPart;
                }
                $title = preg_replace('/\|\s*Apply.*$/i', '', $title);
                $title = trim($title, " –-:|\t\n\r\0\x0B");
            }

            if (empty($title)) {
                $title = $rawTitle;
            }

            $descNode = $art->filter('.entry-summary, .entry-content, p');
            $desc = $descNode->count() > 0 ? trim($descNode->first()->text('')) : $rawTitle;

            $isIntern = (bool) preg_match('/\b(intern|internship)\b/i', $rawTitle);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $title)
                || preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $rawTitle)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $title)
                || (preg_match('/\blead\b/i', $title) && !preg_match('/\blead\s*gen/i', $title)));

            // Exclude senior, staff, principal, head, director and manager roles
            if ($isSenior && !$isIntern) {
                continue;
            }

            if ($company === 'Company') {
                continue;
            }

            $isFresher = (bool) preg_match('/\b(fresher|trainee|graduate|associate|junior|entry[- ]level|drive|off[- ]campus|entry program)\b/i', $rawTitle);
            $employmentType = $isIntern ? 'Internship' : 'Full Time';
            $experienceLevel = $isIntern ? 'Internship' : ($isFresher ? 'Fresher' : null);
            $location = 'India';

            $dedupHash = hash(
                'sha256',
                strtolower(trim($title . '|' . $company . '|' . $location))
            );

            yield $this->item([
                'title' => $title,
                'company_name' => $company,
                'description' => $desc,
                'location' => $location,
                'employment_type' => $employmentType,
                'experience_level' => $experienceLevel,
                'salary' => null,
                'source_name' => 'Fresherscamp',
                'source_url' => $url,
                'source_job_id' => $slug,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
