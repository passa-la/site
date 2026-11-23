import { api } from './api'

const WAIT = 300
const MIN = 3
const PACE = 75

export const finder = () => ({
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

    init() {
        this.map = import('./map/view').then(({ View }) => new View(this.$refs.map))
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

    sample(q) {
        this.q = q
        this.find()
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
        this.detail = null
        this.hits = []
        this.fail = ''
        this.busy = 'lines'

        if (push) {
            this.save()
        }

        this.draw((v) => v.place(p))

        try {
            this.hits = await api.lines(p)
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
            this.detail = await api.line(id, this.place)

            if (push) {
                this.save()
            }

            window.scrollTo({ top: 0, behavior: 'smooth' })
            this.draw((v) => v.line(this.detail, this.place))
        } catch {
            this.fail = 'Não deu pra abrir essa linha. Tenta de novo.'
        } finally {
            this.busy = ''
        }
    },

    close() {
        this.detail = null
        this.save()
        this.draw((v) => v.place(this.place))
    },

    reset() {
        this.place = null
        this.detail = null
        this.hits = []
        this.q = ''
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

        const url = u.size ? `?${u}` : location.pathname

        if (url !== (location.search || location.pathname)) {
            history.pushState(null, '', url)
        }
    },

    async restore() {
        const u = new URLSearchParams(location.search)
        const lat = parseFloat(u.get('lat'))
        const lon = parseFloat(u.get('lon'))

        if (Number.isNaN(lat) || Number.isNaN(lon)) {
            this.place = null
            this.detail = null
            this.hits = []
            this.q = ''
            return
        }

        const same = this.place && this.place.lat.toFixed(6) === lat.toFixed(6) && this.place.lon.toFixed(6) === lon.toFixed(6)

        if (!same) {
            await this.pick({ name: u.get('n') || 'Local escolhido', label: u.get('d') || '', lat, lon }, false)
        }

        const id = u.get('l')

        if (id) {
            await this.open(id, false)
        } else if (this.detail) {
            this.detail = null
            this.draw((v) => v.place(this.place))
        }
    },

    meters(m) {
        return m < 1000 ? `${m} m` : `${(m / 1000).toFixed(1).replace('.', ',')} km`
    },

    walk(m) {
        return `${Math.max(1, Math.round(m / PACE))} min a pé`
    },

    stop(s) {
        return s.name || `Parada ${s.id}`
    },

    count() {
        const n = this.hits.length
        return n === 1 ? '1 linha passa' : `${n} linhas passam`
    },
})
