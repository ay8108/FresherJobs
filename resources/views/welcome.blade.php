<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Discover fresher jobs and internships from top companies, organized intelligently with AI.">
    <meta name="theme-color" content="#ffffff">
    <title>FresherJobs — Find your next opportunity</title>

    <!-- External Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Application Stylesheet (Clean, structured, external) -->
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>

<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand sticky-top">
        <div class="container">
            <a class="brand d-flex align-items-center" href="#top">
                <span class="brand-mark">
                    <i class="bi bi-briefcase-fill"></i>
                </span>
                FresherJobs
            </a>

            <div class="d-flex align-items-center gap-3">
                <div class="navbar-nav d-none d-sm-flex flex-row gap-3">
                    <a class="nav-link" href="#jobs">Find Jobs</a>
                    <a class="nav-link" href="#about">About</a>
                </div>
                <span class="ai-pill">
                    <i class="bi bi-stars me-1"></i>AI Powered
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main id="top">
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <span class="eyebrow">
                    <i class="bi bi-stars"></i> A smarter way to find work
                </span>
                <h1>Find your next<br class="d-none d-sm-block"> opportunity.</h1>
                <p>Discover fresher jobs and internships from top companies, organized intelligently with AI.</p>

                <!-- Search Form -->
                <form id="searchForm" class="search-box" role="search">
                    <div class="row g-0 align-items-center">
                        <div class="col-md">
                            <label class="search-cell w-100">
                                <i class="bi bi-search"></i>
                                <input id="keyword" type="search" aria-label="Search by job title or company"
                                    placeholder="Job title, role or company">
                            </label>
                        </div>
                        <div class="col-auto d-none d-md-block">
                            <div class="search-divider"></div>
                        </div>
                        <div class="col-md">
                            <label class="search-cell w-100">
                                <i class="bi bi-geo-alt"></i>
                                <input id="locationSearch" type="search" aria-label="Search by city or remote"
                                    placeholder="City or remote location">
                            </label>
                        </div>
                        <div class="col-md-auto">
                            <button class="btn btn-primary search-btn" type="submit">
                                Search jobs <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Live Stored Database Stats -->
                <div class="stats">
                    <div class="stat">
                        <strong id="statTotal">{{ number_format($stats['total'] ?? 0) }}</strong>
                        <span>Total live jobs</span>
                    </div>
                    <div class="stat">
                        <strong id="statClassified">{{ number_format($stats['classified'] ?? 0) }}</strong>
                        <span>AI classified</span>
                    </div>
                    <div class="stat">
                        <strong id="statInternships">{{ number_format($stats['internships'] ?? 0) }}</strong>
                        <span>Internships</span>
                    </div>
                    <div class="stat">
                        <strong id="statRemote">{{ number_format($stats['remote'] ?? 0) }}</strong>
                        <span>Remote jobs</span>
                    </div>
                    <div class="stat">
                        <strong id="statCompanies">{{ number_format($stats['companies'] ?? 0) }}</strong>
                        <span>Hiring companies</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Job Discovery & Filter Explorer -->
        <section class="page-wrap" id="jobs">
            <div class="container">
                <div class="row g-4">
                    <!-- Left Sidebar Filters -->
                    <aside class="col-lg-3">
                        <div class="filter-panel" id="filterPanel">
                            <div class="filter-head">
                                <span class="filter-title">
                                    <i class="bi bi-sliders2 me-1"></i>Filters
                                </span>
                                <button type="button" class="link-btn" id="clearFilters">Reset all</button>
                            </div>

                            <!-- 1. Opportunity Type -->
                            <div class="filter-group">
                                <div class="filter-group-header">
                                    <i class="bi bi-mortarboard-fill"></i> Opportunity Type
                                </div>
                                <div id="experienceFilters">
                                    @include('partials.filters.experience', ['experience' => $filters['experience'] ?? []])
                                </div>
                            </div>

                            <!-- 2. Work Mode / Remote -->
                            <div class="filter-group">
                                <div class="filter-group-header">
                                    <i class="bi bi-laptop-fill"></i> Work Mode
                                </div>
                                <label class="custom-toggle-card">
                                    <input class="form-check-input filter-input" type="checkbox" id="filterRemote" value="1">
                                    <span class="custom-toggle-content">
                                        <span class="custom-toggle-title">Remote positions</span>
                                        <span class="custom-toggle-desc">Work from anywhere</span>
                                    </span>
                                </label>
                            </div>

                            <!-- 3. Job Role -->
                            <div class="filter-group">
                                <div class="filter-group-header">
                                    <i class="bi bi-code-slash"></i> Job Role
                                </div>
                                <div class="filter-search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input type="search" class="filter-search-input" id="roleFilterSearch"
                                        placeholder="Search roles..." aria-label="Search roles">
                                </div>
                                <div id="roleFilters" class="filter-list-scroll">
                                    @include('partials.filters.roles', ['roles' => $filters['roles'] ?? []])
                                </div>
                                <button type="button" class="link-btn more-btn mt-2" id="moreRoles">+ Show more</button>
                            </div>

                            <!-- 4. Location / City -->
                            <div class="filter-group">
                                <div class="filter-group-header">
                                    <i class="bi bi-geo-alt-fill"></i> Location / City
                                </div>
                                <div class="filter-search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input type="search" class="filter-search-input" id="locationFilterSearch"
                                        placeholder="Search cities..." aria-label="Search cities">
                                </div>
                                <div id="locationFilters" class="filter-list-scroll">
                                    @include('partials.filters.cities', ['cities' => $filters['cities'] ?? []])
                                </div>
                                <button type="button" class="link-btn more-btn mt-2" id="moreLocations">+ Show more</button>
                            </div>

                            <!-- 5. Hiring Company -->
                            <div class="filter-group">
                                <div class="filter-group-header">
                                    <i class="bi bi-building-fill"></i> Company
                                </div>
                                <div id="companyFilters" class="filter-list-scroll">
                                    @include('partials.filters.companies', ['companies' => $filters['companies'] ?? []])
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Right Column: Job Results -->
                    <div class="col-lg-9">
                        <!-- Controls Bar -->
                        <div class="content-top">
                            <div>
                                <h2 class="section-title mb-1">Explore opportunities</h2>
                                <div class="subtle small" id="resultSummary" aria-live="polite">{{ $initialSummary ?? 'Loading jobs...' }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="sort-wrap">
                                    <span>Sort by</span>
                                    <select id="sortSelect" class="form-select" aria-label="Sort jobs">
                                        <option value="latest">Latest posted</option>
                                        <option value="relevant">AI match confidence</option>
                                        <option value="company">Company name</option>
                                        <option value="oldest">Oldest</option>
                                    </select>
                                </div>
                                <div class="view-toggle" aria-label="Job layout view">
                                    <button type="button" class="active" data-view="grid" aria-label="Grid view">
                                        <i class="bi bi-grid"></i>
                                    </button>
                                    <button type="button" data-view="list" aria-label="List view">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Active Filter Pills & Mobile Trigger -->
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="active-chips" id="activeChips"></div>
                            <button type="button" class="btn btn-outline-soft d-lg-none" id="mobileFiltersTop">
                                <i class="bi bi-sliders me-1"></i> Filters
                            </button>
                        </div>

                        <!-- Job Listings Container -->
                        <div id="jobResults" aria-live="polite">
                            {!! $initialJobsHtml ?? '' !!}
                        </div>

                        <!-- Pagination Container -->
                        <div id="paginationWrap">
                            {!! $initialPaginationHtml ?? '' !!}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer" id="about">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <a class="brand d-inline-flex align-items-center" href="#top">
                    <span class="brand-mark-sm">
                        <i class="bi bi-briefcase-fill"></i>
                    </span>
                    <span class="brand-name-sm">FresherJobs</span>
                </a>
                <div class="mt-2">Connecting graduates and freshers with verified opportunities directly from top ATS feeds.</div>
                <div class="mt-1 text-secondary">All applications are completed directly on the employer's official career board.</div>
            </div>
            <div>
                <a href="#about">About</a>
                <a href="#jobs">Find Jobs</a>
            </div>
        </div>
    </footer>

    <!-- Job Detail Modal (Container for Server-Rendered Blade Modal) -->
    <div class="modal fade" id="jobModal" tabindex="-1" aria-labelledby="jobModalTitle" aria-hidden="true">
        <div id="jobModalContainer"></div>
    </div>

    <!-- External Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Application JavaScript (Pure, clean, modular) -->
    <script src="{{ asset('js/portal.js') }}"></script>
</body>
</html>