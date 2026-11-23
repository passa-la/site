@extends('layouts.site')

@section('main')
    <main x-data="finder({{ Js::from($today) }})" class="mx-auto flex min-h-dvh max-w-7xl flex-col lg:mx-0 lg:grid lg:h-dvh lg:max-w-none lg:grid-cols-[420px_1fr] lg:grid-rows-[auto_1fr]">
        <header class="px-4 pt-6 pb-4 lg:col-start-1 lg:row-start-1 lg:px-6">
            <a href="/" x-on:click.prevent="clear()" class="inline-block text-3xl font-bold tracking-tight">passa lá<span class="text-sun">?</span></a>
            <p x-show="!place && !detail && mode !== 'ride'" class="mt-1 text-mute">Digita pra onde você vai e a gente mostra os ônibus que passam a até 1 km de lá.</p>

            <div role="group" aria-label="Buscar por" class="mt-4 flex w-fit rounded-full border border-edge bg-card p-1 text-sm">
                <x-ui.button variant="ghost" x-on:click="swap('place')" x-bind:aria-pressed="mode === 'place'" x-bind:class="mode === 'place' && 'bg-fg text-paper hover:bg-fg hover:text-paper'">
                    <x-icon name="pin" class="size-4" /> Lugar
                </x-ui.button>
                <x-ui.button variant="ghost" x-on:click="swap('line')" x-bind:aria-pressed="mode === 'line'" x-bind:class="mode === 'line' && 'bg-fg text-paper hover:bg-fg hover:text-paper'">
                    <x-icon name="bus" class="size-4" /> Linha
                </x-ui.button>
                <x-ui.button variant="ghost" x-on:click="swap('ride')" x-bind:aria-pressed="mode === 'ride'" x-bind:class="mode === 'ride' && 'bg-fg text-paper hover:bg-fg hover:text-paper'">
                    <x-icon name="route" class="size-4" /> De → Para
                </x-ui.button>
            </div>

            <form x-show="mode !== 'ride'" x-on:submit.prevent="first()" role="search" class="relative mt-3">
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

                <ul x-show="menu && mode === 'place'" x-cloak x-on:click.outside="menu = false" class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-edge bg-card p-1 shadow-lg">
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
                    <li x-show="mode === 'place' && !places.length" class="px-3 py-3 text-sm text-mute">
                        Não achei esse lugar em Pernambuco. Tenta outro nome ou
                        <button type="button" x-on:click="target()" class="cursor-pointer font-medium text-fg underline">marca no mapa</button>.
                    </li>
                </ul>
            </form>

            <form x-show="mode === 'ride'" x-cloak x-on:submit.prevent x-on:keydown.escape="menu = false" role="search" class="relative mt-3">
                <div class="relative rounded-2xl border border-edge bg-card shadow-sm focus-within:border-fg/50">
                    <label class="flex items-center gap-3 border-b border-edge pr-14 pl-4">
                        <span class="w-9 shrink-0 text-xs font-semibold tracking-wide text-mute uppercase">De</span>
                        <input x-ref="qa" x-model="qa" x-on:input="typeEnd('from')" x-on:focus="field = 'from'; menu = true" type="search" autocomplete="off"
                            placeholder="De onde você sai?" class="h-12 w-full min-w-0 bg-transparent outline-none placeholder:text-mute/70 [&::-webkit-search-cancel-button]:hidden">
                    </label>
                    <label class="flex items-center gap-3 pr-14 pl-4">
                        <span class="w-9 shrink-0 text-xs font-semibold tracking-wide text-mute uppercase">Para</span>
                        <input x-ref="qb" x-model="qb" x-on:input="typeEnd('to')" x-on:focus="field = 'to'; menu = places.length > 0" type="search" autocomplete="off"
                            placeholder="Pra onde você vai?" class="h-12 w-full min-w-0 bg-transparent outline-none placeholder:text-mute/70 [&::-webkit-search-cancel-button]:hidden">
                    </label>
                    <x-ui.button variant="ghost" x-on:click="flip()" aria-label="Inverter origem e destino" class="absolute top-1/2 right-2 -translate-y-1/2 px-2">
                        <x-icon name="swap" class="size-5" />
                    </x-ui.button>
                </div>

                <ul x-show="menu && mode === 'ride'" x-cloak x-on:click.outside="menu = false" class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-edge bg-card p-1 shadow-lg">
                    <li x-show="field === 'from'">
                        <x-ui.button variant="row" x-on:click="here()" class="gap-3">
                            <x-icon name="walk" class="size-5 shrink-0 text-mute" />
                            <span class="font-medium">Minha localização</span>
                        </x-ui.button>
                    </li>
                    <template x-for="p in places" x-bind:key="'e' + p.lat + ',' + p.lon">
                        <li>
                            <x-ui.button variant="row" x-on:click="setEnd(p)" class="gap-3">
                                <x-icon name="pin" class="size-5 shrink-0 text-mute" />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium" x-text="p.name"></span>
                                    <span class="block truncate text-sm text-mute" x-text="p.label"></span>
                                </span>
                            </x-ui.button>
                        </li>
                    </template>
                    <li x-show="field === 'to' && !places.length" class="px-3 py-3 text-sm text-mute">Não achei esse lugar em Pernambuco. Tenta outro nome ou toca no mapa.</li>
                </ul>
            </form>

            <div x-show="mode !== 'line' && spots.length && !aim" x-cloak class="mt-3">
                <p x-show="home()" class="mb-2 text-sm font-medium text-mute">Lugares salvos</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="s in spots" x-bind:key="s.key">
                        <x-ui.button variant="chip" x-on:click="go(s)" x-bind:title="s.label || s.name">
                            <x-icon name="bookmark" class="size-3.5 fill-current text-sun" /> <span x-text="s.nick || s.name"></span>
                        </x-ui.button>
                    </template>
                </div>
            </div>

            <x-ui.button variant="ghost" x-show="mode === 'place' && !place && !aim && !detail" x-on:click="target()" class="mt-2 -ml-3 text-sm">
                <x-icon name="pin" class="size-4" /> Escolher no mapa
            </x-ui.button>

            <div x-show="(geo === 'ask' || geo === 'busy') && !detail && mode !== 'ride'" x-cloak class="mt-3 flex items-start gap-3 rounded-2xl border border-edge bg-card p-4">
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

            <div x-show="home() && favs.length" x-cloak class="mt-5">
                <p class="text-sm font-medium text-mute">Favoritas</p>
                <ul class="mt-1 divide-y divide-edge">
                    <template x-for="f in favs" x-bind:key="'f' + f.id">
                        <x-line line="f" note="''" x-on:click="line(f)" />
                    </template>
                </ul>
            </div>

            <div x-show="home() && recents.length" x-cloak class="mt-5">
                <p class="text-sm font-medium text-mute">Ônibus recentes</p>
                <ul class="mt-1 divide-y divide-edge">
                    <template x-for="r in recents" x-bind:key="r.line.id + (r.place?.name ?? '')">
                        <x-line line="r.line" note="r.place ? 'perto de ' + r.place.name : 'busca por linha'" x-on:click="again(r)" />
                    </template>
                </ul>
            </div>

            <template x-if="place || detail || mode === 'ride'">
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

        <section x-bind:class="detail ? 'h-[55dvh]' : aim ? 'h-[60dvh]' : place || (mode === 'ride' && (ends.from || ends.to)) ? 'h-56' : 'hidden'" class="relative lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:block lg:h-auto lg:py-3 lg:pr-3">
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
                                <p x-show="nick(detail.line.id)" class="text-xs font-semibold tracking-wide text-sun uppercase" x-text="nick(detail.line.id)"></p>
                                <h2 class="text-xl leading-tight font-semibold" x-text="detail.line.name"></h2>
                                <p class="text-sm text-mute first-letter:uppercase" x-text="detail.line.agency"></p>
                            </div>
                            <x-ui.button variant="ghost" x-on:click="star(detail.line)" x-bind:aria-pressed="!!fav(detail.line.id)"
                                x-bind:aria-label="fav(detail.line.id) ? 'Tirar das favoritas' : 'Favoritar'" class="-mt-1 px-2">
                                <x-icon name="star" class="size-5" x-bind:class="fav(detail.line.id) && 'fill-current text-sun'" />
                            </x-ui.button>
                            <x-ui.button variant="ghost" x-on:click="close()" aria-label="Fechar linha" class="-mt-1 -mr-2 px-2">
                                <x-icon name="close" class="size-5" />
                            </x-ui.button>
                        </div>

                        <div x-show="fav(detail.line.id)" class="flex items-center gap-3 rounded-2xl border border-edge bg-card px-4 py-1.5">
                            <label for="nick" class="shrink-0 text-xs font-medium tracking-wide text-mute uppercase">Apelido</label>
                            <input id="nick" type="text" maxlength="30" placeholder="Ex.: trabalho, casa da vó" autocomplete="off"
                                x-bind:value="nick(detail.line.id)" x-on:input.debounce.400ms="rename(detail.line.id, $event.target.value)"
                                class="h-9 w-full min-w-0 bg-transparent outline-none placeholder:text-mute/70">
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

                        <div x-show="detail.runs && !origin() && geo !== 'busy'" class="flex items-center justify-between gap-3 rounded-2xl border border-edge bg-card p-4 text-sm">
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
                                <p class="text-sm text-mute" x-text="`${meters(w.drop.meters)} de ${spotName(dest())} · ${walking(w.drop.meters)}`"></p>
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
            <template x-if="mode === 'line' && !place">
                <div class="pt-2 pb-6">
                    <p x-show="busy === 'search'" class="text-sm text-mute">Procurando linhas…</p>
                    <p x-show="busy !== 'search' && found.length" class="mb-2 text-sm text-mute" x-text="heading()"></p>
                    <p x-show="busy !== 'search' && q.trim() && !found.length" class="rounded-2xl border border-dashed border-edge p-5 text-center text-sm text-mute">
                        Nenhuma linha com esse número ou nome.
                    </p>
                    <ul class="divide-y divide-edge">
                        <template x-for="l in found" x-bind:key="'l' + l.id">
                            <x-line line="l" x-on:click="line(l)" />
                        </template>
                    </ul>
                </div>
            </template>

            <template x-if="mode === 'ride'">
                <div class="pt-2 pb-6">
                    <x-radius />

                    <p x-show="!both()" class="mt-4 text-sm text-mute">Escolhe de onde você sai e pra onde vai. A gente mostra os ônibus que levam direto, sem baldeação.</p>
                    <p x-show="busy === 'rides'" class="mt-4 text-sm text-mute">Procurando linhas diretas…</p>
                    <p x-show="both() && busy !== 'rides' && rides.length" class="mt-4 mb-2 text-sm text-mute">
                        <strong class="text-fg" x-text="direct()"></strong> <span x-text="days[day].when"></span> · <span x-text="path()"></span>
                    </p>
                    <div x-show="both() && busy !== 'rides' && !rides.length && !fail" class="mt-4 rounded-2xl border border-dashed border-edge p-5 text-center text-sm text-mute">
                        Nenhuma linha direta entre esses lugares <span x-text="days[day].when"></span>. Tenta aumentar a distância ou outro dia.
                    </div>

                    <ul class="divide-y divide-edge">
                        <template x-for="r in rides" x-bind:key="'r' + r.line.id">
                            <x-line line="r.line" note="`${r.hops} paradas · anda ${meters(r.board.meters)} + ${meters(r.drop.meters)}`" x-on:click="ride(r)" x-bind:disabled="busy === 'line'">
                                <span class="shrink-0 text-right">
                                    <span class="block text-sm font-semibold tabular-nums" x-text="meters(r.walk)"></span>
                                    <span class="block text-xs text-mute">a pé</span>
                                </span>
                            </x-line>
                        </template>
                    </ul>
                </div>
            </template>

            <template x-if="place">
                <div class="pb-6">
                    <x-radius class="pt-2" />

                    <div class="flex items-start gap-3 py-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold" x-text="spotName(place)"></p>
                            <p class="truncate text-sm text-mute" x-text="spot(place)?.nick ? place.name : place.label"></p>
                            <p class="mt-1 text-xs text-mute">Toque no mapa pra mudar o ponto.</p>
                        </div>
                        <x-ui.button variant="chip" x-on:click="keep(place)" x-bind:aria-pressed="!!spot(place)" class="shrink-0 text-sm">
                            <x-icon name="bookmark" class="size-4" x-bind:class="spot(place) && 'fill-current text-sun'" />
                            <span x-text="spot(place) ? 'Salvo' : 'Salvar'"></span>
                        </x-ui.button>
                    </div>

                    <div x-show="spot(place)" class="mb-4 flex items-center gap-3 rounded-2xl border border-edge bg-card px-4 py-1.5">
                        <label for="tag" class="shrink-0 text-xs font-medium tracking-wide text-mute uppercase">Apelido</label>
                        <input id="tag" type="text" maxlength="30" placeholder="Ex.: Casa, Trabalho" autocomplete="off"
                            x-bind:value="spot(place)?.nick ?? ''" x-on:input.debounce.400ms="tag(place, $event.target.value)"
                            class="h-9 w-full min-w-0 bg-transparent outline-none placeholder:text-mute/70">
                    </div>

                    <p x-show="busy === 'lines'" class="text-sm text-mute">Procurando linhas…</p>
                    <p x-show="busy !== 'lines' && hits.length && !wide" class="mb-2 text-sm text-mute">
                        <strong class="text-fg" x-text="count()"></strong> a até <span x-text="meters(radius)"></span> daqui <span x-text="days[day].when"></span>
                    </p>
                    <p x-show="busy !== 'lines' && hits.length && wide" class="mb-2 rounded-2xl border border-dashed border-edge p-4 text-sm text-mute">
                        Nenhum ônibus passa a até <span x-text="meters(radius)"></span> daqui <span x-text="days[day].when"></span>. Os mais perto:
                    </p>
                    <p x-show="busy !== 'lines' && hits.some((h) => fav(h.line.id))" class="mb-2 flex items-center gap-1.5 text-sm font-medium">
                        <x-icon name="star" class="size-4 fill-current text-sun" /> <span x-text="mine()"></span>
                    </p>
                    <div x-show="busy !== 'lines' && !hits.length && !fail" class="rounded-2xl border border-dashed border-edge p-5 text-center text-sm text-mute">
                        Nenhum ônibus a até 5 km daqui <span x-text="days[day].when"></span>. Por enquanto a gente só cobre a Região Metropolitana do Recife.
                    </div>

                    <ul class="divide-y divide-edge">
                        <template x-for="h in ranked()" x-bind:key="h.line.id">
                            <x-line line="h.line" x-on:click="open(h.line.id)" x-bind:disabled="busy === 'line'">
                                <span class="shrink-0 text-right">
                                    <span class="block text-sm font-semibold tabular-nums" x-text="meters(h.meters)"></span>
                                    <span class="block text-xs text-mute" x-text="walking(h.meters)"></span>
                                </span>
                            </x-line>
                        </template>
                    </ul>
                </div>
            </template>

            <footer class="mt-auto space-y-3 border-t border-edge py-5 text-xs text-mute">
                <div class="flex items-center justify-between gap-3">
                    <p class="flex min-w-0 items-center gap-2">
                        <span class="size-2 shrink-0 rounded-full bg-sun"></span>
                        <span>
                            Linhas do <a href="https://www.granderecife.pe.gov.br" class="font-medium text-fg/80 hover:text-fg hover:underline">Grande Recife</a>@if ($fresh), atualizadas em {{ $fresh }}@endif
                        </span>
                    </p>
                    <a href="https://github.com/passa-la/site" class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-edge px-3 py-1.5 font-medium text-fg/80 transition hover:border-fg/40 hover:text-fg">
                        <x-icon name="code" class="size-3.5" /> Código aberto
                    </a>
                </div>
                <p class="flex flex-wrap gap-x-3 gap-y-1 leading-relaxed">
                    <span class="whitespace-nowrap">Mapa <a href="https://openfreemap.org" class="text-fg/80 hover:text-fg hover:underline">OpenFreeMap</a> © <a href="https://www.openstreetmap.org/copyright" class="text-fg/80 hover:text-fg hover:underline">OpenStreetMap</a></span>
                    <span class="whitespace-nowrap">Busca <a href="https://photon.komoot.io" class="text-fg/80 hover:text-fg hover:underline">Photon</a></span>
                    <span class="whitespace-nowrap">A pé <a href="https://routing.openstreetmap.de" class="text-fg/80 hover:text-fg hover:underline">FOSSGIS</a></span>
                </p>
            </footer>
        </section>
    </main>
@endsection
