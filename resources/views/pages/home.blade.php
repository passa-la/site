@extends('layouts.site')

@section('main')
    <main x-data="finder({{ Js::from($today) }})" class="mx-auto flex min-h-dvh max-w-7xl flex-col lg:mx-0 lg:grid lg:h-dvh lg:max-w-none lg:grid-cols-[420px_1fr] lg:grid-rows-[auto_1fr]">
        <header class="px-4 pt-6 pb-4 lg:col-start-1 lg:row-start-1 lg:px-6">
            <a href="/" x-on:click.prevent="clear()" class="inline-block text-3xl font-bold tracking-tight">passa lá<span class="text-sun">?</span></a>
            <p x-show="!place && !detail" class="mt-1 text-mute">Digita pra onde você vai e a gente mostra os ônibus que passam a até 1 km de lá.</p>

            <div role="group" aria-label="Buscar por" class="mt-4 flex w-fit rounded-full border border-edge bg-card p-1 text-sm">
                <x-ui.button variant="ghost" x-on:click="swap('place')" x-bind:aria-pressed="mode === 'place'" x-bind:class="mode === 'place' && 'bg-fg text-paper hover:bg-fg hover:text-paper'">
                    <x-icon name="pin" class="size-4" /> Lugar
                </x-ui.button>
                <x-ui.button variant="ghost" x-on:click="swap('line')" x-bind:aria-pressed="mode === 'line'" x-bind:class="mode === 'line' && 'bg-fg text-paper hover:bg-fg hover:text-paper'">
                    <x-icon name="bus" class="size-4" /> Linha
                </x-ui.button>
            </div>

            <form x-on:submit.prevent="first()" role="search" class="relative mt-3">
                <label for="q" class="sr-only" x-text="mode === 'line' ? 'Qual linha?' : 'Pra onde você vai?'">Pra onde você vai?</label>
                <div class="flex items-center gap-2 rounded-2xl border border-edge bg-card pr-2 pl-4 shadow-sm focus-within:border-fg/50">
                    <x-icon name="search" class="size-5 shrink-0 text-mute" />
                    <input id="q" x-ref="q" x-model="q" x-on:input="type()" x-on:focus="menu = places.length > 0" x-on:keydown.escape="menu = false"
                        type="search" enterkeyhint="search" autocomplete="off" x-bind:placeholder="hint()" placeholder="Ex.: Shopping Patteo Olinda"
                        class="h-14 w-full min-w-0 bg-transparent text-lg outline-none placeholder:text-mute/70 [&::-webkit-search-cancel-button]:hidden">
                    <span x-show="busy === 'search'" x-cloak class="size-4 shrink-0 animate-spin rounded-full border-2 border-edge border-t-fg"></span>
                    <x-ui.button variant="ghost" x-show="q" x-cloak x-on:click="clear()" aria-label="Limpar busca" class="px-2">
                        <x-icon name="close" class="size-4" />
                    </x-ui.button>
                </div>

                <ul x-show="menu" x-cloak x-on:click.outside="menu = false" class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-edge bg-card p-1 shadow-lg">
                    <template x-for="l in mode === 'line' ? found : []" x-bind:key="'l' + l.id">
                        <li>
                            <x-ui.button variant="row" x-on:click="line(l)" class="gap-3">
                                <x-ui.code x-text="l.code" />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium" x-text="l.name"></span>
                                    <span class="block truncate text-xs text-mute first-letter:uppercase" x-text="l.agency"></span>
                                </span>
                            </x-ui.button>
                        </li>
                    </template>
                    <template x-for="p in mode === 'place' ? places : []" x-bind:key="p.lat + ',' + p.lon">
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
                    <li x-show="mode === 'place' && !places.length" class="px-3 py-3 text-sm text-mute">
                        Não achei esse lugar em Pernambuco. Tenta outro nome ou
                        <button type="button" x-on:click="target()" class="cursor-pointer font-medium text-fg underline">marca no mapa</button>.
                    </li>
                    <li x-show="mode === 'line' && !found.length" class="px-3 py-3 text-sm text-mute">Nenhuma linha com esse número ou nome.</li>
                </ul>
            </form>

            <x-ui.button variant="ghost" x-show="mode === 'place' && !place && !aim && !detail" x-on:click="target()" class="mt-2 -ml-3 text-sm">
                <x-icon name="pin" class="size-4" /> Escolher no mapa
            </x-ui.button>

            <div x-show="(geo === 'ask' || geo === 'busy') && !detail" x-cloak class="mt-3 flex items-start gap-3 rounded-2xl border border-edge bg-card p-4">
                <x-icon name="walk" class="mt-0.5 size-5 shrink-0 text-mute" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm">Quer usar sua localização? A gente mostra a parada mais perto de você e o caminho a pé até ela.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-ui.button x-on:click="locate()" x-bind:disabled="geo === 'busy'" class="text-sm">
                            <span x-text="geo === 'busy' ? 'Localizando…' : 'Usar minha localização'"></span>
                        </x-ui.button>
                        <x-ui.button variant="ghost" x-on:click="skip()" class="text-sm">Agora não</x-ui.button>
                    </div>
                </div>
            </div>

            <div x-show="!place && !detail && recents.length" x-cloak class="mt-5">
                <p class="text-sm font-medium text-mute">Ônibus recentes</p>
                <ul class="mt-1 divide-y divide-edge">
                    <template x-for="r in recents" x-bind:key="r.line.id + (r.place?.name ?? '')">
                        <li>
                            <x-ui.button variant="row" x-on:click="again(r)" class="gap-3 px-0">
                                <x-ui.code x-text="r.line.code" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium" x-text="r.line.name"></span>
                                    <span class="block truncate text-xs text-mute" x-text="r.place ? 'perto de ' + r.place.name : 'busca por linha'"></span>
                                </span>
                            </x-ui.button>
                        </li>
                    </template>
                </ul>
            </div>

            <template x-if="place || detail">
                <div role="group" aria-label="Dia da semana" class="mt-4 flex flex-wrap gap-2">
                    <template x-for="(v, k) in days" x-bind:key="k">
                        <x-ui.button variant="chip" x-on:click="pickDay(k)" x-bind:aria-pressed="day === k"
                            x-bind:class="{ 'border-fg bg-fg text-paper hover:border-fg': day === k, 'line-through opacity-50': !runs(k) }">
                            <span x-text="v.name"></span><span x-show="k === today" class="text-xs opacity-70">hoje</span>
                        </x-ui.button>
                    </template>
                </div>
            </template>

            <p x-show="fail" x-text="fail" x-cloak class="mt-3 rounded-xl bg-alert/10 px-3 py-2 text-sm text-alert"></p>
        </header>

        <section x-bind:class="detail ? 'h-[55dvh]' : aim ? 'h-[60dvh]' : place ? 'h-56' : 'hidden'" class="relative lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:block lg:h-auto lg:py-3 lg:pr-3">
            <div x-ref="map" class="size-full overflow-hidden lg:rounded-3xl lg:border lg:border-edge"></div>
            <div x-show="aim" x-cloak class="pointer-events-none absolute inset-x-0 bottom-10 flex justify-center px-3">
                <div class="pointer-events-auto flex items-center gap-2 rounded-full bg-ink py-1.5 pr-1.5 pl-4 text-sm text-white shadow-lg">
                    Toque no mapa pra marcar o destino
                    <button type="button" x-on:click="aim = false" class="cursor-pointer rounded-full bg-white/15 px-3 py-1 hover:bg-white/25">Cancelar</button>
                </div>
            </div>
        </section>

        <aside x-show="detail" x-cloak aria-label="Linha selecionada"
            class="px-4 lg:z-10 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:my-7 lg:mr-7 lg:max-h-[calc(100%-3.5rem)] lg:w-[360px] lg:self-start lg:justify-self-end lg:overflow-y-auto lg:rounded-3xl lg:border lg:border-edge lg:bg-paper lg:px-5 lg:shadow-2xl">
                <template x-if="detail">
                    <div class="space-y-4 py-4 lg:py-5">
                        <div class="flex items-start gap-3">
                            <x-ui.code x-text="detail.line.code" class="text-lg" />
                            <div class="min-w-0 flex-1">
                                <h2 class="text-xl leading-tight font-semibold" x-text="detail.line.name"></h2>
                                <p class="text-sm text-mute first-letter:uppercase" x-text="detail.line.agency"></p>
                            </div>
                            <x-ui.button variant="ghost" x-on:click="close()" aria-label="Fechar linha" class="-mt-1 -mr-2 px-2">
                                <x-icon name="close" class="size-5" />
                            </x-ui.button>
                        </div>

                        <p x-show="!detail.runs" class="rounded-2xl border border-dashed border-edge p-4 text-sm text-mute">
                            Essa linha não roda <span x-text="days[day].when"></span>. Escolhe outro dia.
                        </p>

                        <div x-show="detail.ways.length > 1" role="group" aria-label="Sentido" class="flex gap-2">
                            <template x-for="w in detail.ways" x-bind:key="w.dir">
                                <x-ui.button variant="chip" x-on:click="show(w.dir)" x-bind:aria-pressed="focus === w.dir"
                                    x-bind:class="focus === w.dir && 'border-fg bg-fg text-paper hover:border-fg'">
                                    <span class="h-1.5 w-5 rounded-full" x-bind:class="w.dir === 1 ? 'bg-back' : 'bg-sun'"></span>
                                    <span x-text="label(w.dir)"></span>
                                </x-ui.button>
                            </template>
                            <x-ui.button variant="chip" x-on:click="show(null)" x-bind:aria-pressed="focus === null"
                                x-bind:class="focus === null && 'border-fg bg-fg text-paper hover:border-fg'">Ambos</x-ui.button>
                        </div>

                        <div x-show="detail.runs && !me && geo !== 'busy'" class="flex items-center justify-between gap-3 rounded-2xl border border-edge bg-card p-4 text-sm">
                            <span x-text="geo === 'denied' ? 'Sua localização está bloqueada no navegador. Libera nas configurações pra ver onde pegar.' : 'Liga sua localização pra ver onde pegar e o caminho a pé.'"></span>
                            <x-ui.button x-show="geo !== 'denied'" x-on:click="locate()" class="shrink-0 text-sm">Usar</x-ui.button>
                        </div>

                        <template x-if="way()?.board">
                            <div class="rounded-2xl border border-edge bg-card p-4">
                                <p class="text-xs font-medium tracking-wide text-mute uppercase">Onde pegar · <span x-text="label(way().dir)"></span></p>
                                <p class="mt-1 font-semibold" x-text="stop(way().board.stop)"></p>
                                <p class="text-sm text-mute" x-text="trip()"></p>
                                <p x-show="detail.ways.length > 1 && way().drop && !way().fits" class="mt-2 rounded-xl bg-alert/10 px-3 py-2 text-sm text-alert">
                                    Nesse sentido o ônibus passa pelo destino antes da sua parada. Dá uma olhada no outro sentido.
                                </p>
                                <p class="mt-2 text-xs text-mute">Toca em outra parada no mapa pra trocar.</p>
                            </div>
                        </template>

                        <template x-for="w in detail.ways.filter((w) => w.drop && (focus === null || w.dir === focus))" x-bind:key="'drop' + w.dir">
                            <div class="rounded-2xl border border-edge bg-card p-4">
                                <p class="text-xs font-medium tracking-wide text-mute uppercase">Onde descer · <span x-text="label(w.dir)"></span></p>
                                <p class="mt-1 font-semibold" x-text="stop(w.drop.stop)"></p>
                                <p class="text-sm text-mute" x-text="`${meters(w.drop.meters)} de ${place.name} · ${walking(w.drop.meters)}`"></p>
                            </div>
                        </template>

                        <ul class="grid grid-cols-2 gap-x-3 gap-y-2 rounded-2xl bg-white p-4 text-sm text-ink">
                            <li class="flex items-center gap-2"><span class="h-1.5 w-6 rounded-full bg-sun ring-2 ring-ink"></span> Ida</li>
                            <li class="flex items-center gap-2"><span class="h-1.5 w-6 rounded-full bg-back ring-2 ring-ink"></span> Volta</li>
                            <li class="flex items-center gap-2"><span class="size-3.5 rounded-full border-[3px] border-ink bg-white"></span> Onde pegar</li>
                            <li class="flex items-center gap-2"><span class="size-3.5 rounded-full border-[3px] border-sun bg-ink"></span> Onde descer</li>
                            <li class="flex items-center gap-2"><span class="grid size-4 place-items-center rounded-full bg-ink"><span class="size-1.5 rounded-full bg-white"></span></span> Você</li>
                            <li class="flex items-center gap-2"><span class="w-6 border-t-[3px] border-dotted border-ink"></span> Caminho a pé</li>
                        </ul>
                    </div>
                </template>
        </aside>

        <section class="flex flex-1 flex-col px-4 lg:col-start-1 lg:row-start-2 lg:min-h-0 lg:overflow-y-auto lg:px-6">
            <template x-if="place">
                <div class="pb-6">
                    <div role="group" aria-label="Distância" class="flex items-center gap-2 pt-2">
                        <span class="text-sm text-mute">Até</span>
                        <template x-for="r in radii" x-bind:key="r">
                            <x-ui.button variant="chip" x-on:click="resize(r)" x-bind:aria-pressed="radius === r"
                                x-bind:class="radius === r && 'border-fg bg-fg text-paper hover:border-fg'">
                                <span x-text="meters(r)"></span>
                            </x-ui.button>
                        </template>
                    </div>

                    <div class="py-4">
                        <p class="truncate font-semibold" x-text="place.name"></p>
                        <p class="truncate text-sm text-mute" x-text="place.label"></p>
                        <p class="mt-1 text-xs text-mute">Toque no mapa pra mudar o ponto.</p>
                    </div>

                    <p x-show="busy === 'lines'" class="text-sm text-mute">Procurando linhas…</p>
                    <p x-show="busy !== 'lines' && hits.length && !wide" class="mb-2 text-sm text-mute">
                        <strong class="text-fg" x-text="count()"></strong> a até <span x-text="meters(radius)"></span> daqui <span x-text="days[day].when"></span>
                    </p>
                    <p x-show="busy !== 'lines' && hits.length && wide" class="mb-2 rounded-2xl border border-dashed border-edge p-4 text-sm text-mute">
                        Nenhum ônibus passa a até <span x-text="meters(radius)"></span> daqui <span x-text="days[day].when"></span>. Os mais perto:
                    </p>
                    <div x-show="busy !== 'lines' && !hits.length && !fail" class="rounded-2xl border border-dashed border-edge p-5 text-center text-sm text-mute">
                        Nenhum ônibus a até 5 km daqui <span x-text="days[day].when"></span>. Por enquanto a gente só cobre a Região Metropolitana do Recife.
                    </div>

                    <ul class="divide-y divide-edge">
                        <template x-for="h in hits" x-bind:key="h.line.id">
                            <li>
                                <x-ui.button variant="row" x-on:click="open(h.line.id)" x-bind:disabled="busy === 'line'" x-bind:aria-current="detail?.line.id === h.line.id"
                                    x-bind:class="detail?.line.id === h.line.id && 'bg-edge/70 ring-1 ring-fg/20'" class="-mx-3 w-[calc(100%+1.5rem)] gap-3">
                                    <x-ui.code x-text="h.line.code" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-medium" x-text="h.line.name"></span>
                                        <span class="block truncate text-xs text-mute first-letter:uppercase" x-text="h.line.agency"></span>
                                    </span>
                                    <span class="shrink-0 text-right">
                                        <span class="block text-sm font-semibold tabular-nums" x-text="meters(h.meters)"></span>
                                        <span class="block text-xs text-mute" x-text="walking(h.meters)"></span>
                                    </span>
                                </x-ui.button>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <footer class="mt-auto border-t border-edge py-4 text-xs text-mute">
                Linhas: <a href="https://www.granderecife.pe.gov.br" class="underline hover:text-fg">Grande Recife</a> (GTFS, atualizado toda semana) ·
                Mapa: <a href="https://openfreemap.org" class="underline hover:text-fg">OpenFreeMap</a> © <a href="https://www.openstreetmap.org/copyright" class="underline hover:text-fg">OpenStreetMap</a> ·
                Caminho a pé: <a href="https://routing.openstreetmap.de" class="underline hover:text-fg">FOSSGIS</a> ·
                <a href="https://github.com/passa-la/site" class="underline hover:text-fg">Código aberto</a>
            </footer>
        </section>
    </main>
@endsection
