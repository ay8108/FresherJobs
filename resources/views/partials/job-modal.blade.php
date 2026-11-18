<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
        <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
                <div class="company-logo {{ $job->logo_class }}">{{ $job->company_initial }}</div>
                <div>
                    <h2 class="modal-title fs-5 fw-bold" id="jobModalTitle">{{ $job->title }}</h2>
                    <div class="company-name">{{ $job->company_name ?: 'Employer' }}</div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="job-meta mb-3">
                <span><i class="bi bi-geo-alt"></i>{{ $job->location ?: ($job->ai_city ?: 'Remote') }}</span>
                <span><i class="bi bi-calendar3"></i>Posted {{ optional($job->posted_at ?: $job->created_at)->format('d M, Y') ?: 'Recently' }}</span>
                <span><i class="bi bi-globe2"></i>Source: {{ ucfirst($job->source_name ?: 'Direct') }}</span>
            </div>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <span class="badge-soft {{ $job->experience_badge_class }}">{{ $job->experience_level ?: 'General' }}</span>
                @if($job->ai_role)
                    <span class="badge-soft role-label">{{ $job->ai_role }}</span>
                @endif
                @if($job->ai_confidence && $job->ai_confidence > 0)
                    <span class="confidence"><i class="bi bi-stars"></i> {{ round($job->ai_confidence * 100) }}% AI match</span>
                @endif
            </div>
            <div class="detail-section">
                @if($job->description)
                    <p>{{ $job->description }}</p>
                @else
                    <p>This job was fetched directly from the {{ $job->company_name ?: 'Employer' }} career board. Full specifications and application instructions are available on the employer's official website.</p>
                @endif
                <h3>About This Opportunity</h3>
                <p>Listing synchronized from {{ ucfirst($job->source_name ?: 'direct career feed') }}. AI role classification: {{ $job->ai_role ?: 'General' }}.</p>
            </div>
        </div>
        <div class="modal-footer justify-content-between">
            <span class="source">Source: {{ ucfirst($job->source_name ?: 'Careers') }} · Official application on employer portal</span>
            <a class="btn btn-primary btn-apply" href="{{ $job->source_url ?: '#' }}" target="_blank" rel="noopener noreferrer">
                Apply on employer site <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
