<?php

namespace App\Services;

use App\Models\JobPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GeminiService
{
    /**
     * Standardized role taxonomy for fresher/entry-level categorization.
     */
    private const ROLES = [
        'Laravel Developer', 'PHP Developer', 'Backend Developer', 'Frontend Developer',
        'Full Stack Developer', 'Software Engineer', 'QA Engineer', 'Data Analyst',
        'Data Scientist', 'DevOps Engineer', 'Machine Learning Engineer', 'Mobile Developer',
        'System Administrator', 'Technical Support Engineer', 'UI/UX Designer', 'Product Manager',
        'Project Manager', 'Digital Marketing Specialist', 'Content Writer', 'Sales / Business Development',
        'HR / Recruiter', 'Operations Associate', 'Video Editor', 'Graphic Designer',
        'Cybersecurity Analyst', 'Embedded Systems Engineer', 'Hardware Engineer',
    ];

    /**
     * Classify a batch of jobs in a single Gemini API request.
     *
     * @param array $jobs List of jobs: [['id' => 1, 'title' => '...', 'company_name' => '...', 'location' => '...', 'employment_type' => '...', 'description' => '...']]
     * @return array Map of [jobId => classificationArray]
     */
    public function classifyJobsBatch(array $jobs): array
    {
        if (empty($jobs)) {
            return [];
        }

        $rolesList = implode("\n  * ", self::ROLES);

        $prompt = <<<PROMPT
            You are an expert AI job classifier for an Indian fresher & entry-level job portal.
            Classify each job in the JSON list below. Return a single JSON array of objects with the exact same count and order of items.

            Output fields for each object:
            - id: (integer or string) The exact job id provided in the input.
            - role: A standardized category chosen from this list (or closest match):
            * {$rolesList}
            - city: The exact city name (e.g. Mumbai, Bengaluru, Pune, Delhi, Gurugram, Noida, Hyderabad, Chennai, Kolkata, Ahmedabad).
            Rules:
            1. Return ONLY the city name. Never return a country (India, USA), state (Karnataka, Maharashtra), or "Remote".
            2. If location is purely Remote, multiple locations, or cannot be determined, return "Unknown".
            3. If location has multiple cities (e.g. "Delhi, Noida"), return the primary hiring city.
            - experience_level: "Fresher", "Internship", "Experienced", or "Unknown".
            Rules:
            1. If employment_type is "Internship" or title has "intern", return "Internship".
            2. If title has "Fresher", "Trainee", "Junior", "Graduate", or "Entry-level", return "Fresher".
            3. If title has "Senior", "Sr.", "Lead", "Principal", "Manager", "Head", return "Experienced".
            4. If experience cannot be confidently determined, return "Unknown".
            - confidence: A decimal number between 0.0 and 1.0.

            Jobs to classify:
        PROMPT;

        // Truncate descriptions to 300 chars to optimize token usage
        $trimmedJobs = array_map(fn($j) => [
            'id' => $j['id'] ?? null,
            'title' => $j['title'] ?? '',
            'company_name' => $j['company_name'] ?? '',
            'location' => $j['location'] ?? '',
            'employment_type' => $j['employment_type'] ?? '',
            'description' => isset($j['description']) ? mb_substr(strip_tags($j['description']), 0, 300) : '',
        ], $jobs);

        $prompt .= json_encode($trimmedJobs, JSON_UNESCAPED_UNICODE);

        $model = config('services.gemini.model', 'gemini-flash-lite-latest');
        $apiKey = config('services.gemini.key');

        // Execute API request with automatic retry for rate limits
        $response = $this->callGeminiWithRetry($model, $apiKey, $prompt);

        $rawText = $response->json('candidates.0.content.parts.0.text');
        $decoded = json_decode((string) $rawText, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON array from Gemini: ' . substr((string) $rawText, 0, 200));
        }

        // Handle possible wrapper keys (e.g. {"jobs": [...]})
        if (isset($decoded['jobs']) && is_array($decoded['jobs'])) {
            $decoded = $decoded['jobs'];
        } elseif (!array_is_list($decoded) && isset($decoded['id'])) {
            $decoded = [$decoded];
        }

        $results = [];
        $validExperience = ['Fresher', 'Internship', 'Experienced', 'Unknown'];

        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['id'])) {
                continue;
            }

            $jobId = $item['id'];
            $expLevel = trim((string) ($item['experience_level'] ?? 'Unknown'));

            $results[$jobId] = [
                'role' => trim((string) ($item['role'] ?? 'Software Engineer')),
                'city' => $this->cleanCity($item['city'] ?? null),
                'experience_level' => in_array($expLevel, $validExperience, true) ? $expLevel : 'Unknown',
                'confidence' => isset($item['confidence']) && is_numeric($item['confidence'])
                    ? min(1.0, max(0.0, (float) $item['confidence']))
                    : 0.85,
                'raw_response' => json_encode($item),
            ];
        }

        return $results;
    }

    /**
     * Classify and persist a collection of JobPost models in the database.
     *
     * @param Collection|array $jobs
     * @return int Number of successfully updated jobs
     */
    public function classifyAndSaveBatch($jobs): int
    {
        $jobList = [];
        foreach ($jobs as $job) {
            $jobList[] = [
                'id' => $job->id,
                'title' => $job->title,
                'company_name' => $job->company_name,
                'location' => $job->location,
                'employment_type' => $job->employment_type,
                'description' => $job->description,
            ];
        }

        $classifications = $this->classifyJobsBatch($jobList);
        $savedCount = 0;

        foreach ($jobs as $job) {
            if (isset($classifications[$job->id])) {
                $res = $classifications[$job->id];
                $updateData = [
                    'ai_role' => $res['role'],
                    'ai_city' => $res['city'],
                    'ai_confidence' => $res['confidence'],
                    'ai_raw_response' => $res['raw_response'],
                ];

                if ($res['experience_level'] !== 'Unknown' && empty($job->experience_level)) {
                    $updateData['experience_level'] = $res['experience_level'];
                }

                $job->update($updateData);
                $savedCount++;
            }
        }

        return $savedCount;
    }

    /**
     * Send HTTP POST to Gemini generateContent with 3 retry attempts for rate limits.
     */
    private function callGeminiWithRetry(string $model, ?string $apiKey, string $prompt)
    {
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(45)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                        [
                            'contents' => [
                                ['parts' => [['text' => $prompt]]],
                            ],
                            'generationConfig' => [
                                'responseMimeType' => 'application/json',
                                'temperature' => 0.1,
                            ],
                        ]
                    );

                if ($response->status() === 429 || $response->status() === 503) {
                    $retryAfter = (int) ($response->header('Retry-After') ?: (3 * $attempt));
                    Log::warning("Gemini rate limit {$response->status()} on attempt {$attempt}, sleeping {$retryAfter}s");
                    sleep($retryAfter);
                    continue;
                }

                $response->throw();
                return $response;
            } catch (Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                sleep(2 * $attempt);
            }
        }

        throw new RuntimeException('Gemini API call failed after retries.');
    }

    /**
     * Normalize city output string.
     */
    private function cleanCity(?string $city): string
    {
        $city = trim((string) $city);
        if ($city === '' || strcasecmp($city, 'unknown') === 0) {
            return 'Unknown';
        }

        $city = rtrim($city, ".,;");
        if (str_contains($city, ',')) {
            $city = trim(explode(',', $city)[0]);
        }

        return ucwords(strtolower($city));
    }
}
