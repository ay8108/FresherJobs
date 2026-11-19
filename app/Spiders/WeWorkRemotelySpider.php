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

class WeWorkRemotelySpider extends BasicSpider
{
    public array $startUrls = [
        'https://weworkremotely.com/categories/remote-programming-jobs',
        'https://weworkremotely.com/categories/remote-devops-sysadmin-jobs',
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
        $links = $crawler->filter('section.jobs article li a[href*="/remote-jobs/"]');

        $seenIds = [];

        foreach ($links as $linkNode) {
            $a = new Crawler($linkNode);
            $href = $a->attr('href');

            if (empty($href) || str_contains($href, '/categories/')) {
                continue;
            }

            $slug = trim(str_replace('/remote-jobs/', '', parse_url($href, PHP_URL_PATH) ?? ''), '/');
            if (empty($slug) || isset($seenIds[$slug])) {
                continue;
            }
            $seenIds[$slug] = true;

            // Extract Title
            $titleNode = $a->filter('.new-listing__header__title__text, .title');
            $title = $titleNode->count() > 0 ? trim($titleNode->text('')) : '';

            if (empty($title)) {
                continue;
            }

            // Extract Company
            $compNode = $a->filter('.new-listing__company-name, .company');
            $company = $compNode->count() > 0 ? trim($compNode->text('')) : 'WWR Employer';

            // Extract Location / Region
            $locNode = $a->filter('.new-listing__company-headquarters, .region');
            $location = $locNode->count() > 0 ? trim(str_replace(['HQ:', 'Region:'], '', $locNode->text(''))) : 'Remote';
            if (empty($location)) {
                $location = 'Remote';
            }

            // Extract Categories (Salary, Type, Region)
            $salary = null;
            $employmentType = 'Full Time';
            $categoryNodes = $a->filter('.new-listing__categories__category');
            foreach ($categoryNodes as $catNode) {
                $catText = trim($catNode->textContent);
                if (str_contains($catText, '$') || str_contains(strtolower($catText), 'usd') || str_contains(strtolower($catText), 'eur')) {
                    $salary = $catText;
                } elseif (in_array(strtolower($catText), ['full-time', 'part-time', 'contract', 'freelance'])) {
                    $employmentType = ucwords($catText);
                }
            }

            $url = 'https://weworkremotely.com' . '/' . ltrim($href, '/');

            $isIntern = (bool) preg_match('/\b(intern|internship)\b/i', $title);
            $isFresher = (bool) preg_match('/\b(junior|entry[- ]level|fresher|graduate|trainee)\b/i', $title);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $title)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $title)
                || (preg_match('/\blead\b/i', $title) && !preg_match('/\blead\s*gen/i', $title)));

            // Exclude senior, staff, principal, head, director and manager roles
            if ($isSenior && !$isIntern) {
                continue;
            }

            // Keep remote jobs only when eligibility for candidates from India is confirmed or reasonably clear
            $isIndiaEligible = (bool) preg_match('/\b(anywhere in the world|worldwide|global|india|asia|apac)\b/i', $location);
            $isExplicitlyExcludingIndia = (bool) preg_match('/\b(usa only|us only|north america only|europe only|uk only|canada only|latam only|emea only)\b/i', $location);

            if (!$isIndiaEligible && ($isExplicitlyExcludingIndia || !$isFresher)) {
                continue;
            }

            // Do not blindly classify jobs as Fresher or Entry-level when experience information is missing
            $experienceLevel = $isIntern ? 'Internship' : ($isFresher ? 'Fresher' : null);

            $dedupHash = hash(
                'sha256',
                strtolower(trim($title . '|' . $company . '|' . $location))
            );

            yield $this->item([
                'title' => $title,
                'company_name' => $company,
                'description' => "{$title} at {$company} ({$location}). Employment type: {$employmentType}.",
                'location' => $location,
                'employment_type' => $employmentType,
                'experience_level' => $experienceLevel,
                'salary' => $salary,
                'source_name' => 'WeWorkRemotely',
                'source_url' => $url,
                'source_job_id' => $slug,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
