import { api } from './api'
import { store } from './store'
import { marks } from './finder/marks'
import { ride } from './finder/ride'
import { sheet } from './finder/sheet'
import { install } from './finder/install'
import { desk } from './screen'

const WAIT = 300
const PACE = 75
const KEEP = 6
const WALK = 1000
const RADII = [300, 500, 1000]
const MIN = { place: 3, line: 1 }
const SIDE = 390
const TICK = 30000


const DAYS = {
    weekday: { name: 'Dias úteis', when: 'em dias úteis' },
    saturday: { name: 'Sábado', when: 'no sábado' },
    sunday: { name: 'Domingo', when: 'no domingo' },
}

const WAYS = ['Ida', 'Volta']

const HINTS = {
    place: 'Ex.: Shopping Patteo Olinda',
    line: 'Ex.: 823 ou Jardim Brasil',
    ride: '',
}

export const finder = (today) => ({
    ...marks(),
    ...ride(),
    ...sheet(),
    ...install(),
    q: '',
    mode: 'place',
    places: [],
    found: [],
    similar: false,
    hits: [],
    wide: false,
    place: null,
    detail: null,
    menu: false,
    aim: false,
    busy: '',
    fail: '',
    timer: null,
    map: null,
    today,
    day: today,
    days: DAYS,
    radii: RADII,
    radius: WALK,
    me: null,
    geo: 'ask',
    focus: null,
    chosen: null,
    end: null,
    table: false,
    tick: Date.now(),
    got: 0,
    walk: null,
    recents: [],

    init() {
        this.map = import('./map/view').then(
            ({ View }) => new View(this.$refs.map, { stop: (id) => this.board(id), tap: (p) => this.tap(p) }),
        )
        this.recents = store.get('recent', [])
        this.favs = store.get('fav', [])
        this.spots = store.get('spots', [])
        this.geo = store.get('geo', 'ask')
        this.watch()
        this.radius = RADII.includes(store.get('radius')) ? store.get('radius') : WALK

        if (this.geo === 'on') {
            this.locate()
        }

        window.addEventListener('popstate', () => this.restore())
        setInterval(() => this.beat(), TICK)
        this.restore()
    },

    beat() {
        this.tick = Date.now()
        const w = this.way()

        if (!this.busy && w?.times?.next && this.soon(w).length < w.times.next.length) {
            this.refresh()
        }
    },

    hint() {
        return HINTS[this.mode]
    },

    swap(mode) {
        this.mode = mode
        this.q = ''
        this.places = []
        this.found = []
        this.similar = false
        this.menu = false
        this.aim = false

        if (mode === 'line' && this.place) {
            this.place = null
            this.hits = []
            this.paint(true)
            this.save()
        }

        if (mode === 'line' && this.detail) {
            this.like(this.detail.line)
        }

        if (mode === 'ride') {
            this.into()
            return
        }

        this.$nextTick(() => this.$refs.q.focus())
    },

    into() {
        const to = this.place ?? this.ends.to
        const from = this.ends.from ?? (this.me ? { name: 'Minha localização', label: '', lat: this.me.lat, lon: this.me.lon, here: true } : null)

        this.place = null
        this.hits = []
        this.ends = { from, to }
        this.qa = this.spotName(from)
        this.qb = this.spotName(to)
        this.field = from ? 'to' : 'from'
        this.trips()
        this.$nextTick(() => (from ? this.$refs.qb : this.$refs.qa).focus())
    },

    home() {
        return !this.place && !this.detail && this.mode !== 'ride' && !(this.mode === 'line' && this.q.trim())
    },

    heading() {
        if (this.similar) {
            return `Parecidas com a ${this.detail?.line.code ?? ''}`
        }

        const n = this.found.length
        return n === 1 ? '1 linha encontrada' : `${n} linhas encontradas`
    },

    async like(l) {
        const term = l.name.split(/[\/(]/)[0].trim() || l.code

        try {
            this.found = await api.find(term)
            this.similar = true
        } catch {
            this.found = []
        }
    },

    type() {
        clearTimeout(this.timer)
        this.fail = ''

        if (this.q.trim().length < MIN[this.mode]) {
            this.places = []
            this.found = []
            this.menu = false

            if (this.mode === 'line' && this.detail) {
                this.like(this.detail.line)
            }

            return
        }

        this.timer = setTimeout(() => this.find(), WAIT)
    },

    async find() {
        const q = this.q.trim()
        const mode = this.mode
        this.busy = 'search'

        try {
            const all = mode === 'line' ? await api.find(q) : await api.places(q)

            if (q === this.q.trim() && mode === this.mode) {
                if (mode === 'line') {
                    this.found = all
                    this.similar = false
                } else {
                    this.places = all
                    this.menu = true
                }
            }
        } catch {
            this.fail = 'Não deu pra buscar agora. Tenta de novo em instantes.'
        } finally {
            this.busy = ''
        }
    },

    first() {
        if (this.mode === 'line' && this.found.length) {
            this.line(this.found[0])
        } else if (this.mode === 'place' && this.places.length) {
            this.pick(this.places[0])
        }
    },

    line(l) {
        if (this.mode !== 'line') {
            this.mode = 'line'
            this.q = ''
            this.found = []
        }

        this.menu = false
        this.place = null
        this.hits = []
        this.open(l.id)
    },

    async pick(p, push = true) {
        this.place = p
        this.q = p.name
        this.menu = false
        this.aim = false
        this.places = []
        this.reset()
        this.snapTo('mid')

        if (push) {
            this.save()
        }

        this.draw((v) => v.place(p, this.me, this.radius))
        await this.load()
    },

    async load() {
        this.busy = 'lines'
        this.fail = ''

        try {
            const r = await api.lines(this.place, this.day, this.radius)
            this.hits = r.data
            this.wide = r.meta.wide
        } catch {
            this.fail = 'Não deu pra carregar as linhas. Tenta de novo.'
        } finally {
            this.busy = ''
        }
    },

    async name(at, done) {
        try {
            const named = await api.reverse(at)
            done({ ...named, lat: at.lat, lon: at.lon, pin: true })
            history.replaceState(null, '', this.url())
        } catch {}
    },

    async tap(p) {
        if (this.detail || !(desk() || this.aim || this.aiming)) {
            return
        }

        const at = { name: 'Ponto no mapa', label: '', lat: p.lat, lon: p.lon, pin: true }

        if (this.mode === 'ride') {
            const f = this.aiming ?? this.field
            await this.setEnd(at, f)
            this.name(at, (named) => {
                this.ends = { ...this.ends, [f]: named }
                this.write(f, named.name)
            })
            return
        }

        this.mode = 'place'
        await this.pick(at)

        try {
            const named = await api.reverse(at)

            if (this.place === at || (this.place?.lat === at.lat && this.place?.lon === at.lon)) {
                this.place = { ...named, lat: at.lat, lon: at.lon, pin: true }
                this.q = this.place.name
                history.replaceState(null, '', this.url())
            }
        } catch {}
    },

    target() {
        this.aim = true
        this.menu = false
        this.snapTo('low')
        document.activeElement?.blur()
        this.draw(() => {})
    },

    async resize(r) {
        if (r === this.radius) {
            return
        }

        this.radius = r
        store.set('radius', r)
        this.save()

        if (this.mode === 'ride') {
            await this.trips(false)
        } else if (this.place) {
            this.draw((v) => v.place(this.place, this.me, r))
            await this.load()
        }
    },

    async open(id, push = true) {
        this.busy = 'line'
        this.fail = ''

        try {
            const me = this.origin()
            this.table = false
            this.detail = await this.fetch(id)

            if (this.origin() !== me) {
                this.detail = await this.fetch(id)
            }

            this.focus = this.detail.best

            if (push) {
                this.save()
            }

            this.snapTo('mid')
            this.$nextTick(() => this.$refs.scroll?.scrollTo({ top: 0, behavior: 'smooth' }))

            this.remember()

            if (this.mode === 'line' && !this.place && !this.q.trim()) {
                this.like(this.detail.line)
            }

            await this.route(true)
        } catch {
            this.fail = 'Não deu pra abrir essa linha. Tenta de novo.'
        } finally {
            this.busy = ''
        }
    },

    async fetch(id) {
        const d = await api.line(id, { day: this.day, to: this.dest(), from: this.origin(), stop: this.chosen, end: this.end })
        this.got = Date.now()
        this.tick = this.got

        return d
    },

    async refresh() {
        if (!this.detail) {
            return
        }

        this.detail = await this.fetch(this.detail.line.id)
        this.focus = this.detail.best ?? this.focus
        await this.route(false)
    },

    async board(id) {
        if (!this.detail) {
            return
        }

        if (this.chosen && !this.end && id !== this.chosen) {
            this.end = id
        } else {
            this.chosen = id
            this.end = null
        }

        await this.refresh()
    },

    unend() {
        this.end = null
        this.refresh()
    },

    show(dir) {
        this.focus = dir
        this.route(false)
    },

    way() {
        const ways = this.detail?.ways ?? []
        const dir = this.focus ?? this.detail?.best

        return ways.find((w) => w.dir === dir) ?? ways[0] ?? null
    },

    async route(fit) {
        const way = this.way()
        this.walk = null
        this.paint(fit)

        if (!this.origin() || !way?.board) {
            return
        }

        try {
            this.walk = await api.walk(this.origin(), way.board.stop)
            this.paint(false)
        } catch {
            this.walk = null
        }
    },

    paint(fit) {
        if (!this.detail) {
            return
        }

        const ctx = {
            place: this.dest(),
            me: this.origin(),
            way: this.way(),
            focus: this.focus,
            walk: this.walk,
            radius: this.radius,
        }

        this.draw((v) => v.line(this.detail, ctx, fit))
    },

    async pickDay(d) {
        if (d === this.day) {
            return
        }

        this.day = d
        this.chosen = null
        this.end = null
        this.save()

        if (this.mode === 'ride') {
            const open = this.detail?.line.id
            await this.trips(false)

            if (open) {
                await this.open(open, false)
            }

            return
        }

        if (this.place) {
            this.load()
        }

        if (this.detail) {
            await this.refresh()
        }
    },

    locate(done) {
        if (!navigator.geolocation) {
            this.geo = 'off'
            return
        }

        this.geo = 'busy'
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.me = { lat: pos.coords.latitude, lon: pos.coords.longitude }
                this.geo = 'on'
                store.set('geo', 'on')

                if (done) {
                    done()
                } else if (this.detail && this.busy !== 'line' && this.mode !== 'ride') {
                    this.refresh()
                } else if (this.place) {
                    this.draw((v) => v.spot(this.me))
                }
            },
            (err) => {
                this.geo = err.code === err.PERMISSION_DENIED ? 'denied' : 'off'
                store.set('geo', 'off')
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
        )
    },

    skip() {
        this.geo = 'off'
        store.set('geo', 'off')
    },

    remember() {
        const d = this.detail
        const place = this.dest()
        const item = { line: { id: d.line.id, code: d.line.code, name: d.line.name }, place }
        const same = (r) => r.line.id === d.line.id && r.place?.name === place?.name

        this.recents = [item, ...this.recents.filter((r) => !same(r))].slice(0, KEEP)
        store.set('recent', this.recents)
    },

    async again(r) {
        if (!r.place) {
            this.line(r.line)
            return
        }

        this.mode = 'place'
        await this.pick(r.place)
        await this.open(r.line.id)
    },

    close() {
        this.detail = null
        this.chosen = null
        this.end = null
        this.walk = null
        this.focus = null

        if (this.mode === 'ride') {
            this.save()
            this.draw((v) => this.both() && v.pair(this.ends.from, this.ends.to, this.radius))
            return
        }

        if (!this.place) {
            this.save()

            if (this.mode !== 'line') {
                this.clear()
            }

            return
        }

        this.save()
        this.draw((v) => v.place(this.place, this.me, this.radius))
    },

    reset() {
        this.detail = null
        this.hits = []
        this.wide = false
        this.chosen = null
        this.end = null
        this.walk = null
        this.focus = null
        this.fail = ''
    },

    clear() {
        this.place = null
        this.q = ''
        this.aim = false
        this.reset()
        this.save()
        this.$nextTick(() => this.$refs.q.focus())
    },

    draw(fn) {
        this.$nextTick(async () => {
            const v = await this.map
            v.resize()
            v.edge = this.edge()
            fn(v)
        })
    },

    edge() {
        return desk() ? { right: this.detail ? SIDE : 0, bottom: 0 } : { right: 0, bottom: this.sheetPx() }
    },

    url() {
        const u = new URLSearchParams()
        const to = this.dest()
        const from = this.mode === 'ride' ? this.ends.from : null

        if (from) {
            u.set('de', `${from.lat.toFixed(6)},${from.lon.toFixed(6)}`)
            u.set('dn', from.name)
        }

        if (to) {
            u.set('lat', to.lat.toFixed(6))
            u.set('lon', to.lon.toFixed(6))
            u.set('n', to.name)

            if (to.label) {
                u.set('d', to.label)
            }
        }

        if (this.detail) {
            u.set('l', this.detail.line.id)
        }

        if (this.day !== this.today) {
            u.set('day', this.day)
        }

        if (to && this.radius !== WALK) {
            u.set('r', this.radius)
        }

        return u.size ? `?${u}` : location.pathname
    },

    save() {
        const url = this.url()

        if (url !== (location.search || location.pathname)) {
            history.pushState(null, '', url)
        }
    },

    async restore() {
        const u = new URLSearchParams(location.search)
        const lat = parseFloat(u.get('lat'))
        const lon = parseFloat(u.get('lon'))
        const day = u.get('day')
        const r = parseInt(u.get('r'), 10)
        const id = u.get('l')

        this.day = DAYS[day] ? day : this.today

        if (RADII.includes(r)) {
            this.radius = r
        }

        if (u.get('de')) {
            const [flat, flon] = u.get('de').split(',').map(parseFloat)
            const to = Number.isNaN(lat) ? null : { name: u.get('n') || 'Ponto no mapa', label: u.get('d') || '', lat, lon }

            this.mode = 'ride'
            this.place = null
            this.ends = { from: { name: u.get('dn') || 'Ponto no mapa', label: '', lat: flat, lon: flon }, to }
            this.qa = this.spotName(this.ends.from)
            this.qb = this.spotName(to)
            await this.trips(false)

            if (id) {
                await this.open(id, false)
            }

            return
        }

        if (Number.isNaN(lat) || Number.isNaN(lon)) {
            this.place = null
            this.q = ''
            this.reset()

            if (id) {
                this.mode = 'line'
                await this.open(id, false)
            }

            return
        }

        if (this.mode === 'ride') {
            this.mode = 'place'
        }

        const same = this.place && this.place.lat.toFixed(6) === lat.toFixed(6) && this.place.lon.toFixed(6) === lon.toFixed(6)

        if (!same) {
            await this.pick({ name: u.get('n') || 'Ponto no mapa', label: u.get('d') || '', lat, lon }, false)
        } else {
            this.load()
        }

        if (id) {
            await this.open(id, false)
        } else if (this.detail) {
            this.detail = null
            this.draw((v) => v.place(this.place, this.me, this.radius))
        }
    },

    meters(m) {
        return m < 1000 ? `${m} m` : `${(m / 1000).toFixed(1).replace('.0', '').replace('.', ',')} km`
    },

    walking(m) {
        return `${Math.max(1, Math.round(m / PACE))} min a pé`
    },

    stop(s) {
        return s.name || `Parada ${s.id}`
    },

    count() {
        const n = this.hits.length
        return n === 1 ? '1 linha passa' : `${n} linhas passam`
    },

    label(dir) {
        return WAYS[dir] ?? `Sentido ${dir + 1}`
    },

    runs(d) {
        return !this.detail || this.detail.line.days.includes(d)
    },

    trip() {
        const w = this.way()

        if (!w?.board || !this.origin()) {
            return ''
        }

        const who = this.mode === 'ride' ? this.spotName(this.ends.from) : 'você'

        return this.walk
            ? `${this.meters(this.walk.meters)} de ${who} · ${this.walk.minutes} min a pé`
            : `${this.meters(w.board.meters)} de ${who}`
    },

    soon(w) {
        const gone = Math.floor((this.tick - this.got) / 60000)

        return (w?.times?.next ?? []).map((b) => ({ ...b, in: b.in - gone })).filter((b) => b.in >= 0)
    },

    wait(m) {
        if (m < 1) {
            return 'agora'
        }

        if (m < 60) {
            return `em ${m} min`
        }

        return `em ${Math.floor(m / 60)} h${m % 60 ? ` ${m % 60} min` : ''}`
    },

    hours(w) {
        const out = []

        for (const t of w?.times?.all ?? []) {
            const [h, m] = t.split(':')
            const last = out.at(-1)

            if (last?.h === h) {
                last.m.push(m)
            } else {
                out.push({ h, m: [m] })
            }
        }

        return out
    },

    arrive(w) {
        const t = w?.times

        if (!t?.ride || t.end !== w.drop?.stop.id) {
            return ''
        }

        const b = this.soon(w)[0]

        return b?.end ? `Próximo chega ~${b.end} · ${t.ride} min de ônibus` : `~${t.ride} min de ônibus`
    },

    nudge() {
        if (!this.chosen) {
            return 'Toca em outra parada no mapa pra trocar.'
        }

        return this.end ? 'Toca em outra parada pra escolher outro embarque.' : 'Toca na parada onde vai descer pra ver a que horas chega.'
    },
})
