@extends('layouts.site')

@section('main')
    <main x-data="finder" class="mx-auto flex min-h-dvh max-w-7xl flex-col lg:grid lg:h-dvh lg:grid-cols-[minmax(360px,440px)_1fr] lg:grid-rows-[auto_1fr] lg:gap-x-6 lg:p-6">
        <header class="px-4 pt-6 pb-4 lg:col-start-1 lg:row-start-1 lg:px-0 lg:pt-2">
            <a href="/" x-on:click.prevent="reset()" class="inline-block text-3xl font-bold tracking-tight">passa lá<span class="text-sun">?</span></a>
            <p x-show="!place" class="mt-1 text-mute">Digita pra onde você vai e a gente mostra os ônibus que passam a até 1 km de lá.</p>

            <form x-on:submit.prevent="first()" role="search" class="relative mt-4">
                <label for="q" class="sr-only">Pra onde você vai?</label>
                <div class="flex items-center gap-2 rounded-2xl border border-edge bg-card pr-2 pl-4 shadow-sm focus-within:border-fg/50">
                    <x-icon name="search" class="size-5 shrink-0 text-mute" />
                    <input id="q" x-ref="q" x-model="q" x-on:input="type()" x-on:focus="menu = places.length > 0" x-on:keydown.escape="menu = false"
                        type="search" enterkeyhint="search" autocomplete="off" placeholder="Ex.: Shopping Patteo Olinda"
                        class="h-14 w-full min-w-0 bg-transparent text-lg outline-none placeholder:text-mute/70 [&::-webkit-search-cancel-button]:hidden">
                    <span x-show="busy === 'places'" x-cloak class="size-4 shrink-0 animate-spin rounded-full border-2 border-edge border-t-fg"></span>
                    <x-ui.button variant="ghost" x-show="q" x-cloak x-on:click="reset()" aria-label="Limpar busca" class="px-2">
                        <x-icon name="close" class="size-4" />
                    </x-ui.button>
                </div>

                <ul x-show="menu" x-cloak x-on:click.outside="menu = false" class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-edge bg-card p-1 shadow-lg">
                    <template x-for="p in places" x-bind:key="p.lat + ',' + p.lon">
                        <li>
                            <x-ui.button variant="row" x-on:click="pick(p)" class="gap-3">
                                <x-icon name="pin" class="size-5 shrink-0 text-mute" />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium" x-text="p.name"></span>
                                    <span class="block truncate text-sm text-mute" x-text="p.label"></span>
                                </span>
                            </x-ui.button>
                        </li>
                    </template>
                    <li x-show="!places.length" class="px-3 py-3 text-sm text-mute">Não achei esse lugar em Pernambuco. Tenta outro nome.</li>
                </ul>
            </form>

            <div x-show="!place" class="mt-4 flex flex-wrap items-center gap-2">
                <span class="text-sm text-mute">Experimenta:</span>
                @foreach (['Shopping Patteo Olinda', 'Marco Zero', 'UFPE', 'Shopping Recife'] as $s)
                    <x-ui.button variant="chip" x-on:click="sample({{ Js::from($s) }})">{{ $s }}</x-ui.button>
                @endforeach
            </div>

            <p x-show="fail" x-text="fail" x-cloak class="mt-3 rounded-xl bg-alert/10 px-3 py-2 text-sm text-alert"></p>
        </header>

        <section x-bind:class="place ? (detail ? 'h-[55dvh]' : 'h-56') : 'hidden'" class="relative lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:block lg:h-auto">
            <div x-ref="map" class="size-full overflow-hidden lg:rounded-3xl lg:border lg:border-edge"></div>
        </section>

        <section class="flex flex-1 flex-col px-4 lg:col-start-1 lg:row-start-2 lg:min-h-0 lg:overflow-y-auto lg:px-0">
            <template x-if="place && !detail">
                <div class="pb-6">
                    <div class="py-4">
                        <p class="truncate font-semibold" x-text="place.name"></p>
                        <p class="truncate text-sm text-mute" x-text="place.label"></p>
                    </div>

                    <p x-show="busy === 'lines'" class="text-sm text-mute">Procurando linhas…</p>
                    <p x-show="busy !== 'lines' && hits.length" class="mb-2 text-sm text-mute">
                        <strong class="text-fg" x-text="count()"></strong> a até 1 km daqui
                    </p>
                    <div x-show="busy !== 'lines' && !hits.length && !fail" class="rounded-2xl border border-dashed border-edge p-5 text-center text-sm text-mute">
                        Nenhum ônibus passa a até 1 km daqui. Por enquanto a gente só cobre a Região Metropolitana do Recife.
                    </div>

                    <ul class="divide-y divide-edge">
                        <template x-for="h in hits" x-bind:key="h.line.id">
                            <li>
                                <x-ui.button variant="row" x-on:click="open(h.line.id)" x-bind:disabled="busy === 'line'" class="gap-3">
                                    <x-ui.code x-text="h.line.code" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-medium" x-text="h.line.name"></span>
                                        <span class="block truncate text-xs text-mute first-letter:uppercase" x-text="h.line.agency"></span>
                                    </span>
                                    <span class="shrink-0 text-right">
                                        <span class="block text-sm font-semibold tabular-nums" x-text="meters(h.meters)"></span>
                                        <span class="block text-xs text-mute" x-text="walk(h.meters)"></span>
                                    </span>
                                </x-ui.button>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <template x-if="detail">
                <div class="py-4">
                    <x-ui.button variant="ghost" x-on:click="close()" class="-ml-3">
                        <x-icon name="back" class="size-4" /> Voltar pra lista
                    </x-ui.button>

                    <div class="mt-3 flex items-start gap-3">
                        <x-ui.code x-text="detail.line.code" class="text-lg" />
                        <div class="min-w-0">
                            <h2 class="text-xl leading-tight font-semibold" x-text="detail.line.name"></h2>
                            <p class="text-sm text-mute first-letter:uppercase" x-text="detail.line.agency"></p>
                        </div>
                    </div>

                    <div x-show="detail.near" class="mt-4 rounded-2xl border border-edge bg-card p-4">
                        <p class="text-xs font-medium tracking-wide text-mute uppercase">Onde pegar</p>
                        <p class="mt-1 font-semibold" x-text="detail.near && stop(detail.near.stop)"></p>
                        <p class="text-sm text-mute" x-text="detail.near && `${meters(detail.near.meters)} de ${place.name} · ${walk(detail.near.meters)}`"></p>
                    </div>

                    <ul class="mt-4 grid grid-cols-2 gap-2 text-sm text-mute">
                        <li class="flex items-center gap-2"><span class="h-1.5 w-6 rounded-full bg-sun ring-2 ring-ink"></span> Trajeto da linha</li>
                        <li class="flex items-center gap-2"><span class="size-3.5 rounded-full border-[3px] border-sun bg-ink"></span> Onde pegar</li>
                        <li class="flex items-center gap-2"><span class="size-3.5 rounded-full border border-dashed border-ink/60 bg-sun/20"></span> Raio de 1 km</li>
                        <li class="flex items-center gap-2"><span class="size-2.5 rounded-full border-[1.5px] border-ink bg-white"></span> <span x-text="`${detail.stops.length} paradas`"></span></li>
                    </ul>
                </div>
            </template>

            <footer class="mt-auto border-t border-edge py-4 text-xs text-mute">
                Linhas: <a href="https://www.granderecife.pe.gov.br" class="underline hover:text-fg">Grande Recife</a> (GTFS, atualizado toda semana) ·
                Mapa: <a href="https://openfreemap.org" class="underline hover:text-fg">OpenFreeMap</a> © <a href="https://www.openstreetmap.org/copyright" class="underline hover:text-fg">OpenStreetMap</a> ·
                <a href="https://github.com/passa-la/site" class="underline hover:text-fg">Código aberto</a>
            </footer>
        </section>
    </main>
@endsection
