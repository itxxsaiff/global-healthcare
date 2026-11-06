@props(['status'])
<span {{ $attributes->merge(['class' => 'status tone-'.$status->tone()]) }}>{{ $status->label() }}</span>
