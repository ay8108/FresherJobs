<?php

namespace App\Http\Controllers;

use App\Services\JobPortalService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    protected JobPortalService $portalService;

    /**
     * Inject the business logic service.
     */
    public function __construct(JobPortalService $portalService)
    {
        $this->portalService = $portalService;
    }

    /**
     * Return application welcome/home view with pre-rendered data from service layer.
     *
     * @return View
     */
    public function index(): View
    {
        $data = $this->portalService->getHomePageData();
        $metadata = $this->portalService->getFilterMetadata();
        $jobs = $this->portalService->getJobs([], 9);

        $data['filters'] = $metadata['filters'];
        $data['initialJobsHtml'] = view('partials.job-list', compact('jobs'))->render();
        $data['initialPaginationHtml'] = view('partials.pagination', ['paginator' => $jobs])->render();
        $from = $jobs->firstItem() ?? 0;
        $to = $jobs->lastItem() ?? 0;
        $data['initialSummary'] = "Showing {$from}–{$to} of {$jobs->total()} matching jobs";

        return view('welcome', $data);
    }
}
