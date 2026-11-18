@foreach($experience as $item)
    <label class="custom-check-item">
        <input class="form-check-input filter-input" type="checkbox" data-filter="experience" value="{{ $item['value'] }}">
        <span class="custom-check-label">{{ $item['label'] }}</span>
    </label>
@endforeach
