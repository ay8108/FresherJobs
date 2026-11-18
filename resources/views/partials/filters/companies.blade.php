@forelse($companies as $company)
    <label class="custom-check-item">
        <input class="form-check-input filter-input" type="checkbox" data-filter="companies" value="{{ $company }}">
        <span class="custom-check-label">{{ $company }}</span>
    </label>
@empty
    <div class="subtle small py-1 px-2">No companies available</div>
@endforelse
