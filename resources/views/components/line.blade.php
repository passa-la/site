@props(['line', 'note' => null])

@php($note ??= $line.'.agency')

<li class="flex items-center gap-1">
    <x-ui.button variant="row" {{ $attributes->class('-ml-3 min-w-0 flex-1 gap-3') }}
        x-bind:aria-current="detail?.line.id === {{ $line }}.id"
        x-bind:class="detail?.line.id === {{ $line }}.id && 'bg-edge/70 ring-1 ring-fg/20'">
        <x-ui.code x-text="{{ $line }}.code" />
        <span class="min-w-0 flex-1">
            <span class="flex min-w-0 items-center gap-1.5">
                <span x-show="nick({{ $line }}.id)" class="shrink-0 rounded-md bg-sun/20 px-1.5 text-xs font-semibold" x-text="nick({{ $line }}.id)"></span>
                <span class="truncate font-medium" x-text="{{ $line }}.name"></span>
            </span>
            <span x-show="{{ $note }}" class="block truncate text-xs text-mute first-letter:uppercase" x-text="{{ $note }}"></span>
        </span>
        {{ $slot }}
    </x-ui.button>
    <x-ui.button variant="ghost" x-on:click="star({{ $line }})" x-bind:aria-pressed="!!fav({{ $line }}.id)"
        x-bind:aria-label="fav({{ $line }}.id) ? 'Tirar das favoritas' : 'Favoritar'" class="-mr-2 shrink-0 px-2">
        <x-icon name="star" class="size-5" x-bind:class="fav({{ $line }}.id) && 'fill-current text-sun'" />
    </x-ui.button>
</li>
