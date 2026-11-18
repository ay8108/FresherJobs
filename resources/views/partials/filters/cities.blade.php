@forelse($cities as $city)
    <label class="custom-check-item city-item">
        <input class="form-check-input filter-input" type="checkbox" data-filter="cities" value="{{ $city }}">
        <span class="custom-check-label">{{ $city }}</span>
    </label>
@empty
    <div class="subtle small py-1 px-2">No cities available</div>
@endforelse
