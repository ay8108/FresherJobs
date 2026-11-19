<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;
use Symfony\Component\DomCrawler\Crawler;
use App\ItemProcessors\SaveJobProcessor;

class InternshalaSpider extends BasicSpider
{
    public array $startUrls = [
        'https://internshala.com/internships/',
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

        $cards = $crawler->filter('[id^="individual_internship_"]');

        foreach ($cards as $cardNode) {
            $card = new Crawler($cardNode);

            $title = trim($card->filter('.job-title-href')->text(''));
            $company = trim($card->filter('.company-name')->text(''));
            $location = trim($card->filter('.locations')->text(''));
            $salary = trim($card->filter('.stipend')->text(''));
            $description = trim($card->filter('.about_job')->text(''));

            $jobId = str_replace(
                'individual_internship_',
                '',
                $card->attr('id')
            );

            $jobUrl = $card->filter('.job-title-href')->attr('data-href');

            if (empty($title) || empty($jobId)) {
                continue;
            }

            $jobUrl = $jobUrl
                ? 'https://internshala.com' . $jobUrl
                : 'https://internshala.com/internship/detail/' . $jobId;

            $dedupHash = hash(
                'sha256',
                strtolower(trim($title . '|' . $company . '|' . $location))
            );

            yield $this->item([
                'title' => $title,
                'company_name' => $company,
                'description' => $description,
                'location' => $location,
                'employment_type' => 'Internship',
                'experience_level' => 'Fresher',
                'salary' => $salary,
                'source_name' => 'Internshala',
                'source_url' => $jobUrl,
                'source_job_id' => $jobId,
                'dedup_hash' => $dedupHash,
                'last_seen_at' => now(),
            ]);
        }
    }
}
