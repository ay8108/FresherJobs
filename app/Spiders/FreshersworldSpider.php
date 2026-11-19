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

class FreshersworldSpider extends BasicSpider
{
    public array $startUrls = [
        'https://www.freshersworld.com/jobs/jobsearch/it-software-jobs-for-freshers',
        'https://www.freshersworld.com/jobs/jobsearch/internship-jobs',
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
        $cards = $crawler->filter('.job-container');

        $isInternship = str_contains(strtolower($response->getRequest()->getUri()), 'internship');

        foreach ($cards as $cardNode) {
            $card = new Crawler($cardNode);

            // Title
            $seoTitle = trim($card->filter('.wrap-title.seo_title, .seo_title')->text(''));
            $title = preg_replace('/\s+Job(s)?\s+Opening\b.*$/i', '', $seoTitle);
            $title = preg_replace('/\s+at\s+.*$/i', '', $title);
            $title = trim(preg_replace('/(Less|More)$/i', '', $title));

            if (empty($title)) {
                $rawTitleNode = $card->filter('.wrap-title, .job-title');
                if ($rawTitleNode->count() > 0) {
                    $title = trim(preg_replace('/(Less|More)$/i', '', $rawTitleNode->first()->text('')));
                }
            }

            if (empty($title)) {
                continue;
            }

            $isIntern = $isInternship || (bool) preg_match('/\b(intern|internship)\b/i', $title);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $title)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $title)
                || (preg_match('/\blead\b/i', $title) && !preg_match('/\blead\s*gen/i', $title)));

            // Exclude senior, staff, principal, head, director and manager roles from fresher feed
            if ($isSenior && !$isIntern) {
                continue;
            }

            // Company
            $company = trim($card->filter('.latest-jobs-title, .bold_title')->text(''));
            if (empty($company)) {
                $company = 'Freshersworld Employer';
            }

            // Location
            $location = trim($card->filter('.job-location, .locations')->text(''));
            if (empty($location)) {
                $location = 'India';
            }

            // Salary
            $salaryNode = $card->filter('.salary');
            $salary = $salaryNode->count() > 0 ? trim($salaryNode->text('')) : null;

            // Description / snippet
            $descNode = $card->filter('.desc, .job-desc');
            $desc = $descNode->count() > 0 ? trim($descNode->text('')) : '';

            // Job URL and ID
            $linkNodes = $card->filter('a[href*="/jobs/"]');
            if ($linkNodes->count() === 0) {
                continue;
            }
            $url = $linkNodes->last()->attr('href');
            if (!str_starts_with($url, 'http')) {
                $url = 'https://www.freshersworld.com' . '/' . ltrim($url, '/');
            }

            preg_match('/-(\d+)$/', $url, $matches);
            $jobId = $matches[1] ?? md5($url);

            $dedupHash = hash(
                'sha256',
                strtolower(trim($title . '|' . $company . '|' . $location))
            );

            yield $this->item([
                'title' => $title,
                'company_name' => $company,
                'description' => $desc,
                'location' => $location,
                'employment_type' => $isInternship ? 'Internship' : 'Full Time',
                'experience_level' => $isInternship ? 'Internship' : 'Fresher',
                'salary' => $salary,
                'source_name' => 'Freshersworld',
                'source_url' => $url,
                'source_job_id' => (string) $jobId,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
