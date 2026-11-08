{{--
    Icon-only action button for tables and cards (Remix Icon).
    <x-act icon="eye-line" label="View" :href="$url" />
    <x-act icon="forbid-line" label="Suspend" tone="danger" type="submit" />
--}}
@props(['icon', 'label', 'href' => null, 'tone' => 'view', 'type' => 'button'])
@php $classes = 'btn-act act-'.$tone; @endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} title="{{ $label }}" aria-label="{{ $label }}" data-bs-toggle="tooltip"><i class="ri-{{ $icon }}" aria-hidden="true"></i></a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} title="{{ $label }}" aria-label="{{ $label }}" data-bs-toggle="tooltip"><i class="ri-{{ $icon }}" aria-hidden="true"></i></button>
@endif
