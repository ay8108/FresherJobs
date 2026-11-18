<article class="job-card">
    <div class="d-flex gap-3">
        <div class="company-logo {{ $job->logo_class }}">{{ $job->company_initial }}</div>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between gap-2">
                <div>
                    <div class="job-title">{{ $job->title }}</div>
                    <div class="company-name">{{ $job->company_name ?: 'Employer' }}</div>
                </div>
            </div>
            <div class="job-meta">
                <span><i class="bi bi-geo-alt"></i>{{ $job->location ?: ($job->ai_city ?: 'Remote') }}</span>
                <span><i class="bi bi-calendar3"></i>{{ optional($job->posted_at ?: $job->created_at)->format('d M, Y') ?: 'Recently' }}</span>
                <span><i class="bi bi-globe2"></i>{{ ucfirst($job->source_name ?: 'Direct') }}</span>
            </div>
            <p class="job-description">
                {{ $job->description ?: "Verified opportunity at " . ($job->company_name ?: 'Employer') . " for " . $job->title . ". Click 'View details' to read more and apply." }}
            </p>
            <div class="job-footer">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge-soft {{ $job->experience_badge_class }}">{{ $job->experience_level ?: 'General' }}</span>
                    @if($job->ai_role)
                        <span class="badge-soft role-label">{{ $job->ai_role }}</span>
                    @endif
                    @if($job->ai_confidence && $job->ai_confidence > 0)
                        <span class="confidence"><i class="bi bi-stars"></i> {{ round($job->ai_confidence * 100) }}% AI match</span>
                    @endif
                </div>
                <div class="card-actions">
                    <button type="button" class="btn-outline-soft details-btn" data-id="{{ $job->id }}">View details</button>
                    <a class="btn btn-primary btn-apply" href="{{ $job->source_url ?: '#' }}" target="_blank" rel="noopener noreferrer">
                        Apply now <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</article>
