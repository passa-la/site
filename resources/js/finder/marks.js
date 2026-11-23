import { store } from '../store'

const NICK = 30

const key = (p) => `${p.lat.toFixed(5)},${p.lon.toFixed(5)}`

const short = (s) => s.trim().slice(0, NICK)

export const marks = () => ({
    favs: [],
    spots: [],

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
        this.favs = this.favs.map((f) => (f.id === id ? { ...f, nick: short(nick) } : f))
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

    spot(p) {
        return p ? (this.spots.find((s) => s.key === key(p)) ?? null) : null
    },

    keep(p) {
        this.spots = this.spot(p)
            ? this.spots.filter((s) => s.key !== key(p))
            : [...this.spots, { key: key(p), name: p.name, label: p.label ?? '', lat: p.lat, lon: p.lon, nick: '' }]
        store.set('spots', this.spots)
    },

    tag(p, nick) {
        this.spots = this.spots.map((s) => (s.key === key(p) ? { ...s, nick: short(nick) } : s))
        store.set('spots', this.spots)
    },

    spotName(p) {
        return p ? this.spot(p)?.nick || p.name : ''
    },

    go(s) {
        if (this.mode === 'ride') {
            this.setEnd(s)
            return
        }

        this.mode = 'place'
        this.pick(s)
    },
})
