@if($jobs->isEmpty())
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-search"></i></div>
        <h3>No jobs found matching your filters</h3>
        <p>Try adjusting your search query, city or clear some filters to discover more open opportunities.</p>
        <button type="button" class="btn btn-primary btn-sm" id="emptyClear">Clear all filters</button>
    </div>
@else
    <div class="job-list">
        @foreach($jobs as $job)
            @include('partials.job-card', ['job' => $job])
        @endforeach
    </div>
@endif
