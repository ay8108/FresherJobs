<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Discover fresher jobs and internships from top companies, organized intelligently with AI.">
    <meta name="theme-color" content="#ffffff">
    <title>FresherJobs — Find your next opportunity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #15243b;
            --muted: #748198;
            --blue: #4359e8;
            --line: #e9edf3;
            --wash: #f6f8fc;
            --green: #16846b
        }

        * {
            box-sizing: border-box
        }

        body {
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: #fff;
            font-size: 14px
        }

        .navbar {
            height: 72px;
            border-bottom: 1px solid var(--line);
            background: rgba(255, 255, 255, .96)
        }

        .brand {
            font-weight: 760;
            font-size: 19px;
            letter-spacing: -.7px;
            color: var(--ink);
            text-decoration: none
        }

        .brand-mark {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            background: var(--blue);
            display: inline-grid;
            place-items: center;
            color: #fff;
            margin-right: 9px
        }

        .nav-link {
            color: #536178;
            font-weight: 550
        }

        .ai-pill {
            border: 1px solid #dfe4ff;
            background: #f4f5ff;
            color: var(--blue);
            border-radius: 99px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700
        }

        .hero {
            background: radial-gradient(ellipse at 80% 4%, #e7eaff 0, transparent 34%), linear-gradient(180deg, #f7f8ff 0%, #fff 100%);
            padding: 58px 0 30px
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--blue);
            font-weight: 700;
            font-size: 12px;
            background: #eef0ff;
            padding: 7px 11px;
            border-radius: 99px
        }

        .hero h1 {
            font-size: clamp(36px, 5vw, 54px);
            line-height: 1.08;
            letter-spacing: -2.8px;
            font-weight: 760;
            margin: 18px 0 12px
        }

        .hero p {
            color: #69768d;
            font-size: 16px;
            max-width: 600px
        }

        .search-box {
            background: #fff;
            padding: 8px;
            border: 1px solid #e6e9f0;
            border-radius: 16px;
            box-shadow: 0 12px 40px #24366012;
            max-width: 850px
        }

        .search-cell {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 8px 14px;
            min-width: 0
        }

        .search-cell i {
            font-size: 18px;
            color: #8490a5
        }

        .search-cell input {
            border: 0;
            outline: 0;
            width: 100%;
            font-size: 14px;
            color: var(--ink)
        }

        .search-cell input::placeholder {
            color: #929bad
        }

        .search-divider {
            height: 32px;
            width: 1px;
            background: var(--line)
        }

        .search-btn {
            white-space: nowrap;
            padding: 12px 21px;
            border-radius: 11px;
            font-weight: 650
        }

        .quick-search {
            color: var(--muted);
            font-size: 12px;
            margin-top: 14px
        }

        .quick-tag {
            background: white;
            border: 1px solid var(--line);
            border-radius: 99px;
            color: #56647b;
            padding: 6px 11px;
            font-size: 12px;
            margin: 0 5px 6px 0;
            transition: .2s
        }

        .quick-tag:hover {
            border-color: #abb5ff;
            color: var(--blue)
        }

        .stats {
            display: flex;
            gap: 35px;
            margin-top: 30px
        }

        .stat strong {
            display: block;
            font-size: 18px;
            letter-spacing: -.4px
        }

        .stat span {
            color: var(--muted);
            font-size: 11px
        }

        .page-wrap {
            padding: 35px 0 64px
        }

        .section-title {
            font-size: 22px;
            font-weight: 740;
            letter-spacing: -.7px
        }

        .subtle {
            color: var(--muted)
        }

        .filter-panel {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 19px;
            background: #fff;
            position: sticky;
            top: 91px
        }

        .filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px
        }

        .filter-head strong {
            font-size: 14px
        }

        .link-btn {
            border: 0;
            background: none;
            color: var(--blue);
            font-size: 12px;
            font-weight: 650;
            padding: 0
        }

        .filter-group {
            border-top: 1px solid var(--line);
            padding: 15px 0 3px
        }

        .filter-group h3 {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px
        }

        .form-check {
            margin-bottom: 10px;
            min-height: 17px
        }

        .form-check-input {
            margin-top: .18em;
            border-color: #cbd2df
        }

        .form-check-input:checked {
            background-color: var(--blue);
            border-color: var(--blue)
        }

        .form-check-label {
            font-size: 12px;
            color: #536178
        }

        .count {
            color: #98a2b3;
            margin-left: auto;
            font-size: 11px
        }

        .filter-check {
            display: flex;
            align-items: center
        }

        .content-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px
        }

        .sort-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 12px
        }

        .sort-wrap .form-select {
            width: auto;
            min-width: 125px;
            font-size: 12px;
            border-color: var(--line);
            padding-top: 8px;
            padding-bottom: 8px
        }

        .view-toggle {
            display: flex;
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 3px
        }

        .view-toggle button {
            border: 0;
            background: white;
            border-radius: 6px;
            color: #758198;
            width: 31px;
            height: 29px
        }

        .view-toggle button.active {
            background: #eff1ff;
            color: var(--blue)
        }

        .active-chips {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            min-height: 26px;
            margin: 13px 0 4px
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 9px;
            border: 1px solid #dfe4ff;
            border-radius: 99px;
            background: #f6f7ff;
            color: #4c5bd0;
            font-size: 11px
        }

        .filter-chip button {
            border: 0;
            background: none;
            color: inherit;
            padding: 0;
            line-height: 1
        }

        .job-list {
            display: grid;
            gap: 12px;
            margin-top: 12px
        }

        .job-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 17px 18px;
            transition: .2s;
            background: white
        }

        .job-card:hover {
            border-color: #d4dafe;
            box-shadow: 0 8px 24px #1d31510a;
            transform: translateY(-1px)
        }

        .company-logo {
            height: 43px;
            width: 43px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-weight: 750;
            font-size: 15px;
            flex: 0 0 43px
        }

        .logo-blue {
            background: #eaf0ff;
            color: #3555c7
        }

        .logo-green {
            background: #e4f6ee;
            color: #18825f
        }

        .logo-orange {
            background: #fff0e2;
            color: #c36a1b
        }

        .logo-purple {
            background: #f0eaff;
            color: #7345bc
        }

        .logo-red {
            background: #ffebeb;
            color: #c44848
        }

        .logo-teal {
            background: #e3f5f6;
            color: #26838b
        }

        .job-title {
            font-size: 15px;
            font-weight: 720;
            color: var(--ink);
            letter-spacing: -.2px
        }

        .company-name {
            font-size: 12px;
            color: #68758a;
            margin-top: 2px
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 13px;
            margin-top: 12px;
            color: #67748a;
            font-size: 11px
        }

        .job-meta i {
            color: #98a3b4;
            margin-right: 4px
        }

        .job-description {
            color: #707d91;
            font-size: 12px;
            line-height: 1.55;
            margin: 12px 0 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden
        }

        .job-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-top: 1px solid #f0f2f6;
            margin-top: 14px;
            padding-top: 12px
        }

        .source {
            font-size: 10px;
            color: #8a95a7
        }

        .source i {
            color: #abb4c3
        }

        .confidence {
            font-size: 10px;
            color: var(--green);
            margin-left: 9px
        }

        .badge-soft {
            border-radius: 99px;
            padding: 5px 8px;
            font-size: 10px;
            font-weight: 650
        }

        .badge-fresher {
            background: #e9f7ef;
            color: #19835b
        }

        .badge-intern {
            background: #fff3df;
            color: #a76b0d
        }

        .badge-experienced {
            background: #edf0ff;
            color: #4d5dd5
        }

        .role-label {
            background: #f1f4f8;
            color: #627087
        }

        .card-actions {
            display: flex;
            gap: 7px
        }

        .btn-outline-soft {
            border: 1px solid #e2e6ee;
            background: #fff;
            color: #43516a;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 650;
            padding: 7px 11px
        }

        .btn-outline-soft:hover {
            background: #f6f7fa
        }

        .btn-primary {
            background: var(--blue);
            border-color: var(--blue)
        }

        .btn-primary:hover {
            background: #3448d2;
            border-color: #3448d2
        }

        .btn-apply {
            border-radius: 8px;
            font-size: 11px;
            font-weight: 650;
            padding: 7px 12px
        }

        .pagination {
            margin: 20px 0 0
        }

        .page-link {
            border: 0;
            border-radius: 7px !important;
            color: #536178;
            font-size: 12px;
            margin: 0 2px
        }

        .page-item.active .page-link {
            background: var(--blue)
        }

        .empty-state {
            text-align: center;
            border: 1px dashed #dce2ec;
            background: #fafbfe;
            border-radius: 14px;
            padding: 48px 20px
        }

        .empty-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: #eef0ff;
            color: var(--blue);
            font-size: 22px;
            margin: 0 auto 15px
        }

        .empty-state h3 {
            font-size: 17px;
            font-weight: 700
        }

        .empty-state p {
            color: var(--muted);
            font-size: 13px
        }

        .footer {
            border-top: 1px solid var(--line);
            padding: 25px 0;
            color: #7b8799;
            font-size: 11px
        }

        .footer a {
            color: #647187;
            text-decoration: none;
            margin-left: 17px
        }

        .modal-content {
            border: 0;
            border-radius: 16px
        }

        .modal-header,
        .modal-footer {
            border-color: var(--line)
        }

        .detail-section {
            font-size: 13px;
            line-height: 1.7;
            color: #59677d
        }

        .detail-section h3 {
            font-size: 13px;
            color: var(--ink);
            font-weight: 700;
            margin: 20px 0 7px
        }

        .detail-list {
            padding-left: 18px
        }

        .list-view .job-list {
            display: block
        }

        .list-view .job-card {
            margin-bottom: 10px
        }

        .list-view .job-description {
            max-width: 820px
        }

        @media(max-width:767px) {
            .navbar {
                height: 62px
            }

            .hero {
                padding: 40px 0 25px
            }

            .hero h1 {
                letter-spacing: -1.8px
            }

            .search-box {
                padding: 10px
            }

            .search-cell {
                padding: 9px 7px
            }

            .search-divider {
                display: none
            }

            .search-box .row {
                gap: 4px
            }

            .search-btn {
                width: 100%
            }

            .stats {
                gap: 17px;
                flex-wrap: wrap
            }

            .stat strong {
                font-size: 16px
            }

            .page-wrap {
                padding-top: 25px
            }

            .filter-panel {
                position: static;
                margin-bottom: 17px;
                display: none
            }

            .filter-panel.open {
                display: block
            }

            .content-top {
                align-items: flex-start
            }

            .content-top .section-title {
                font-size: 20px
            }

            .sort-wrap {
                gap: 5px
            }

            .sort-wrap>span,
            .view-toggle {
                display: none
            }

            .job-card {
                padding: 14px
            }

            .job-footer {
                align-items: flex-start;
                flex-direction: column
            }

            .footer .container {
                gap: 12px;
                align-items: flex-start !important;
                flex-direction: column
            }

            .footer a {
                margin: 0 12px 0 0
            }

            .nav-link {
                font-size: 13px
            }

            .ai-pill {
                display: none
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand sticky-top">
        <div class="container"><a class="brand d-flex align-items-center" href="#top"><span class="brand-mark"><i
                        class="bi bi-briefcase-fill"></i></span>FresherJobs</a>
            <div class="d-flex align-items-center gap-3">
                <div class="navbar-nav d-none d-sm-flex flex-row gap-3"><a class="nav-link" href="#jobs">Find Jobs</a><a
                        class="nav-link" href="#companies">Companies</a><a class="nav-link" href="#about">About</a>
                </div><span class="ai-pill"><i class="bi bi-stars me-1"></i>AI Powered</span>
            </div>
        </div>
    </nav>
    <main id="top">
        <section class="hero">
            <div class="container"><span class="eyebrow"><i class="bi bi-stars"></i> A smarter way to find work</span>
                <h1>Find your next<br class="d-none d-sm-block"> opportunity.</h1>
                <p>Discover fresher jobs and internships from top companies, organized intelligently with AI.</p>
                <form id="searchForm" class="search-box" role="search">
                    <div class="row g-0 align-items-center">
                        <div class="col-md"><label class="search-cell w-100"><i class="bi bi-search"></i><input
                                    id="keyword" type="search" aria-label="Search by job title or company"
                                    placeholder="Job title or company"></label></div>
                        <div class="col-auto d-none d-md-block">
                            <div class="search-divider"></div>
                        </div>
                        <div class="col-md"><label class="search-cell w-100"><i class="bi bi-geo-alt"></i><input
                                    id="locationSearch" type="search" aria-label="Search by city"
                                    placeholder="City or remote"></label></div>
                        <div class="col-md-auto"><button class="btn btn-primary search-btn" type="submit">Search jobs <i
                                    class="bi bi-arrow-right ms-1"></i></button></div>
                    </div>
                </form>
                <div class="quick-search">Popular: <button class="quick-tag" data-search="Laravel Developer">Laravel
                        Developer</button><button class="quick-tag" data-search="Backend Developer">Backend
                        Developer</button><button class="quick-tag" data-search="Frontend Developer">Frontend
                        Developer</button><button class="quick-tag" data-search="Software Engineer">Software
                        Engineer</button><button class="quick-tag" data-search="Internship">Internship</button></div>
                <div class="stats">
                    <div class="stat"><strong>2,480+</strong><span>Total jobs</span></div>
                    <div class="stat"><strong>840</strong><span>Fresher opportunities</span></div>
                    <div class="stat"><strong>320</strong><span>Internships</span></div>
                    <div class="stat"><strong>186</strong><span>Companies hiring</span></div>
                </div>
            </div>
        </section>
        <section class="page-wrap" id="jobs">
            <div class="container">
                <div class="row g-4">
                    <aside class="col-lg-3"><button class="btn btn-outline-soft d-lg-none mb-3 w-100" id="mobileFilters"
                            aria-expanded="false"><i class="bi bi-sliders me-2"></i>Filters</button>
                        <div class="filter-panel" id="filterPanel">
                            <div class="filter-head"><strong><i class="bi bi-sliders me-2"></i>Filters</strong><button
                                    class="link-btn" id="clearFilters">Clear all</button></div>
                            <div class="filter-group">
                                <h3>Experience level</h3>
                                <div id="experienceFilters"><label class="form-check filter-check"><input
                                            class="form-check-input filter-input" type="checkbox" value="Fresher"
                                            data-filter="experience"><span class="form-check-label">Fresher</span><span
                                            class="count">840</span></label><label
                                        class="form-check filter-check"><input class="form-check-input filter-input"
                                            type="checkbox" value="Internship" data-filter="experience"><span
                                            class="form-check-label">Internship</span><span
                                            class="count">320</span></label><label
                                        class="form-check filter-check"><input class="form-check-input filter-input"
                                            type="checkbox" value="Experienced" data-filter="experience"><span
                                            class="form-check-label">Experienced</span></label><label
                                        class="form-check filter-check"><input class="form-check-input filter-input"
                                            type="checkbox" value="Unknown" data-filter="experience"><span
                                            class="form-check-label">Unknown</span></label></div>
                            </div>
                            <div class="filter-group">
                                <h3>Job role</h3>
                                <div id="roleFilters"></div><button class="link-btn mt-1" id="moreRoles">Show all
                                    roles</button>
                            </div>
                            <div class="filter-group">
                                <h3>Location</h3><input class="form-control form-control-sm mb-2"
                                    id="locationFilterSearch" placeholder="Search locations"
                                    aria-label="Search locations">
                                <div id="locationFilters"></div><button class="link-btn mt-1" id="moreLocations">Show
                                    all locations</button>
                            </div>
                            <div class="filter-group">
                                <h3>Employment type</h3>
                                <div id="typeFilters"></div>
                            </div>
                            <div class="filter-group">
                                <h3>More options</h3><label class="form-check"><input
                                        class="form-check-input filter-input" type="checkbox" data-filter="remote"
                                        value="true"><span class="form-check-label">Remote jobs
                                        only</span></label><label class="form-check"><input
                                        class="form-check-input filter-input" type="checkbox" data-filter="friendly"
                                        value="true"><span class="form-check-label">Fresher-friendly</span></label>
                            </div>
                        </div>
                    </aside>
                    <div class="col-lg-9">
                        <div class="content-top">
                            <div>
                                <h2 class="section-title mb-1">Explore jobs</h2>
                                <div class="subtle small" id="resultSummary" aria-live="polite"></div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="sort-wrap"><span>Sort by</span><select id="sortSelect" class="form-select"
                                        aria-label="Sort jobs">
                                        <option value="latest">Latest</option>
                                        <option value="oldest">Oldest</option>
                                        <option value="relevant">Most relevant</option>
                                    </select></div>
                                <div class="view-toggle" aria-label="Job layout"><button class="active" data-view="grid"
                                        aria-label="Grid view"><i class="bi bi-grid"></i></button><button
                                        data-view="list" aria-label="List view"><i class="bi bi-list-ul"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="active-chips" id="activeChips"></div><button
                                class="btn btn-outline-soft d-lg-none" id="mobileFiltersTop"><i
                                    class="bi bi-sliders"></i></button>
                        </div>
                        <div id="jobResults" aria-live="polite"></div>
                        <div id="paginationWrap"></div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer class="footer" id="about">
        <div class="container d-flex justify-content-between align-items-center">
            <div><a class="brand d-inline-flex align-items-center" href="#top"><span class="brand-mark"
                        style="width:27px;height:27px;border-radius:8px;font-size:12px"><i
                            class="bi bi-briefcase-fill"></i></span><span style="font-size:15px">FresherJobs</span></a>
                <div class="mt-2">We make your next opportunity easier to find.</div>
                <div class="mt-1">FresherJobs aggregates job listings from external sources. All applications are
                    completed on the original employer websites.</div>
            </div>
            <div><a href="#about">About FresherJobs</a><a href="#jobs">Find Jobs</a><a href="#privacy">Privacy
                    Policy</a><a href="#disclaimer">Disclaimer</a></div>
        </div>
    </footer>
    <div class="modal fade" id="jobModal" tabindex="-1" aria-labelledby="jobModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="company-logo" id="modalLogo"></div>
                            <div>
                                <h2 class="modal-title fs-5 fw-bold" id="jobModalTitle"></h2>
                                <div class="company-name" id="modalCompany"></div>
                            </div>
                        </div>
                    </div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="job-meta mb-3" id="modalMeta"></div>
                    <div id="modalBadges" class="d-flex gap-2 flex-wrap mb-3"></div>
                    <div class="detail-section" id="modalDescription"></div>
                </div>
                <div class="modal-footer justify-content-between"><span class="source" id="modalSource"></span><a
                        id="modalApply" class="btn btn-primary btn-apply" target="_blank"
                        rel="noopener noreferrer">Apply on company site <i
                            class="bi bi-box-arrow-up-right ms-1"></i></a></div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function ($) {
            'use strict';
            const jobs = [
                { id: 1, title: 'Junior Laravel Developer', company: 'Razorpay', logo: 'R', logoClass: 'logo-blue', location: 'Bangalore', experience: 'Fresher', role: 'Backend Developer', type: 'Full Time', salary: '₹6–10 LPA', posted: '2026-11-16', source: 'Greenhouse', confidence: 96, remote: false, friendly: true, url: 'https://razorpay.com/jobs/', description: 'Build reliable payment experiences used by millions. You will work with a collaborative engineering team to develop and maintain Laravel services, write clean APIs, and learn from experienced developers.', requirements: ['0–1 years of experience with PHP and Laravel', 'Familiarity with REST APIs, SQL, and Git', 'Strong fundamentals and eagerness to learn'], full: true },
                { id: 2, title: 'Software Engineer Intern', company: 'Atlassian', logo: 'A', logoClass: 'logo-blue', location: 'Remote', experience: 'Internship', role: 'Software Engineer', type: 'Internship', salary: '₹45,000 / month', posted: '2026-11-15', source: 'Ashby', confidence: 98, remote: true, friendly: true, url: 'https://www.atlassian.com/company/careers', description: 'Join a product engineering team for a six-month internship. Contribute to customer-facing features, take part in code reviews, and grow your skills with a dedicated mentor.', requirements: ['Currently pursuing a CS or related degree', 'Knowledge of one programming language', 'Available for a six-month internship'], full: true },
                { id: 3, title: 'Frontend Developer', company: 'CRED', logo: 'C', logoClass: 'logo-green', location: 'Bangalore', experience: 'Fresher', role: 'Frontend Developer', type: 'Full Time', salary: '₹8–14 LPA', posted: '2026-11-14', source: 'Company Careers', confidence: 93, remote: false, friendly: true, url: 'https://careers.cred.club/', description: 'Help craft polished, fast web experiences for members. Partner with design and product to deliver accessible interfaces using modern JavaScript and React.', requirements: ['Strong JavaScript, HTML, and CSS fundamentals', 'A portfolio or project demonstrating frontend craft', 'Interest in performance and accessibility'], full: true },
                { id: 4, title: 'Data Analyst Intern', company: 'Swiggy', logo: 'S', logoClass: 'logo-orange', location: 'Hyderabad', experience: 'Internship', role: 'Data Analyst', type: 'Internship', salary: '₹30,000 / month', posted: '2026-11-12', source: 'Greenhouse', confidence: 91, remote: false, friendly: true, url: 'https://careers.swiggy.com/', description: 'Turn marketplace data into clear insights that help teams make better decisions. Work with analysts on dashboards, experiments, and operational reporting.', requirements: ['Comfortable with SQL and spreadsheets', 'Basic understanding of statistics', 'Clear written communication'], full: true },
                { id: 5, title: 'QA Engineer — Graduate', company: 'Freshworks', logo: 'F', logoClass: 'logo-purple', location: 'Chennai', experience: 'Fresher', role: 'QA Engineer', type: 'Full Time', salary: '₹5–8 LPA', posted: '2026-11-10', source: 'Ashby', confidence: 89, remote: false, friendly: true, url: 'https://www.freshworks.com/company/careers/', description: 'Help ship dependable customer support software. Build test plans, investigate bugs, and work closely with developers across the product lifecycle.', requirements: ['2025 or 2026 graduate in CS or related field', 'Understanding of software testing concepts', 'Curiosity and attention to detail'], full: true },
                { id: 6, title: 'DevOps Engineer', company: 'Postman', logo: 'P', logoClass: 'logo-orange', location: 'Remote', experience: 'Experienced', role: 'DevOps Engineer', type: 'Full Time', salary: '₹18–28 LPA', posted: '2026-11-08', source: 'Company Careers', confidence: 88, remote: true, friendly: false, url: 'https://www.postman.com/company/careers/', description: 'Build and operate the infrastructure behind a platform trusted by millions of developers. Improve deployment workflows, observability, and service reliability.', requirements: ['3+ years in cloud infrastructure or SRE', 'Experience with Kubernetes and CI/CD', 'Strong automation and incident response skills'], full: true },
                { id: 7, title: 'Product Design Intern', company: 'Meesho', logo: 'M', logoClass: 'logo-red', location: 'Bangalore', experience: 'Internship', role: 'UI/UX Designer', type: 'Internship', salary: '₹35,000 / month', posted: '2026-11-06', source: 'Greenhouse', confidence: 94, remote: false, friendly: true, url: 'https://www.meesho.io/jobs', description: 'Explore real user problems and design thoughtful shopping experiences. Work with product designers on research, flows, prototypes, and visual design.', requirements: ['Portfolio with interaction or product design work', 'Comfort with Figma', 'Empathy for diverse users'], full: true },
                { id: 8, title: 'Backend Engineer', company: 'PhonePe', logo: 'P', logoClass: 'logo-purple', location: 'Pune', experience: 'Experienced', role: 'Backend Developer', type: 'Full Time', salary: '₹20–32 LPA', posted: '2026-11-03', source: 'Ashby', confidence: 92, remote: false, friendly: false, url: 'https://www.phonepe.com/careers/', description: 'Design and operate highly available services for products at massive scale. Own systems end-to-end and collaborate across engineering, product, and data.', requirements: ['2+ years building backend services', 'Proficiency in Java, Go, or similar', 'Experience with distributed systems'], full: true },
                { id: 9, title: 'Full Stack Developer — New Grad', company: 'BrowserStack', logo: 'B', logoClass: 'logo-teal', location: 'Mumbai', experience: 'Fresher', role: 'Full Stack Developer', type: 'Full Time', salary: '₹7–12 LPA', posted: '2026-10-30', source: 'Company Careers', confidence: 86, remote: true, friendly: true, url: 'https://www.browserstack.com/careers', description: 'Help build tools used by developers around the world. Pair with experienced engineers to deliver product features from database to interface.', requirements: ['Graduate in computer science or equivalent', 'Fundamentals of web development', 'Project experience with a modern stack'], full: true },
                { id: 10, title: 'Cloud Engineering Trainee', company: 'Zomato', logo: 'Z', logoClass: 'logo-red', location: 'Gurugram', experience: 'Fresher', role: 'DevOps Engineer', type: 'Full Time', salary: '₹6–9 LPA', posted: '2026-10-27', source: 'Greenhouse', confidence: 82, remote: false, friendly: true, url: 'https://www.zomato.com/careers', description: 'Start your cloud engineering career supporting services that power everyday food discovery. Learn infrastructure automation, monitoring, and cloud operations.', requirements: ['Recent graduate in CS or IT', 'Linux and networking fundamentals', 'Good problem-solving skills'], full: true },
                { id: 11, title: 'Backend Developer Intern', company: 'Groww', logo: 'G', logoClass: 'logo-green', location: 'Pune', experience: 'Internship', role: 'Backend Developer', type: 'Internship', salary: '₹40,000 / month', posted: '2026-10-24', source: 'Ashby', confidence: 90, remote: false, friendly: true, url: 'https://groww.in/careers', description: 'Build dependable backend features for a growing investment platform. Learn from experienced engineers and contribute to production systems.', requirements: ['Proficiency in one programming language', 'Understanding of databases and APIs', 'Available for a four-to-six-month internship'], full: true },
                { id: 12, title: 'Frontend Engineer', company: 'Acko', logo: 'A', logoClass: 'logo-blue', location: 'Mumbai', experience: 'Experienced', role: 'Frontend Developer', type: 'Contract', salary: '₹15–22 LPA', posted: '2026-10-20', source: 'Company Careers', confidence: 80, remote: true, friendly: false, url: 'https://www.acko.com/careers/', description: 'Develop responsive, accessible insurance experiences used across devices. Work with designers and engineers to make complex flows feel simple.', requirements: ['2+ years building production frontend apps', 'Strong HTML, CSS, and JavaScript', 'Experience with accessibility best practices'], full: true }
            ];
            const roles = ['Backend Developer', 'Frontend Developer', 'Full Stack Developer', 'Software Engineer', 'QA Engineer', 'Data Analyst', 'DevOps Engineer', 'UI/UX Designer', 'Other']; const locations = ['Mumbai', 'Pune', 'Bangalore', 'Hyderabad', 'Chennai', 'Delhi', 'Gurugram', 'Remote', 'Other']; const types = ['Full Time', 'Internship', 'Part Time', 'Contract']; let page = 1; const pageSize = 6; let expandedRoles = false, expandedLocations = false;
            function buildFilters() { const render = (items, target, field, extra) => { const show = extra && items.length > 5 && !extra.expanded ? items.slice(0, 5) : items; $(target).html(show.map(v => `<label class="form-check filter-check"><input class="form-check-input filter-input" type="checkbox" data-filter="${field}" value="${v}"><span class="form-check-label">${v}</span></label>`).join('')); }; render(roles, '#roleFilters', 'role', { expanded: expandedRoles }); render(locations, '#locationFilters', 'location', { expanded: expandedLocations }); render(types, '#typeFilters', 'type'); }
            function selected(field) { return $(`.filter-input[data-filter="${field}"]:checked`).map((_, e) => e.value).get(); }
            function filteredJobs() { let list = jobs.slice(); const q = $('#keyword').val().trim().toLowerCase(), loc = $('#locationSearch').val().trim().toLowerCase(); if (q) list = list.filter(j => [j.title, j.company, j.role, j.description].some(x => x.toLowerCase().includes(q))); if (loc) list = list.filter(j => j.location.toLowerCase().includes(loc));['experience', 'role', 'location', 'type'].forEach(k => { const v = selected(k); if (v.length) list = list.filter(j => v.includes(j[k])); }); if ($('.filter-input[data-filter="remote"]').is(':checked')) list = list.filter(j => j.remote); if ($('.filter-input[data-filter="friendly"]').is(':checked')) list = list.filter(j => j.friendly); const sort = $('#sortSelect').val(); list.sort((a, b) => sort === 'oldest' ? a.posted.localeCompare(b.posted) : sort === 'relevant' ? b.confidence - a.confidence : b.posted.localeCompare(a.posted)); return list; }
            function card(j) { const exp = j.experience === 'Fresher' ? 'badge-fresher' : j.experience === 'Internship' ? 'badge-intern' : 'badge-experienced'; return `<article class="job-card"><div class="d-flex gap-3"><div class="company-logo ${j.logoClass}">${j.logo}</div><div class="flex-grow-1 min-w-0"><div class="d-flex justify-content-between gap-2"><div><div class="job-title">${j.title}</div><div class="company-name">${j.company}</div></div><button class="btn btn-link text-secondary p-0 align-self-start" aria-label="Save ${j.title}" title="Save job"><i class="bi bi-bookmark"></i></button></div><div class="job-meta"><span><i class="bi bi-geo-alt"></i>${j.location}</span><span><i class="bi bi-clock"></i>${j.type}</span>${j.salary ? `<span><i class="bi bi-currency-rupee"></i>${j.salary}</span>` : ''}</div><p class="job-description">${j.description}</p><div class="job-footer"><div class="d-flex flex-wrap gap-2 align-items-center"><span class="badge-soft ${exp}">${j.experience}</span><span class="badge-soft role-label">${j.role}</span><span class="source"><i class="bi bi-globe2"></i> ${j.source} · ${new Date(j.posted + 'T12:00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}</span><span class="confidence"><i class="bi bi-stars"></i> ${j.confidence}% AI match</span></div><div class="card-actions"><button class="btn-outline-soft details-btn" data-id="${j.id}">View details</button><a class="btn btn-primary btn-apply" href="${j.url}" target="_blank" rel="noopener noreferrer">Apply now <i class="bi bi-arrow-up-right"></i></a></div></div></div></div></article>`; }
            function activeChips() { let chips = [];['experience', 'role', 'location', 'type'].forEach(k => selected(k).forEach(v => chips.push({ k, v, label: v }))); if ($('.filter-input[data-filter="remote"]').is(':checked')) chips.push({ k: 'remote', v: 'true', label: 'Remote only' }); if ($('.filter-input[data-filter="friendly"]').is(':checked')) chips.push({ k: 'friendly', v: 'true', label: 'Fresher-friendly' }); $('#activeChips').html(chips.map(c => `<span class="filter-chip">${c.label}<button aria-label="Remove ${c.label}" data-filter="${c.k}" data-value="${c.v}"><i class="bi bi-x"></i></button></span>`).join('')); }
            function render() { const list = filteredJobs(); const total = list.length; const pages = Math.max(1, Math.ceil(total / pageSize)); page = Math.min(page, pages); const visible = list.slice((page - 1) * pageSize, page * pageSize); $('#resultSummary').text(`Showing ${total ? ((page - 1) * pageSize + 1) : 0}–${Math.min(page * pageSize, total)} of ${total} matching jobs`); activeChips(); if (!total) { $('#jobResults').html('<div class="empty-state mt-3"><div class="empty-icon"><i class="bi bi-search"></i></div><h3>No jobs found matching your filters.</h3><p>Try adjusting your search or clear your filters to see more opportunities.</p><button class="btn btn-primary btn-sm" id="emptyClear">Clear filters</button></div>'); $('#paginationWrap').empty(); return; } $('#jobResults').html('<div class="job-list">' + visible.map(card).join('') + '</div>'); const start = (page - 1) * pageSize + 1, end = Math.min(page * pageSize, total); let html = `<div class="d-flex justify-content-between align-items-center mt-3"><span class="source">Showing ${start}–${end} of ${total} results</span><nav aria-label="Job results pages"><ul class="pagination pagination-sm mb-0"><li class="page-item ${page === 1 ? 'disabled' : ''}"><button class="page-link" data-page="${page - 1}" aria-label="Previous page"><i class="bi bi-chevron-left"></i></button></li>`; for (let i = 1; i <= pages; i++)html += `<li class="page-item ${page === i ? 'active' : ''}"><button class="page-link" data-page="${i}" aria-label="Page ${i}" ${page === i ? 'aria-current="page"' : ''}>${i}</button></li>`; html += `<li class="page-item ${page === pages ? 'disabled' : ''}"><button class="page-link" data-page="${page + 1}" aria-label="Next page"><i class="bi bi-chevron-right"></i></button></li></ul></nav></div>`; $('#paginationWrap').html(html); }
            function clear() { $('.filter-input').prop('checked', false); $('#keyword,#locationSearch,#locationFilterSearch').val(''); $('#sortSelect').val('latest'); page = 1; render(); }
            $(document).on('change', '.filter-input,#sortSelect', function () { page = 1; render(); }); $('#searchForm').on('submit', e => { e.preventDefault(); page = 1; render(); }); $('.quick-tag').on('click', function () { $('#keyword').val($(this).data('search')); page = 1; render(); document.querySelector('#jobs').scrollIntoView({ behavior: 'smooth' }); }); $('#clearFilters').on('click', clear); $(document).on('click', '#emptyClear', clear); $('#moreRoles').on('click', function () { expandedRoles = !expandedRoles; buildFilters(); $(this).text(expandedRoles ? 'Show fewer roles' : 'Show all roles'); }); $('#moreLocations').on('click', function () { expandedLocations = !expandedLocations; buildFilters(); $(this).text(expandedLocations ? 'Show fewer locations' : 'Show all locations'); }); $('#locationFilterSearch').on('input', function () { const q = this.value.toLowerCase(); $('#locationFilters label').each(function () { $(this).toggle($(this).text().toLowerCase().includes(q)); }); }); $(document).on('click', '.filter-chip button', function () { const k = $(this).data('filter'), v = $(this).data('value'); $(`.filter-input[data-filter="${k}"][value="${v}"]`).prop('checked', false); page = 1; render(); }); $(document).on('click', '.page-link[data-page]', function () { const p = Number($(this).data('page')); const pages = Math.max(1, Math.ceil(filteredJobs().length / pageSize)); if (p >= 1 && p <= pages) { page = p; render(); document.querySelector('#jobs').scrollIntoView({ behavior: 'smooth' }); } }); $('.view-toggle button').on('click', function () { $('.view-toggle button').removeClass('active'); $(this).addClass('active'); $('#jobResults').toggleClass('list-view', $(this).data('view') === 'list'); }); $('#mobileFilters,#mobileFiltersTop').on('click', function () { const open = $('#filterPanel').toggleClass('open').hasClass('open'); $('#mobileFilters').attr('aria-expanded', open); }); $(document).on('click', '.details-btn', function () { const j = jobs.find(x => x.id === Number($(this).data('id'))); if (!j) return; $('#modalLogo').attr('class', `company-logo ${j.logoClass}`).text(j.logo); $('#jobModalTitle').text(j.title); $('#modalCompany').text(j.company); $('#modalMeta').html(`<span><i class="bi bi-geo-alt"></i>${j.location}</span><span><i class="bi bi-clock"></i>${j.type}</span><span><i class="bi bi-calendar3"></i>Posted ${new Date(j.posted + 'T12:00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })}</span>${j.salary ? `<span><i class="bi bi-currency-rupee"></i>${j.salary}</span>` : ''}`); const exp = j.experience === 'Fresher' ? 'badge-fresher' : j.experience === 'Internship' ? 'badge-intern' : 'badge-experienced'; $('#modalBadges').html(`<span class="badge-soft ${exp}">${j.experience}</span><span class="badge-soft role-label">${j.role}</span><span class="badge-soft role-label">${j.type}</span>`); $('#modalDescription').html(`<p>${j.description}</p><h3>What you’ll bring</h3><ul class="detail-list">${j.requirements.map(x => `<li>${x}</li>`).join('')}</ul><h3>About this listing</h3><p>Aggregated from ${j.source}. AI role classification confidence: ${j.confidence}%.</p>`); $('#modalSource').text(`Source: ${j.source} · Apply on the original employer website`); $('#modalApply').attr('href', j.url); bootstrap.Modal.getOrCreateInstance(document.getElementById('jobModal')).show(); });
            buildFilters(); render();
        })(jQuery);
    </script>
</body>

</html>