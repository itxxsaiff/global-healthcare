{{-- Re-posts the current query string as hidden inputs, minus the keys this form edits itself. --}}
@foreach (request()->except(array_merge($except ?? [], ['page'])) as $key => $value)
    @if (is_array($value))
        @foreach ($value as $v)@if (is_scalar($v))<input type="hidden" name="{{ $key }}[]" value="{{ $v }}">@endif @endforeach
    @elseif (is_scalar($value) && $value !== '')
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endif
@endforeach
