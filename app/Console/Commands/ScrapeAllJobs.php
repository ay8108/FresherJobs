<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RoachPHP\Roach;

class ScrapeAllJobs extends Command
{
    protected $signature = 'jobs:scrape-all';

    protected $description = 'Scrape jobs from all registered spiders';

    public function handle()
    {
        $spiders = config('job_scrapers.spiders', []);

        if (empty($spiders)) {
            $this->warn('No spiders registered.');
            return self::SUCCESS;
        }

        foreach ($spiders as $spider) {
            $this->newLine();
            $this->info("Starting: {$spider}");

            try {
                Roach::startSpider($spider);

                $this->info("Completed: {$spider}");
            } catch (\Throwable $e) {
                $this->error("Failed: {$spider}");
                $this->error($e->getMessage());
            }
        }

        $this->newLine();
        $this->info('All scraping tasks completed successfully.');

        return self::SUCCESS;
    }
}