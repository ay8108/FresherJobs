<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JobPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    protected JobPortalService $portalService;

    /**
     * Inject the JobPortalService.
     */
    public function __construct(JobPortalService $portalService)
    {
        $this->portalService = $portalService;
    }

    /**
     * Return paginated filtered jobs rendered via Blade partials.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->get('per_page', 9), 1), 50);
        $jobs = $this->portalService->getJobs($request->all(), $perPage);

        $html = view('partials.job-list', compact('jobs'))->render();
        $paginationHtml = view('partials.pagination', ['paginator' => $jobs])->render();

        $from = $jobs->firstItem() ?? 0;
        $to = $jobs->lastItem() ?? 0;
        $total = $jobs->total();
        $summary = "Showing {$from}–{$to} of {$total} matching jobs";

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination_html' => $paginationHtml,
            'summary' => $summary,
            'total' => $total,
            'current_page' => $jobs->currentPage(),
            'last_page' => $jobs->lastPage(),
        ]);
    }

    /**
     * Return filter options rendered via Blade partials and live stats.
     *
     * @return JsonResponse
     */
    public function filters(): JsonResponse
    {
        $metadata = $this->portalService->getFilterMetadata();
        $filters = $metadata['filters'];

        $experienceHtml = view('partials.filters.experience', ['experience' => $filters['experience']])->render();
        $rolesHtml = view('partials.filters.roles', ['roles' => $filters['roles']])->render();
        $citiesHtml = view('partials.filters.cities', ['cities' => $filters['cities']])->render();
        $companiesHtml = view('partials.filters.companies', ['companies' => $filters['companies']])->render();

        return response()->json([
            'success' => true,
            'experience_html' => $experienceHtml,
            'roles_html' => $rolesHtml,
            'cities_html' => $citiesHtml,
            'companies_html' => $companiesHtml,
            'stats' => $metadata['stats'],
        ]);
    }

    /**
     * Return single job post details rendered via Blade modal partial.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $job = $this->portalService->getJobById($id);
        $html = view('partials.job-modal', compact('job'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'data' => $job,
        ]);
    }
}
