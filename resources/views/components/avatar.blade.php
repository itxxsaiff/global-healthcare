@props(['profile' => null, 'name' => null, 'src' => null, 'size' => null, 'round' => false])
@php
    $label = $name ?? $profile?->user?->name ?? '?';
    $src ??= $profile?->headshotUrl();
    $initials = collect(preg_split('/\s+/', trim(preg_replace('/^(Dr|Prof|Mr|Ms|Mrs)\.?\s+/i', '', $label))))
        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $tone = 'tone-'.(abs(crc32($label)) % 6);
@endphp
<span {{ $attributes->class(['avatar', $tone, 'avatar-'.$size => $size, 'avatar-round' => $round]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $label }}" loading="lazy" decoding="async" width="168" height="168">
    @else
        <span aria-hidden="true">{{ $initials }}</span>
        <span class="visually-hidden">{{ $label }}</span>
    @endif
</span>
