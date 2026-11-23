<div role="group" aria-label="Distância" {{ $attributes->class('flex items-center gap-2') }}>
    <span class="text-sm text-mute">Até</span>
    <template x-for="r in radii" x-bind:key="r">
        <x-ui.button variant="chip" x-on:click="resize(r)" x-bind:aria-pressed="radius === r" class="shrink-0 whitespace-nowrap"
            x-bind:class="radius === r && 'border-fg bg-fg text-paper hover:border-fg'">
            <span x-text="meters(r)"></span>
        </x-ui.button>
    </template>
</div>
