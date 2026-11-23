import * as gl from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'
import worker from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import { EMPTY, circle, point } from './shape'

gl.setWorkerUrl(worker)

const STYLE = 'https://tiles.openfreemap.org/styles/liberty'
const HUB = [-34.8711, -8.0631]
const SIDE = 3
const DIM = 0.22

const tone = (name) => getComputedStyle(document.documentElement).getPropertyValue(`--color-${name}`).trim()

const person = () => {
    const el = document.createElement('div')
    el.className = 'me'
    el.innerHTML =
        '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="6.5" r="2.6"/><path d="M8.2 21.5l1.6-6.2-1.6-1.5.9-4.1a2 2 0 0 1 2-1.6h1.8a2 2 0 0 1 2 1.6l.9 4.1-1.6 1.5 1.6 6.2"/></svg>'
    return el
}

const all = (features) => ({ type: 'FeatureCollection', features })

export class View {
    constructor(el, { stop, tap } = {}) {
        this.map = new gl.Map({
            container: el,
            style: STYLE,
            center: HUB,
            zoom: 11,
            attributionControl: { compact: true },
        })
        this.map.addControl(new gl.NavigationControl({ showCompass: false }), 'top-right')
        this.pin = new gl.Marker({ color: tone('ink') })
        this.me = new gl.Marker({ element: person() })
        this.stop = stop
        this.tap = tap
        this.ready = new Promise((ok) => this.map.on('load', () => ok(this.layers())))
    }

    layers() {
        const m = this.map
        const ink = tone('ink')
        const sun = tone('sun')
        const hue = ['match', ['get', 'dir'], 1, tone('back'), sun]
        const round = { 'line-join': 'round', 'line-cap': 'round' }

        for (const id of ['area', 'walk', 'path', 'stops', 'board', 'drop']) {
            m.addSource(id, { type: 'geojson', data: EMPTY })
        }

        m.addLayer({ id: 'area-fill', type: 'fill', source: 'area', paint: { 'fill-color': sun, 'fill-opacity': 0.14 } })
        m.addLayer({
            id: 'area-edge',
            type: 'line',
            source: 'area',
            paint: { 'line-color': ink, 'line-width': 1.25, 'line-opacity': 0.55, 'line-dasharray': [3, 2] },
        })
        m.addLayer({
            id: 'path-case',
            type: 'line',
            source: 'path',
            layout: round,
            paint: { 'line-color': ink, 'line-width': 8, 'line-offset': SIDE },
        })
        m.addLayer({
            id: 'path-line',
            type: 'line',
            source: 'path',
            layout: round,
            paint: { 'line-color': hue, 'line-width': 4.5, 'line-offset': SIDE },
        })
        m.addLayer({
            id: 'path-arrow',
            type: 'symbol',
            source: 'path',
            layout: {
                'symbol-placement': 'line',
                'symbol-spacing': 70,
                'text-field': '›',
                'text-font': ['Noto Sans Bold'],
                'text-size': 22,
                'text-keep-upright': false,
                'text-rotation-alignment': 'map',
                'text-allow-overlap': true,
            },
            paint: { 'text-color': ink, 'text-halo-color': hue, 'text-halo-width': 1 },
        })
        m.addLayer({
            id: 'walk',
            type: 'line',
            source: 'walk',
            layout: round,
            paint: { 'line-color': ink, 'line-width': 3.5, 'line-dasharray': [0.2, 1.6] },
        })
        m.addLayer({
            id: 'stops',
            type: 'circle',
            source: 'stops',
            minzoom: 11,
            paint: {
                'circle-radius': ['interpolate', ['linear'], ['zoom'], 11, 2.5, 15, 5.5],
                'circle-color': '#fff',
                'circle-stroke-color': ink,
                'circle-stroke-width': 1.5,
            },
        })
        m.addLayer({
            id: 'drop',
            type: 'circle',
            source: 'drop',
            paint: { 'circle-radius': 8, 'circle-color': ink, 'circle-stroke-color': hue, 'circle-stroke-width': 4 },
        })
        m.addLayer({
            id: 'board',
            type: 'circle',
            source: 'board',
            paint: { 'circle-radius': 9, 'circle-color': '#fff', 'circle-stroke-color': ink, 'circle-stroke-width': 4.5 },
        })

        m.on('click', 'stops', (e) => this.stop?.(String(e.features[0].properties.id)))
        m.on('click', (e) => {
            if (!m.queryRenderedFeatures(e.point, { layers: ['stops'] }).length) {
                this.tap?.({ lat: e.lngLat.lat, lon: e.lngLat.lng })
            }
        })
        m.on('mouseenter', 'stops', () => (m.getCanvas().style.cursor = 'pointer'))
        m.on('mouseleave', 'stops', () => (m.getCanvas().style.cursor = ''))
    }

    set(id, data) {
        this.map.getSource(id).setData(data)
    }

    spot(me) {
        if (me) {
            this.me.setLngLat([me.lon, me.lat]).addTo(this.map)
        } else {
            this.me.remove()
        }
    }

    async place(p, me, radius) {
        await this.ready
        const area = circle(p, radius)

        this.pin.setLngLat([p.lon, p.lat]).addTo(this.map)
        this.spot(me)
        this.set('area', area)

        for (const id of ['walk', 'path', 'stops', 'board', 'drop']) {
            this.set(id, EMPTY)
        }

        this.fit(area.geometry.coordinates[0])
    }

    async line(d, { place, me, way, focus, walk, radius }, fit = true) {
        await this.ready
        const shown = d.ways.filter((w) => focus === null || w.dir === focus)

        if (place) {
            this.pin.setLngLat([place.lon, place.lat]).addTo(this.map)
            this.set('area', circle(place, radius))
        } else {
            this.pin.remove()
            this.set('area', EMPTY)
        }

        this.spot(me)
        this.set('path', all(d.ways.map((w) => ({ type: 'Feature', properties: { dir: w.dir }, geometry: w.geometry }))))
        this.set('stops', all(shown.flatMap((w) => w.stops.map((s) => ({ ...point(s), properties: { id: s.id, dir: w.dir } })))))
        this.set('drop', all(shown.filter((w) => w.drop).map((w) => ({ ...point(w.drop.stop), properties: { dir: w.dir } }))))
        this.set('board', way?.board ? point(way.board.stop) : EMPTY)
        this.set('walk', walk ? { type: 'Feature', properties: {}, geometry: walk.geometry } : EMPTY)
        this.focus(focus)

        if (fit) {
            const coords = shown.flatMap((w) => (w.geometry.coordinates.length ? w.geometry.coordinates : w.stops.map((s) => [s.lon, s.lat])))
            const extra = [place, me].filter(Boolean).map((p) => [p.lon, p.lat])
            this.fit([...coords, ...extra])
        }
    }

    focus(dir) {
        const fade = dir === null ? 1 : ['match', ['get', 'dir'], dir, 1, DIM]

        this.map.setPaintProperty('path-case', 'line-opacity', fade)
        this.map.setPaintProperty('path-line', 'line-opacity', fade)
        this.map.setPaintProperty('path-arrow', 'text-opacity', fade)
    }

    fit(coords) {
        if (!coords.length) {
            return
        }

        const box = coords.reduce((b, c) => b.extend(c), new gl.LngLatBounds(coords[0], coords[0]))
        this.map.fitBounds(box, { padding: 40, duration: 500, maxZoom: 16 })
    }

    resize() {
        this.map.resize()
    }
}
