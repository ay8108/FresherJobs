<?php

namespace App\Services;

use App\Models\JobPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JobPortalService
{
    /**
     * Get home page overview data by calling Model query functions.
     *
     * @return array
     */
    public function getHomePageData(): array
    {
        return [
            'stats' => JobPost::getPortalStats(),
        ];
    }

    /**
     * Get clean, relevant filter options for job discovery (without heavy count calculation).
     *
     * @return array
     */
    public function getFilterMetadata(): array
    {
        return [
            'filters' => [
                'experience' => JobPost::getExperienceOptions(),
                'roles' => JobPost::getRolesList(),
                'cities' => JobPost::getCitiesList(),
                'companies' => JobPost::getCompaniesList(),
            ],
            'stats' => JobPost::getPortalStats(),
        ];
    }

    /**
     * Get paginated jobs based on filter criteria.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getJobs(array $filters, int $perPage = 9): LengthAwarePaginator
    {
        return JobPost::searchAndFilter($filters, $perPage);
    }

    /**
     * Find single job details by ID.
     *
     * @param int $id
     * @return JobPost
     */
    public function getJobById(int $id): JobPost
    {
        return JobPost::findOrFail($id);
    }
}
