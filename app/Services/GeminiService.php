<?php

namespace App\Services;

use App\Models\JobPost;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    public function classifyJob(array $job): array
    {
        $prompt = <<<PROMPT
            You are an expert job classification AI.

            Analyze the job and return a single JSON object only.
            Do not return a JSON array or markdown.

            Required fields:
            - role: Job role/category.
            - city: City name if explicitly identifiable from the job location, regardless of country. Otherwise Unknown.
            - experience_level: Fresher, Internship, Experienced, or Unknown.
            - fresher_relevance: Yes, No, or Unclear.
            - confidence: Number between 0 and 1.

            GENERAL RULES:
            1. Do not assume missing information.
            2. Use Unknown when information is unavailable.
            3. Never guess or invent information.
            4. Consider job title, description and actual job requirements when determining experience level and fresher relevance.
            5. Senior, Staff, Lead, Principal and Manager titles generally indicate Experienced unless the description clearly says otherwise.
            6. Intern, Internship and Graduate Trainee roles should generally be classified as Internship or Fresher based on the actual posting.

            LOCATION RULES:
            1. The provided location field is the primary source for job location.
            2. Extract the actual city from the location field regardless of country.
            3. Examples:
            - Bangkok, Thailand = Bangkok
            - London, United Kingdom = London
            - Sydney, Australia = Sydney
            - Barcelona, Spain = Barcelona
            - San Francisco, CA = San Francisco
            - Gurugram, India = Gurugram
            - Remote - Bangalore, India = Bangalore
            - Singapore = Singapore
            4. If location contains only a country name, return Unknown.
            5. If location says Remote - US, Remote - Canada, Remote, USA or Remote - European Union without a city, return Unknown.
            6. Never extract cities from company descriptions or office addresses.
            7. Never guess a city.
            8. If multiple locations are provided without a specific hiring location, return Unknown.

            Job Data:
        PROMPT;

        $prompt .= json_encode($job, JSON_UNESCAPED_UNICODE);

        $model = config('services.gemini.model');

        $response = Http::timeout(60)
            ->retry([1000, 2000, 4000])
            ->withHeaders([
                'x-goog-api-key' => config('services.gemini.key'),
            ])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.1,
                    ],
                ]
            );

        $response->throw();

        $result = $response->json('candidates.0.content.parts.0.text');

        if (!is_string($result) || blank($result)) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        $data = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new RuntimeException('Invalid Gemini JSON response.');
        }

        // Handle unexpected JSON array response.
        if (array_is_list($data)) {
            if (!isset($data[0]) || !is_array($data[0])) {
                throw new RuntimeException('Gemini returned an invalid JSON array.');
            }

            $data = $data[0];
        }

        // Validate required fields.
        $requiredFields = [
            'role',
            'city',
            'experience_level',
            'fresher_relevance',
            'confidence',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new RuntimeException(
                    "Gemini response is missing required field: {$field}"
                );
            }
        }

        // Validate experience level.
        $allowedExperienceLevels = [
            'Fresher',
            'Internship',
            'Experienced',
            'Unknown',
        ];

        if (!in_array($data['experience_level'], $allowedExperienceLevels, true)) {
            $data['experience_level'] = 'Unknown';
        }

        // Validate confidence.
        if (
            !is_numeric($data['confidence']) ||
            $data['confidence'] < 0 ||
            $data['confidence'] > 1
        ) {
            throw new RuntimeException('Gemini returned invalid confidence value.');
        }

        // Always extract city from the actual location field.
        // This prevents Gemini from incorrectly returning Unknown
        // when the location explicitly contains a city.
        $data['city'] = $this->extractCityFromLocation(
            $job['location'] ?? ''
        );

        return $data;
    }

    private function extractCityFromLocation(?string $location): string
    {
        if (blank($location)) {
            return 'Unknown';
        }

        $location = trim($location);

        // Remove Remote prefix.
        $location = preg_replace('/^remote\s*[-,:]?\s*/i', '', $location);

        $location = trim($location);

        if ($location === '') {
            return 'Unknown';
        }

        $parts = array_values(array_filter(
            array_map('trim', explode(',', $location)),
            fn ($part) => $part !== ''
        ));

        if (empty($parts)) {
            return 'Unknown';
        }

        $firstPart = trim($parts[0]);

        // Country and region names that should not be treated as cities.
        $nonCityLocations = [
            'india',
            'united states',
            'united states of america',
            'usa',
            'us',
            'canada',
            'united kingdom',
            'uk',
            'china',
            'japan',
            'australia',
            'ireland',
            'france',
            'germany',
            'italy',
            'spain',
            'brazil',
            'mexico',
            'thailand',
            'south korea',
            'north korea',
            'european union',
            'europe',
            'asia',
            'north america',
            'south america',
        ];

        if (in_array(strtolower($firstPart), $nonCityLocations, true)) {
            return 'Unknown';
        }

        // Singapore is both a country and a city-state.
        if (strtolower($firstPart) === 'singapore') {
            return 'Singapore';
        }

        // Return the first location component as the city.
        return $firstPart;
    }

    public function classifyAndSave(JobPost $job): JobPost
    {
        $result = $this->classifyJob(
            $job->only([
                'title',
                'company_name',
                'location',
                'description',
            ])
        );

        $job->update([
            'ai_role' => $result['role'],
            'ai_city' => $result['city'],
            'experience_level' => $result['experience_level'],
            'ai_confidence' => $result['confidence'],
            'ai_raw_response' => json_encode($result),
        ]);

        return $job->fresh();
    }
}
