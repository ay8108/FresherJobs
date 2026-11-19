<?php

namespace App\Services;

use App\Models\JobPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GeminiService
{
    private array $nonCityTerms = [
        // Remote & generic
        'remote', 'work from home', 'wfh', 'hybrid', 'onsite', 'worldwide', 'global',
        'anywhere', 'anywhere in the world', 'multiple locations', 'unknown',
        // Continents / Regions
        'north america', 'south america', 'europe', 'european union', 'eu', 'asia',
        'apac', 'emea', 'latam', 'africa', 'middle east', 'oceania', 'uk/eu',
        // Countries
        'india', 'united states', 'united states of america', 'usa', 'us', 'canada',
        'united kingdom', 'uk', 'australia', 'germany', 'france', 'china', 'japan',
        'ireland', 'spain', 'italy', 'brazil', 'mexico', 'thailand', 'south korea',
        'russia', 'netherlands', 'switzerland', 'uae', 'united arab emirates',
        'saudi arabia', 'new zealand', 'poland', 'ukraine', 'sweden', 'norway',
        'denmark', 'south africa', 'indonesia', 'malaysia', 'vietnam', 'philippines',
        // Indian States
        'maharashtra', 'karnataka', 'tamil nadu', 'telangana', 'andhra pradesh',
        'uttar pradesh', 'madhya pradesh', 'west bengal', 'gujarat', 'rajasthan',
        'kerala', 'bihar', 'punjab', 'haryana', 'odisha', 'assam', 'jharkhand',
        'chhattisgarh', 'uttarakhand', 'himachal pradesh', 'goa', 'tripura', 'manipur',
        'meghalaya', 'nagaland', 'mizoram', 'sikkim', 'arunachal pradesh',
        // US States
        'california', 'texas', 'florida', 'new york', 'pennsylvania', 'illinois',
        'ohio', 'georgia', 'north carolina', 'michigan', 'new jersey', 'virginia',
        'washington', 'arizona', 'massachusetts', 'tennessee', 'indiana', 'missouri',
        'maryland', 'wisconsin', 'colorado', 'minnesota', 'south carolina', 'alabama',
        'louisiana', 'kentucky', 'oregon', 'oklahoma', 'connecticut', 'utah', 'iowa',
        'nevada', 'arkansas', 'mississippi', 'kansas', 'new mexico', 'nebraska', 'idaho',
        'hawaii', 'maine', 'montana', 'delaware', 'south dakota', 'north dakota', 'alaska',
        'vermont', 'wyoming',
    ];

    /**
     * Known Indian city normalization mapping (e.g. Bangalore -> Bengaluru, Gurgaon -> Gurugram).
     */
    private array $cityNormalizations = [
        'bangalore' => 'Bengaluru',
        'bengaluru' => 'Bengaluru',
        'gurgaon' => 'Gurugram',
        'gurugram' => 'Gurugram',
        'bombay' => 'Mumbai',
        'mumbai' => 'Mumbai',
        'calcutta' => 'Kolkata',
        'kolkata' => 'Kolkata',
        'madras' => 'Chennai',
        'chennai' => 'Chennai',
        'delhi' => 'Delhi',
        'new delhi' => 'Delhi',
        'pune' => 'Pune',
        'hyderabad' => 'Hyderabad',
        'noida' => 'Noida',
        'greater noida' => 'Greater Noida',
        'ahmedabad' => 'Ahmedabad',
        'jaipur' => 'Jaipur',
        'indore' => 'Indore',
        'chandigarh' => 'Chandigarh',
        'kochi' => 'Kochi',
        'coimbatore' => 'Coimbatore',
        'lucknow' => 'Lucknow',
        'bhopal' => 'Bhopal',
        'nagpur' => 'Nagpur',
        'bhubaneswar' => 'Bhubaneswar',
        'tiruchirappalli' => 'Tiruchirappalli',
        'tiruchirapalli' => 'Tiruchirappalli',
        'bharuch' => 'Bharuch',
        'agra' => 'Agra',
        'faridabad' => 'Faridabad',
        'ghaziabad' => 'Ghaziabad',
        'madurai' => 'Madurai',
        'mysore' => 'Mysuru',
        'mysuru' => 'Mysuru',
        'visakhapatnam' => 'Visakhapatnam',
        'vizag' => 'Visakhapatnam',
        'thiruvananthapuram' => 'Thiruvananthapuram',
        'trivandrum' => 'Thiruvananthapuram',
        'navi mumbai' => 'Navi Mumbai',
    ];

    /**
     * Classify a batch of jobs in a single Gemini API request.
     *
     * @param array $jobs List of job data arrays: [['id' => 1, 'title' => '...', 'company_name' => '...', 'location' => '...', 'employment_type' => '...', 'description' => '...'], ...]
     * @return array Map of [jobId => classifiedDataArray]
     */
    public function classifyJobsBatch(array $jobs): array
    {
        if (empty($jobs)) {
            return [];
        }

        $prompt = <<<PROMPT
You are an expert AI job classifier for an Indian fresher & entry-level job portal.
Classify each job in the JSON list below. Return a single JSON array of objects with the exact same count and order of items.

Output fields for each object:
- id: (integer or string) The exact job id provided in the input.
- role: A standardized job role category chosen from the following list (or closely matching standard):
  * Laravel Developer
  * PHP Developer
  * Backend Developer
  * Frontend Developer
  * Full Stack Developer
  * Software Engineer
  * QA Engineer
  * Data Analyst
  * Data Scientist
  * DevOps Engineer
  * Machine Learning Engineer
  * Mobile Developer
  * System Administrator
  * Technical Support Engineer
  * UI/UX Designer
  * Product Manager
  * Project Manager
  * Digital Marketing Specialist
  * Content Writer
  * Sales / Business Development
  * HR / Recruiter
  * Operations Associate
  * Video Editor
  * Graphic Designer
  * Cybersecurity Analyst
  * Embedded Systems Engineer
  * Hardware Engineer
- city: The exact city name (e.g. Mumbai, Bengaluru, Pune, Delhi, Gurugram, Noida, Hyderabad, Chennai, Kolkata, Ahmedabad, London, San Francisco, etc.).
  CRITICAL CITY RULES:
  1. Return ONLY the city name.
  2. NEVER return a country (e.g. India, USA, United Kingdom, Canada), state/province (e.g. Maharashtra, Karnataka, California, Missouri, South Carolina), continent/region (e.g. Europe, Asia, North America, APAC, LATAM), or remote tag (e.g. Remote, Work From Home, Worldwide, Anywhere).
  3. If location is purely Remote, or contains only a country/state or unspecifiable locations, return "Unknown".
  4. If location has multiple cities (e.g. "Delhi, Noida, Gurgaon"), return the primary hiring city (e.g. "Delhi").
  5. If location cannot be determined, return "Unknown".
- experience_level: "Fresher", "Internship", "Experienced", or "Unknown".
  CRITICAL EXPERIENCE RULES:
  1. If employment_type is "Internship" or title has "intern" or "internship", experience_level MUST be "Internship".
  2. If title has "Fresher", "Trainee", "Junior", "Graduate", or "Entry-level", classify as "Fresher".
  3. If title has "Senior", "Sr.", "Lead", "Principal", "Staff", "Manager", "Head", classify as "Experienced".
  4. If experience cannot be confidently determined from title or description, return "Unknown" (do NOT guess).
- confidence: A decimal number between 0.0 and 1.0.

Jobs to classify:
PROMPT;

        // Strip huge descriptions to keep tokens optimal (max 300 chars per description)
        $trimmedJobs = array_map(function ($j) {
            return [
                'id' => $j['id'] ?? null,
                'title' => $j['title'] ?? '',
                'company_name' => $j['company_name'] ?? '',
                'location' => $j['location'] ?? '',
                'employment_type' => $j['employment_type'] ?? '',
                'description' => isset($j['description']) ? mb_substr(strip_tags($j['description']), 0, 300) : '',
            ];
        }, $jobs);

        $prompt .= json_encode($trimmedJobs, JSON_UNESCAPED_UNICODE);

        $model = config('services.gemini.model', 'gemini-flash-lite-latest');
        $apiKey = config('services.gemini.key');

        $maxAttempts = 3;
        $attempt = 0;
        $response = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
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
                    Log::warning("Gemini rate limit {$response->status()} encountered on attempt {$attempt}, sleeping {$retryAfter}s");
                    sleep($retryAfter);
                    continue;
                }

                $response->throw();
                break;
            } catch (Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                sleep(2 * $attempt);
            }
        }

        if (!$response || !$response->successful()) {
            throw new RuntimeException('Gemini API call failed after retries: ' . ($response ? $response->body() : 'No response'));
        }

        $rawText = $response->json('candidates.0.content.parts.0.text');
        if (!is_string($rawText) || blank($rawText)) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        $decoded = json_decode($rawText, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON array from Gemini: ' . substr($rawText, 0, 200));
        }

        // If returned as a single object with a wrapper key like "jobs" or single object
        if (isset($decoded['jobs']) && is_array($decoded['jobs'])) {
            $decoded = $decoded['jobs'];
        } elseif (!array_is_list($decoded) && isset($decoded['id'])) {
            $decoded = [$decoded];
        }

        $results = [];
        $allowedExperienceLevels = ['Fresher', 'Internship', 'Experienced', 'Unknown'];

        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['id'])) {
                continue;
            }

            $jobId = $item['id'];
            $origJob = collect($jobs)->firstWhere('id', $jobId) ?? [];

            $role = trim((string) ($item['role'] ?? 'Software Engineer'));
            $rawCity = trim((string) ($item['city'] ?? 'Unknown'));
            $cleanCity = $this->sanitizeCity($rawCity, $origJob['location'] ?? '');

            $expLevel = trim((string) ($item['experience_level'] ?? 'Unknown'));
            if (!in_array($expLevel, $allowedExperienceLevels, true)) {
                $expLevel = 'Unknown';
            }

            $confidence = isset($item['confidence']) && is_numeric($item['confidence'])
                ? min(1.0, max(0.0, (float) $item['confidence']))
                : 0.85;

            $results[$jobId] = [
                'role' => $role,
                'city' => $cleanCity,
                'experience_level' => $expLevel,
                'confidence' => $confidence,
                'raw_response' => json_encode($item),
            ];
        }

        return $results;
    }

    /**
     * Classify and persist a collection of JobPost models in the database.
     *
     * @param \Illuminate\Support\Collection|array $jobs
     * @return int Number of successfully classified and saved jobs
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

                // Update experience_level if it was null/unknown and LLM determined it
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
     * Backward-compatible single job classifier.
     */
    public function classifyJob(array $job): array
    {
        $batch = [array_merge(['id' => $job['id'] ?? 1], $job)];
        $results = $this->classifyJobsBatch($batch);
        $res = $results[$job['id'] ?? 1] ?? null;

        if (!$res) {
            return [
                'role' => 'Software Engineer',
                'city' => 'Unknown',
                'experience_level' => 'Unknown',
                'confidence' => 0.5,
            ];
        }

        return [
            'role' => $res['role'],
            'city' => $res['city'],
            'experience_level' => $res['experience_level'],
            'fresher_relevance' => in_array($res['experience_level'], ['Fresher', 'Internship']) ? 'Yes' : 'No',
            'confidence' => $res['confidence'],
        ];
    }

    /**
     * Backward-compatible single job save.
     */
    public function classifyAndSave(JobPost $job): JobPost
    {
        $this->classifyAndSaveBatch([$job]);
        return $job->fresh();
    }

    /**
     * Sanitize city name: Never return state, country, remote string, or continent.
     */
    public function sanitizeCity(?string $city, ?string $origLocation = ''): string
    {
        if (blank($city)) {
            $city = 'Unknown';
        }

        $city = trim($city);
        $city = preg_replace('/^city of\s+/i', '', $city);
        $city = preg_replace('/[\.\,\;]+$/', '', $city);

        // If city contains a comma (e.g. "Bengaluru, Karnataka"), take the first piece
        if (str_contains($city, ',')) {
            $city = trim(explode(',', $city)[0]);
        }

        $lowerCity = strtolower($city);

        // Check against forbidden non-city terms
        if (in_array($lowerCity, $this->nonCityTerms, true)) {
            $city = 'Unknown';
        }

        // Check if city matches any country or state name partially
        if ($city !== 'Unknown') {
            foreach ($this->nonCityTerms as $term) {
                if ($lowerCity === $term || $lowerCity === 'remote - ' . $term || $lowerCity === $term . ' - remote') {
                    $city = 'Unknown';
                    break;
                }
            }
        }

        // If city is Unknown, check if the original location has a clean recognizable Indian metro
        if ($city === 'Unknown' && !empty($origLocation)) {
            $lowerOrig = strtolower($origLocation);
            foreach ($this->cityNormalizations as $needle => $normalizedName) {
                if (preg_match('/\b' . preg_quote($needle, '/') . '\b/i', $lowerOrig)) {
                    return $normalizedName;
                }
            }
            return 'Unknown';
        }

        // If city is recognized in normalization map, return standardized name
        if (isset($this->cityNormalizations[$lowerCity])) {
            return $this->cityNormalizations[$lowerCity];
        }

        return $city === 'Unknown' ? 'Unknown' : ucwords($lowerCity);
    }
}
