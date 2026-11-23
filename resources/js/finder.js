import { api } from './api'
import { store } from './store'

const WAIT = 300
const PACE = 75
const KEEP = 6
const WALK = 1000
const RADII = [300, 500, 1000]
const MIN = { place: 3, line: 1 }
const SIDE = 390

const desk = () => window.matchMedia('(min-width: 1024px)').matches

const DAYS = {
    weekday: { name: 'Dias úteis', when: 'em dias úteis' },
    saturday: { name: 'Sábado', when: 'no sábado' },
    sunday: { name: 'Domingo', when: 'no domingo' },
}

const WAYS = ['Ida', 'Volta']

const HINTS = {
    place: 'Ex.: Shopping Patteo Olinda',
    line: 'Ex.: 823 ou Jardim Brasil',
}

export const finder = (today) => ({
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
    walk: null,
    recents: [],
    favs: [],

    init() {
        this.map = import('./map/view').then(
            ({ View }) => new View(this.$refs.map, { stop: (id) => this.board(id), tap: (p) => this.tap(p) }),
        )
        this.recents = store.get('recent', [])
        this.favs = store.get('fav', [])
        this.geo = store.get('geo', 'ask')
        this.radius = RADII.includes(store.get('radius')) ? store.get('radius') : WALK

        if (this.geo === 'on') {
            this.locate()
        }

        window.addEventListener('popstate', () => this.restore())
        this.restore()
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

        this.$nextTick(() => this.$refs.q.focus())
    },

    home() {
        return !this.place && !this.detail && !(this.mode === 'line' && this.q.trim())
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

    async tap(p) {
        if (this.detail || (!this.aim && !this.place && !desk())) {
            return
        }

        const at = { name: 'Ponto no mapa', label: '', lat: p.lat, lon: p.lon, pin: true }
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
        this.draw(() => {})
    },

    async resize(r) {
        if (r === this.radius) {
            return
        }

        this.radius = r
        store.set('radius', r)
        this.save()

        if (this.place) {
            this.draw((v) => v.place(this.place, this.me, r))
            await this.load()
        }
    },

    async open(id, push = true) {
        this.busy = 'line'
        this.fail = ''

        try {
            const me = this.me
            this.detail = await this.fetch(id)

            if (this.me !== me) {
                this.detail = await this.fetch(id)
            }

            this.focus = this.detail.best

            if (push) {
                this.save()
            }

            if (push && !desk()) {
                this.$nextTick(() => this.$refs.map.scrollIntoView({ behavior: 'smooth', block: 'start' }))
            }

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

    fetch(id) {
        return api.line(id, { day: this.day, to: this.place, from: this.me, stop: this.chosen })
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

        this.chosen = id
        await this.refresh()
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

        if (!this.me || !way?.board) {
            return
        }

        try {
            this.walk = await api.walk(this.me, way.board.stop)
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
            place: this.place,
            me: this.me,
            way: this.way(),
            focus: this.focus,
            walk: this.walk,
            radius: this.radius,
            side: desk() ? SIDE : 0,
        }

        this.draw((v) => v.line(this.detail, ctx, fit))
    },

    async pickDay(d) {
        if (d === this.day) {
            return
        }

        this.day = d
        this.chosen = null
        this.save()

        if (this.place) {
            this.load()
        }

        if (this.detail) {
            await this.refresh()
        }
    },

    locate() {
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

                if (this.detail && this.busy !== 'line') {
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

    fav(id) {
        return this.favs.find((f) => f.id === id) ?? null
    },

    star(l) {
        this.favs = this.fav(l.id)
            ? this.favs.filter((f) => f.id !== l.id)
            : [...this.favs, { id: l.id, code: l.code, name: l.name, nick: '' }]
        store.set('fav', this.favs)
    },

    rename(id, nick) {
        this.favs = this.favs.map((f) => (f.id === id ? { ...f, nick: nick.trim().slice(0, 30) } : f))
        store.set('fav', this.favs)
    },

    nick(id) {
        return this.fav(id)?.nick ?? ''
    },

    ranked() {
        return [...this.hits].sort((a, b) => Number(!this.fav(a.line.id)) - Number(!this.fav(b.line.id)))
    },

    mine() {
        const n = this.hits.filter((h) => this.fav(h.line.id)).length
        return n === 1 ? '1 favorita sua passa aqui' : `${n} favoritas suas passam aqui`
    },

    remember() {
        const d = this.detail
        const item = { line: { id: d.line.id, code: d.line.code, name: d.line.name }, place: this.place }
        const same = (r) => r.line.id === d.line.id && r.place?.name === this.place?.name

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
        this.walk = null
        this.focus = null

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
            fn(v)
        })
    },

    url() {
        const u = new URLSearchParams()

        if (this.place) {
            u.set('lat', this.place.lat.toFixed(6))
            u.set('lon', this.place.lon.toFixed(6))
            u.set('n', this.place.name)

            if (this.place.label) {
                u.set('d', this.place.label)
            }
        }

        if (this.detail) {
            u.set('l', this.detail.line.id)
        }

        if (this.day !== this.today) {
            u.set('day', this.day)
        }

        if (this.place && this.radius !== WALK) {
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

        if (!w?.board) {
            return ''
        }

        return this.walk
            ? `${this.meters(this.walk.meters)} de você · ${this.walk.minutes} min a pé`
            : `${this.meters(w.board.meters)} de você`
    },
})
