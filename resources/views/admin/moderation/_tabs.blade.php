<div class="d-flex gap-2 mb-3">
    @foreach ($options as $value => $label)
        <a href="{{ request()->url() }}?status={{ $value }}" class="chip {{ $current === $value ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>
