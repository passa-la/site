import { api } from '../api'

const MIN = 3
const WAIT = 300

export const ride = () => ({
    ends: { from: null, to: null },
    field: 'to',
    qa: '',
    qb: '',
    rides: [],

    origin() {
        return this.mode === 'ride' ? this.ends.from : this.me
    },

    dest() {
        return this.mode === 'ride' ? this.ends.to : this.place
    },

    text(f) {
        return f === 'from' ? this.qa : this.qb
    },

    write(f, s) {
        if (f === 'from') {
            this.qa = s
        } else {
            this.qb = s
        }
    },

    typeEnd(f) {
        clearTimeout(this.timer)
        this.field = f
        this.fail = ''

        if (this.text(f).trim().length < MIN) {
            this.places = []
            this.menu = f === 'from'
            return
        }

        this.timer = setTimeout(() => this.findEnd(f), WAIT)
    },

    async findEnd(f) {
        const q = this.text(f).trim()
        this.busy = 'search'

        try {
            const all = await api.places(q)

            if (q === this.text(f).trim() && this.field === f) {
                this.places = all
                this.menu = true
            }
        } catch {
            this.fail = 'Não deu pra buscar agora. Tenta de novo em instantes.'
        } finally {
            this.busy = ''
        }
    },

    async setEnd(p, f = this.field) {
        this.ends = { ...this.ends, [f]: p }
        this.write(f, this.spotName(p))
        this.menu = false
        this.places = []

        if (f === 'from' && !this.ends.to) {
            this.field = 'to'
            this.$nextTick(() => this.$refs.qb.focus())
        }

        await this.trips()
    },

    here() {
        const set = () => this.setEnd({ name: 'Minha localização', label: '', lat: this.me.lat, lon: this.me.lon, here: true }, 'from')

        if (this.me) {
            set()
        } else {
            this.locate(set)
        }
    },

    flip() {
        this.ends = { from: this.ends.to, to: this.ends.from }
        ;[this.qa, this.qb] = [this.qb, this.qa]
        this.trips()
    },

    async trips(push = true) {
        const { from, to } = this.ends
        this.rides = []
        this.detail = null
        this.chosen = null
        this.walk = null

        if (push) {
            this.save()
        }

        if (!from || !to) {
            const one = from ?? to

            if (one) {
                this.draw((v) => v.place(one, null, this.radius))
            }

            return
        }

        this.draw((v) => v.pair(from, to, this.radius))
        this.busy = 'rides'

        try {
            this.rides = (await api.rides(from, to, this.day, this.radius)).data
        } catch {
            this.fail = 'Não deu pra procurar as linhas diretas. Tenta de novo.'
        } finally {
            this.busy = ''
        }
    },

    async ride(r) {
        this.chosen = r.board.stop.id
        await this.open(r.line.id)
    },

    both() {
        return !!(this.ends.from && this.ends.to)
    },

    path() {
        return `${this.spotName(this.ends.from)} → ${this.spotName(this.ends.to)}`
    },

    direct() {
        const n = this.rides.length
        return n === 1 ? '1 linha direta' : `${n} linhas diretas`
    },
})
