/**
 * FresherJobs - Portal Application Logic
 * Pure client-side controller interacting with Laravel REST API endpoints:
 * Server renders all Blade HTML partials; JavaScript only swaps pre-rendered HTML into containers.
 * - GET /api/jobs (returns rendered job-list & pagination partials)
 * - GET /api/jobs/filters (returns rendered filter options partials)
 * - GET /api/jobs/{id} (returns rendered job-modal partial)
 */

(function ($) {
    'use strict';

    // State management
    const state = {
        page: 1,
        perPage: 9,
        expandedRoles: false,
        expandedCities: false,
        activeRequest: null
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // =========================================================================
    // API: Load Filter Metadata & Options
    // =========================================================================
    function loadFilterMetadata() {
        $.ajax({
            url: '/api/jobs/filters',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.success) {
                    if (res.experience_html && !$('#experienceFilters').children().length) {
                        $('#experienceFilters').html(res.experience_html);
                    }
                    if (res.roles_html && !$('#roleFilters').children().length) {
                        $('#roleFilters').html(res.roles_html);
                    }
                    if (res.cities_html && !$('#locationFilters').children().length) {
                        $('#locationFilters').html(res.cities_html);
                    }
                    if (res.companies_html && !$('#companyFilters').children().length) {
                        $('#companyFilters').html(res.companies_html);
                    }
                    if (res.stats) {
                        renderStats(res.stats);
                    }
                    applySidebarSlice();
                }
            },
            error: function (xhr, status, error) {
                console.warn('Could not load filter metadata:', error);
            }
        });
    }

    function renderStats(stats) {
        if (stats.total !== undefined) $('#statTotal').text(stats.total.toLocaleString());
        if (stats.classified !== undefined) $('#statClassified').text(stats.classified.toLocaleString());
        if (stats.internships !== undefined) $('#statInternships').text(stats.internships.toLocaleString());
        if (stats.remote !== undefined) $('#statRemote').text(stats.remote.toLocaleString());
        if (stats.companies_count !== undefined) $('#statCompanies').text(stats.companies_count.toLocaleString());
    }

    function applySidebarSlice() {
        // Roles visibility
        const $roles = $('#roleFilters .role-item');
        const roleSearch = ($('#roleFilterSearch').val() || '').trim().toLowerCase();

        let visibleRoleCount = 0;
        $roles.each(function (index) {
            const matches = !roleSearch || $(this).text().toLowerCase().includes(roleSearch);
            if (matches) {
                visibleRoleCount++;
                if (!state.expandedRoles && !roleSearch && visibleRoleCount > 5) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            } else {
                $(this).hide();
            }
        });

        if (visibleRoleCount > 5 && !roleSearch) {
            $('#moreRoles').show().text(state.expandedRoles ? 'Show fewer' : `+ Show ${visibleRoleCount - 5} more`);
        } else {
            $('#moreRoles').hide();
        }

        // Cities visibility
        const $cities = $('#locationFilters .city-item');
        const citySearch = ($('#locationFilterSearch').val() || '').trim().toLowerCase();

        let visibleCityCount = 0;
        $cities.each(function (index) {
            const matches = !citySearch || $(this).text().toLowerCase().includes(citySearch);
            if (matches) {
                visibleCityCount++;
                if (!state.expandedCities && !citySearch && visibleCityCount > 5) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            } else {
                $(this).show();
            }
        });

        if (visibleCityCount > 5 && !citySearch) {
            $('#moreLocations').show().text(state.expandedCities ? 'Show fewer' : `+ Show ${visibleCityCount - 5} more`);
        } else {
            $('#moreLocations').hide();
        }
    }

    // =========================================================================
    // API: Load & Render Jobs (Pre-rendered by Blade server-side)
    // =========================================================================
    function getSelectedValues(filterName) {
        return $(`.filter-input[data-filter="${filterName}"]:checked`).map(function () {
            return this.value;
        }).get();
    }

    function buildQueryParams() {
        const params = {
            page: state.page,
            per_page: state.perPage,
            sort: $('#sortSelect').val() || 'latest'
        };

        const keyword = ($('#keyword').val() || '').trim();
        if (keyword) params.search = keyword;

        const location = ($('#locationSearch').val() || '').trim();
        if (location) params.location = location;

        const experience = getSelectedValues('experience');
        if (experience.length) params.experience = experience;

        const roles = getSelectedValues('roles');
        if (roles.length) params.roles = roles;

        const cities = getSelectedValues('cities');
        if (cities.length) params.cities = cities;

        const companies = getSelectedValues('companies');
        if (companies.length) params.companies = companies;

        if ($('#filterRemote').is(':checked')) {
            params.remote = 1;
        }

        return params;
    }

    function fetchJobs() {
        if (state.activeRequest) {
            state.activeRequest.abort();
        }

        // Subtle loading indicator
        $('#resultSummary').text('Updating results...');
        $('#jobResults').css('opacity', '0.55');

        const params = buildQueryParams();
        updateActiveChips();

        state.activeRequest = $.ajax({
            url: '/api/jobs',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function (res) {
                state.activeRequest = null;
                $('#jobResults').css('opacity', '1');

                if (res && res.success) {
                    $('#jobResults').html(res.html);
                    $('#paginationWrap').html(res.pagination_html || '');
                    $('#resultSummary').text(res.summary);
                }
            },
            error: function (xhr, status, error) {
                if (status === 'abort') return;
                state.activeRequest = null;
                $('#jobResults').css('opacity', '1');
                console.error('Failed to load jobs:', error);
                $('#resultSummary').text('Error updating results');
            }
        });
    }

    // =========================================================================
    // Active Chips
    // =========================================================================
    function updateActiveChips() {
        const chips = [];

        // Keyword chip
        const kw = ($('#keyword').val() || '').trim();
        if (kw) {
            chips.push({ type: 'keyword', label: `Keyword: "${kw}"` });
        }

        // Location chip
        const loc = ($('#locationSearch').val() || '').trim();
        if (loc) {
            chips.push({ type: 'locationSearch', label: `Location: "${loc}"` });
        }

        // Checkbox filters
        ['experience', 'roles', 'cities', 'companies'].forEach(function (f) {
            getSelectedValues(f).forEach(function (val) {
                chips.push({ type: f, val: val, label: val });
            });
        });

        // Remote toggle
        if ($('#filterRemote').is(':checked')) {
            chips.push({ type: 'toggle', id: 'filterRemote', label: 'Remote only' });
        }

        const html = chips.map(function (c) {
            let dataAttr = '';
            if (c.type === 'keyword') dataAttr = 'data-chip-type="keyword"';
            else if (c.type === 'locationSearch') dataAttr = 'data-chip-type="locationSearch"';
            else if (c.type === 'toggle') dataAttr = `data-chip-type="toggle" data-target-id="${c.id}"`;
            else dataAttr = `data-chip-type="filter" data-filter="${c.type}" data-value="${escapeHtml(c.val)}"`;

            return `
                <span class="filter-chip">
                    ${escapeHtml(c.label)}
                    <button type="button" aria-label="Remove filter" ${dataAttr}><i class="bi bi-x"></i></button>
                </span>
            `;
        }).join('');

        $('#activeChips').html(html);
    }

    function clearAllFilters() {
        $('.filter-input').prop('checked', false);
        $('#keyword, #locationSearch, #roleFilterSearch, #locationFilterSearch').val('');
        $('#filterRemote').prop('checked', false);
        $('#sortSelect').val('latest');
        state.page = 1;
        applySidebarSlice();
        fetchJobs();
    }

    // =========================================================================
    // Modal Job Details (Server-rendered Blade Partial)
    // =========================================================================
    function openJobModal(jobId) {
        $.ajax({
            url: `/api/jobs/${jobId}`,
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.success && res.html) {
                    $('#jobModalContainer').html(res.html);
                    const modalEl = document.getElementById('jobModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            },
            error: function (xhr, status, error) {
                console.error('Failed to load job details modal:', error);
            }
        });
    }

    // =========================================================================
    // Event Listeners
    // =========================================================================
    $(document).ready(function () {
        // Initial setup
        applySidebarSlice();
        loadFilterMetadata();

        // Search form submit
        $('#searchForm').on('submit', function (e) {
            e.preventDefault();
            state.page = 1;
            fetchJobs();
            document.querySelector('#jobs').scrollIntoView({ behavior: 'smooth' });
        });

        // Filter checkbox / sort change
        $(document).on('change', '.filter-input, #filterRemote, #sortSelect', function () {
            state.page = 1;
            fetchJobs();
        });

        // Clear filters
        $('#clearFilters').on('click', clearAllFilters);
        $(document).on('click', '#emptyClear', clearAllFilters);

        // Remove active chip
        $(document).on('click', '.filter-chip button', function () {
            const chipType = $(this).data('chip-type');
            if (chipType === 'keyword') {
                $('#keyword').val('');
            } else if (chipType === 'locationSearch') {
                $('#locationSearch').val('');
            } else if (chipType === 'toggle') {
                const targetId = $(this).data('target-id');
                $(`#${targetId}`).prop('checked', false);
            } else if (chipType === 'filter') {
                const filter = $(this).data('filter');
                const val = $(this).data('value');
                $(`.filter-input[data-filter="${filter}"][value="${val}"]`).prop('checked', false);
            }
            state.page = 1;
            fetchJobs();
        });

        // Pagination click
        $(document).on('click', '.page-link[data-page]', function () {
            const p = Number($(this).data('page'));
            if (!isNaN(p) && p > 0 && p !== state.page) {
                state.page = p;
                fetchJobs();
                document.querySelector('#jobs').scrollIntoView({ behavior: 'smooth' });
            }
        });

        // View details modal
        $(document).on('click', '.details-btn', function () {
            const jobId = Number($(this).data('id'));
            openJobModal(jobId);
        });

        // Show more / fewer roles
        $('#moreRoles').on('click', function () {
            state.expandedRoles = !state.expandedRoles;
            applySidebarSlice();
        });

        // Search within roles
        $('#roleFilterSearch').on('input', function () {
            applySidebarSlice();
        });

        // Show more / fewer locations
        $('#moreLocations').on('click', function () {
            state.expandedCities = !state.expandedCities;
            applySidebarSlice();
        });

        // Search within locations
        $('#locationFilterSearch').on('input', function () {
            applySidebarSlice();
        });

        // Grid / List view toggle
        $('.view-toggle button').on('click', function () {
            $('.view-toggle button').removeClass('active');
            $(this).addClass('active');
            const isList = $(this).data('view') === 'list';
            $('#jobResults').toggleClass('list-view', isList);
        });

        // Mobile filters toggle
        $('#mobileFilters, #mobileFiltersTop').on('click', function () {
            const open = $('#filterPanel').toggleClass('open').hasClass('open');
            $('#mobileFilters').attr('aria-expanded', open);
        });
    });

})(jQuery);
