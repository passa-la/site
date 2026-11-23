@props(['variant' => 'plain'])

@php
    $tone = [
        'plain' => 'rounded-full bg-sun px-4 py-2 font-semibold text-sun-ink hover:brightness-95',
        'ghost' => 'rounded-full px-3 py-1.5 text-mute hover:bg-edge/60 hover:text-fg',
        'chip' => 'rounded-full border border-edge bg-card px-3 py-1.5 text-sm hover:border-fg/40',
        'row' => 'w-full rounded-2xl px-3 py-3 text-left hover:bg-edge/50',
    ][$variant];
@endphp

<button type="button" {{ $attributes->class(['inline-flex cursor-pointer items-center gap-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sun disabled:opacity-50', $tone]) }}>{{ $slot }}</button>
