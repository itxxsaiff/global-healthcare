@props(['icon' => 'activity', 'tone' => 'teal', 'label', 'value', 'note' => null, 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['stat', 'card-hover text-decoration-none' => $href]) }}>
    <span class="icon-tile tone-{{ $tone }}"><x-icon :name="$icon" /></span>
    <span class="stat-body d-block">
        <span class="stat-value d-block">{{ $value }}</span>
        <span class="stat-label d-block">{{ $label }}</span>
        @if ($note)<span class="stat-note d-block">{{ $note }}</span>@endif
    </span>
</{{ $tag }}>
