import { api } from './api'
import { store } from './store'

const WAIT = 300
const MIN = 3
const PACE = 75
const KEEP = 6

const DAYS = {
    weekday: { name: 'Dias úteis', when: 'em dias úteis' },
    saturday: { name: 'Sábado', when: 'no sábado' },
    sunday: { name: 'Domingo', when: 'no domingo' },
}

const WAYS = ['Ida', 'Volta']

export const finder = (today) => ({
    q: '',
    places: [],
    hits: [],
    place: null,
    detail: null,
    menu: false,
    busy: '',
    fail: '',
    timer: null,
    map: null,
    today,
    day: today,
    days: DAYS,
    me: null,
    geo: 'ask',
    focus: null,
    chosen: null,
    walk: null,
    recents: [],

    init() {
        this.map = import('./map/view').then(({ View }) => new View(this.$refs.map, { stop: (id) => this.board(id) }))
        this.recents = store.get('recent', [])
        this.geo = store.get('geo', 'ask')

        if (this.geo === 'on') {
            this.locate()
        }

        window.addEventListener('popstate', () => this.restore())
        this.restore()
    },

    type() {
        clearTimeout(this.timer)
        this.fail = ''

        if (this.q.trim().length < MIN) {
            this.places = []
            this.menu = false
            return
        }

        this.timer = setTimeout(() => this.find(), WAIT)
    },

    async find() {
        const q = this.q.trim()
        this.busy = 'places'

        try {
            const all = await api.places(q)

            if (q === this.q.trim()) {
                this.places = all
                this.menu = true
            }
        } catch {
            this.fail = 'Não deu pra buscar agora. Tenta de novo em instantes.'
        } finally {
            this.busy = ''
        }
    },

    first() {
        if (this.places.length) {
            this.pick(this.places[0])
        }
    },

    async pick(p, push = true) {
        this.place = p
        this.q = p.name
        this.menu = false
        this.places = []
        this.reset()

        if (push) {
            this.save()
        }

        this.draw((v) => v.place(p, this.me))
        await this.load()
    },

    async load() {
        this.busy = 'lines'
        this.fail = ''

        try {
            this.hits = await api.lines(this.place, this.day)
        } catch {
            this.fail = 'Não deu pra carregar as linhas. Tenta de novo.'
        } finally {
            this.busy = ''
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
                window.scrollTo({ top: 0, behavior: 'smooth' })
            }

            this.remember()
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

        const ctx = { place: this.place, me: this.me, way: this.way(), focus: this.focus, walk: this.walk }
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

    remember() {
        const d = this.detail
        const item = { line: { id: d.line.id, code: d.line.code, name: d.line.name }, place: this.place }
        const same = (r) => r.line.id === d.line.id && r.place?.name === this.place?.name

        this.recents = [item, ...this.recents.filter((r) => !same(r))].slice(0, KEEP)
        store.set('recent', this.recents)
    },

    async again(r) {
        await this.pick(r.place)
        await this.open(r.line.id)
    },

    close() {
        this.detail = null
        this.chosen = null
        this.walk = null
        this.focus = null
        this.save()
        this.draw((v) => v.place(this.place, this.me))
    },

    reset() {
        this.detail = null
        this.hits = []
        this.chosen = null
        this.walk = null
        this.focus = null
        this.fail = ''
    },

    clear() {
        this.place = null
        this.q = ''
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

    save() {
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

        const url = u.size ? `?${u}` : location.pathname

        if (url !== (location.search || location.pathname)) {
            history.pushState(null, '', url)
        }
    },

    async restore() {
        const u = new URLSearchParams(location.search)
        const lat = parseFloat(u.get('lat'))
        const lon = parseFloat(u.get('lon'))
        const day = u.get('day')

        this.day = DAYS[day] ? day : this.today

        if (Number.isNaN(lat) || Number.isNaN(lon)) {
            this.place = null
            this.q = ''
            this.reset()
            return
        }

        const same = this.place && this.place.lat.toFixed(6) === lat.toFixed(6) && this.place.lon.toFixed(6) === lon.toFixed(6)

        if (!same) {
            await this.pick({ name: u.get('n') || 'Local escolhido', label: u.get('d') || '', lat, lon }, false)
        } else {
            this.load()
        }

        const id = u.get('l')

        if (id) {
            await this.open(id, false)
        } else if (this.detail) {
            this.detail = null
            this.draw((v) => v.place(this.place, this.me))
        }
    },

    meters(m) {
        return m < 1000 ? `${m} m` : `${(m / 1000).toFixed(1).replace('.', ',')} km`
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
