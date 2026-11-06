@props(['icon' => 'inbox', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-icon"><x-icon :name="$icon" size="lg" /></div>
    <h3>{{ $title }}</h3>
    @if ($text)<p>{{ $text }}</p>@endif
    {{ $slot }}
</div>
