{{-- Click-to-play intro video: no player, iframe or video bytes load until the visitor presses play. --}}
@props(['video', 'name'])
<div {{ $attributes->merge(['class' => 'intro-video ratio ratio-16x9']) }} x-data="{ play: false }">
    <template x-if="play">
        @if ($video['type'] === 'file')
            <video src="{{ $video['src'] }}" controls autoplay playsinline preload="auto"></video>
        @else
            <iframe :src="'{{ $video['embed'] }}?autoplay=1&rel=0'" title="Introduction video from {{ $name }}" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
        @endif
    </template>
    <button type="button" x-show="!play" @click="play = true" class="intro-video-facade {{ $video['type'] === 'youtube' ? '' : 'is-portrait-thumb' }}" aria-label="Play introduction video from {{ $name }}">
        @if ($video['thumb'])<img src="{{ $video['thumb'] }}" alt="" loading="lazy" decoding="async">@endif
        <span class="play"><i class="ri-play-fill"></i></span>
        <span class="caption">
            <strong>Meet {{ $name }}</strong>
            <small>{{ $video['length'] ? $video['length'].' · ' : '' }}Introduction video</small>
        </span>
    </button>
</div>
