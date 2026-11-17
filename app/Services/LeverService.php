<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LeverService
{
    public function fetchJobs(string $company): array
    {
        $url = "https://api.lever.co/v0/postings/{$company}";

        $response = Http::timeout(30)
            ->get($url, [
                'mode' => 'json',
            ]);

        $response->throw();

        return $response->json() ?? [];
    }
}
