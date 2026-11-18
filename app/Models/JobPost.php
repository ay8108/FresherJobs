<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JobPost extends Model
{
    protected $fillable = [
        'title',
        'company_name',
        'description',
        'location',
        'employment_type',
        'experience_level',
        'salary',
        'source_name',
        'source_url',
        'source_job_id',
        'ai_role',
        'ai_city',
        'ai_confidence',
        'ai_raw_response',
        'dedup_hash',
        'posted_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_confidence' => 'decimal:2',
            'posted_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get dynamic brand palette color for company logo.
     */
    public function getLogoClassAttribute(): string
    {
        $palettes = ['logo-blue', 'logo-green', 'logo-orange', 'logo-purple', 'logo-red', 'logo-teal'];
        $name = $this->company_name ?: 'Employer';
        $hash = crc32($name);
        return $palettes[abs($hash) % count($palettes)];
    }

    /**
     * Get single capital letter initial of company.
     */
    public function getCompanyInitialAttribute(): string
    {
        return strtoupper(substr($this->company_name ?: 'E', 0, 1));
    }

    /**
     * Get badge color class according to experience level.
     */
    public function getExperienceBadgeClassAttribute(): string
    {
        return match ($this->experience_level) {
            'Fresher' => 'badge-fresher',
            'Internship' => 'badge-intern',
            'Experienced' => 'badge-experienced',
            default => 'badge-unclassified',
        };
    }

    /**
     * Query overall platform statistics.
     *
     * @return array
     */
    public static function getPortalStats(): array
    {
        return [
            'total' => static::count(),
            'classified' => static::whereNotNull('ai_role')->count(),
            'internships' => static::where('experience_level', 'Internship')->count(),
            'remote' => static::where('location', 'like', '%remote%')->count(),
            'companies' => static::distinct('company_name')->count('company_name'),
            'companies_count' => static::distinct('company_name')->count('company_name'),
        ];
    }

    /**
     * Get clean, relevant experience level choices for intern/fresher candidates.
     *
     * @return array
     */
    public static function getExperienceOptions(): array
    {
        return [
            ['label' => 'Internship', 'value' => 'Internship'],
            ['label' => 'Fresher / Entry Level', 'value' => 'Fresher'],
            ['label' => 'Experienced', 'value' => 'Experienced'],
        ];
    }

    /**
     * Get clean list of distinct roles (without overhead count calculations).
     *
     * @return array
     */
    public static function getRolesList(): array
    {
        return static::whereNotNull('ai_role')
            ->where('ai_role', '!=', '')
            ->distinct()
            ->orderBy('ai_role')
            ->pluck('ai_role')
            ->values()
            ->toArray();
    }

    /**
     * Get clean list of distinct hiring cities (without overhead count calculations).
     *
     * @return array
     */
    public static function getCitiesList(): array
    {
        return static::whereNotNull('ai_city')
            ->where('ai_city', '!=', '')
            ->where('ai_city', '!=', 'Unknown')
            ->distinct()
            ->orderBy('ai_city')
            ->pluck('ai_city')
            ->values()
            ->toArray();
    }

    /**
     * Get clean list of hiring companies (without overhead count calculations).
     *
     * @return array
     */
    public static function getCompaniesList(): array
    {
        return static::whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->distinct()
            ->orderBy('company_name')
            ->pluck('company_name')
            ->values()
            ->toArray();
    }

    /**
     * Filter and paginate job posts from the database.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public static function searchAndFilter(array $filters, int $perPage = 9): LengthAwarePaginator
    {
        $query = static::query();

        // Search keyword in title, company, ai_role or description
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('ai_role', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Location search
        if (!empty($filters['location'])) {
            $location = trim($filters['location']);
            $query->where(function ($q) use ($location) {
                $q->where('location', 'like', "%{$location}%")
                    ->orWhere('ai_city', 'like', "%{$location}%");
            });
        }

        // Experience levels filter (friendly matching for Fresher / Internship)
        if (!empty($filters['experience'])) {
            $experience = is_array($filters['experience'])
                ? $filters['experience']
                : explode(',', $filters['experience']);
            $experience = array_filter(array_map('trim', $experience));

            if (!empty($experience)) {
                $query->where(function ($q) use ($experience) {
                    $hasFresher = in_array('Fresher', $experience, true);
                    $q->whereIn('experience_level', $experience);

                    if ($hasFresher) {
                        $q->orWhere('title', 'like', '%fresher%')
                          ->orWhere('title', 'like', '%graduate%')
                          ->orWhere('title', 'like', '%entry level%')
                          ->orWhere('title', 'like', '%trainee%')
                          ->orWhere('title', 'like', '%junior%');
                    }
                });
            }
        }

        // Roles filter
        if (!empty($filters['roles'])) {
            $roles = is_array($filters['roles'])
                ? $filters['roles']
                : explode(',', $filters['roles']);
            $roles = array_filter(array_map('trim', $roles));
            if (!empty($roles)) {
                $query->whereIn('ai_role', $roles);
            }
        }

        // Cities filter
        if (!empty($filters['cities'])) {
            $cities = is_array($filters['cities'])
                ? $filters['cities']
                : explode(',', $filters['cities']);
            $cities = array_filter(array_map('trim', $cities));
            if (!empty($cities)) {
                $query->whereIn('ai_city', $cities);
            }
        }

        // Companies filter
        if (!empty($filters['companies'])) {
            $companies = is_array($filters['companies'])
                ? $filters['companies']
                : explode(',', $filters['companies']);
            $companies = array_filter(array_map('trim', $companies));
            if (!empty($companies)) {
                $query->whereIn('company_name', $companies);
            }
        }

        // Remote only filter
        if (!empty($filters['remote'])) {
            $query->where('location', 'like', '%remote%');
        }

        // Fresher & Internship only filter
        if (!empty($filters['fresher_only'])) {
            $query->where(function ($q) {
                $q->whereIn('experience_level', ['Fresher', 'Internship'])
                    ->orWhere('title', 'like', '%fresher%')
                    ->orWhere('title', 'like', '%intern%')
                    ->orWhere('title', 'like', '%graduate%')
                    ->orWhere('title', 'like', '%trainee%')
                    ->orWhere('title', 'like', '%junior%');
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? 'latest';
        switch ($sort) {
            case 'oldest':
                $query->orderBy(DB::raw('COALESCE(posted_at, created_at)'), 'asc')
                      ->orderBy('id', 'asc');
                break;
            case 'relevant':
                $query->orderByDesc('ai_confidence')
                      ->orderByDesc('id');
                break;
            case 'company':
                $query->orderBy('company_name', 'asc')
                      ->orderByDesc('id');
                break;
            case 'latest':
            default:
                $query->orderBy(DB::raw('COALESCE(posted_at, created_at)'), 'desc')
                      ->orderByDesc('id');
                break;
        }

        return $query->paginate($perPage);
    }
}
