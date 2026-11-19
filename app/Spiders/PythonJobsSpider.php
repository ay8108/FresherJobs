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

class PythonJobsSpider extends BasicSpider
{
    public array $startUrls = [
        'https://www.python.org/jobs/',
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
        $h2Nodes = $crawler->filter('h2.listing-company');

        foreach ($h2Nodes as $h2Node) {
            $h2 = new Crawler($h2Node);
            $parentLi = $h2Node->parentNode;
            $li = new Crawler($parentLi);

            $titleNode = $h2->filter('a');
            if ($titleNode->count() === 0) {
                continue;
            }

            $href = $titleNode->attr('href');
            if (!preg_match('#^/jobs/(\d+)/#', $href, $matches)) {
                continue;
            }

            $jobId = $matches[1];
            $title = trim($titleNode->text(''));

            if (empty($title)) {
                continue;
            }

            // Extract company name
            $compSpan = $h2->filter('.listing-company-name');
            $company = 'Python Employer';
            if ($compSpan->count() > 0) {
                $compRaw = trim($compSpan->text(''));
                $compRaw = preg_replace('/^New\s+/i', '', $compRaw);
                $compRaw = str_replace($title, '', $compRaw);
                $company = trim($compRaw) ?: 'Python Employer';
            }

            // Extract location
            $locNode = $h2->filter('.listing-location a, .listing-location');
            $location = $locNode->count() > 0 ? trim($locNode->text('')) : 'Remote';
            if (empty($location)) {
                $location = 'Remote';
            }

            // Extract Job Type / employment type
            $typeNode = $li->filter('.listing-job-type');
            $employmentType = $typeNode->count() > 0 ? trim($typeNode->text('')) : 'Full Time';
            if (empty($employmentType)) {
                $employmentType = 'Full Time';
            }

            // Extract Posted date
            $postedAt = null;
            $timeNode = $li->filter('time');
            if ($timeNode->count() > 0 && $timeNode->attr('datetime')) {
                try {
                    $postedAt = new \DateTime($timeNode->attr('datetime'));
                } catch (\Throwable $t) {
                    $postedAt = null;
                }
            }

            $url = 'https://www.python.org' . $href;

            $isIntern = (bool) preg_match('/\b(intern|internship)\b/i', $title);
            $isFresher = (bool) preg_match('/\b(junior|entry[- ]level|fresher|graduate|trainee)\b/i', $title);
            $isSenior = (bool) (preg_match('/\b(senior|sr\.|staff|principal|head|director|manager|architect|vp|chief)\b/i', $title)
                || preg_match('/\b(team lead|tech lead|project lead)\b/i', $title)
                || (preg_match('/\blead\b/i', $title) && !preg_match('/\blead\s*gen/i', $title)));

            // Exclude senior, staff, principal, head, director and manager roles from fresher feed
            if ($isSenior && !$isIntern) {
                continue;
            }

            // Keep remote jobs only when eligibility for candidates from India is confirmed or reasonably clear
            $isExplicitlyExcludingIndia = (bool) preg_match('/\b(north america|usa|us|europe|uk|canada|germany|latin america|latam|poland|france|spain|estonia|ukraine|lithuania|australia)\b/i', $location);
            $isIndiaEligible = (bool) preg_match('/\b(india|asia|apac|worldwide|anywhere|global)\b/i', $location)
                || (preg_match('/\bremote\b/i', $location) && !$isExplicitlyExcludingIndia);

            if (!$isIndiaEligible && !$isFresher) {
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
                'description' => "{$title} at {$company} ({$location}). Categories: {$employmentType}.",
                'location' => $location,
                'employment_type' => 'Full Time',
                'experience_level' => $experienceLevel,
                'salary' => null,
                'source_name' => 'PythonJobs',
                'source_url' => $url,
                'source_job_id' => $jobId,
                'dedup_hash' => $dedupHash,
                'posted_at' => $postedAt,
                'last_seen_at' => now(),
            ]);
        }
    }
}
