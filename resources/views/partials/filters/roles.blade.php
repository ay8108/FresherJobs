@forelse($roles as $role)
    <label class="custom-check-item role-item">
        <input class="form-check-input filter-input" type="checkbox" data-filter="roles" value="{{ $role }}">
        <span class="custom-check-label">{{ $role }}</span>
    </label>
@empty
    <div class="subtle small py-1 px-2">No roles available</div>
@endforelse
