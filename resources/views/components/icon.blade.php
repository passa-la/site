@props(['name'])

@php
    $paths = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'back' => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
        'close' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'walk' => '<circle cx="13" cy="4.5" r="2"/><path d="m9 21 2.5-6.5L14 17v4"/><path d="M7.5 11.5 10 8h4l2.5 4 2 1"/><path d="m11.5 14.5.8-4.5"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9z"/>',
        'bus' => '<rect x="4" y="3" width="16" height="14" rx="3"/><path d="M4 11h16"/><path d="M7 21v-4"/><path d="M17 21v-4"/>',
    ];
@endphp

<svg {{ $attributes->merge(['aria-hidden' => 'true']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $paths[$name] !!}</svg>
