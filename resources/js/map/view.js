import * as gl from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'
import worker from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import { EMPTY, circle, lines, point, points } from './shape'

const STYLE = 'https://tiles.openfreemap.org/styles/liberty'
const HUB = [-34.8711, -8.0631]
const RADIUS = 1000

gl.setWorkerUrl(worker)

const tone = (name) => getComputedStyle(document.documentElement).getPropertyValue(`--color-${name}`).trim()

export class View {
    constructor(el) {
        this.map = new gl.Map({
            container: el,
            style: STYLE,
            center: HUB,
            zoom: 11,
            attributionControl: { compact: true },
        })
        this.map.addControl(new gl.NavigationControl({ showCompass: false }), 'top-right')
        this.pin = new gl.Marker({ color: tone('ink') })
        this.ready = new Promise((ok) => this.map.on('load', () => ok(this.layers())))
    }

    layers() {
        const m = this.map
        const ink = tone('ink')
        const sun = tone('sun')
        const round = { 'line-join': 'round', 'line-cap': 'round' }

        for (const id of ['area', 'path', 'stops', 'near']) {
            m.addSource(id, { type: 'geojson', data: EMPTY })
        }

        m.addLayer({ id: 'area-fill', type: 'fill', source: 'area', paint: { 'fill-color': sun, 'fill-opacity': 0.14 } })
        m.addLayer({
            id: 'area-edge',
            type: 'line',
            source: 'area',
            paint: { 'line-color': ink, 'line-width': 1.25, 'line-opacity': 0.55, 'line-dasharray': [3, 2] },
        })
        m.addLayer({ id: 'path-case', type: 'line', source: 'path', layout: round, paint: { 'line-color': ink, 'line-width': 8 } })
        m.addLayer({ id: 'path-line', type: 'line', source: 'path', layout: round, paint: { 'line-color': sun, 'line-width': 4.5 } })
        m.addLayer({
            id: 'stops',
            type: 'circle',
            source: 'stops',
            minzoom: 12,
            paint: { 'circle-radius': 3.5, 'circle-color': '#fff', 'circle-stroke-color': ink, 'circle-stroke-width': 1.5 },
        })
        m.addLayer({
            id: 'near',
            type: 'circle',
            source: 'near',
            paint: { 'circle-radius': 9, 'circle-color': ink, 'circle-stroke-color': sun, 'circle-stroke-width': 4 },
        })
    }

    set(id, data) {
        this.map.getSource(id).setData(data)
    }

    async place(p) {
        await this.ready
        const area = circle(p, RADIUS)

        this.pin.setLngLat([p.lon, p.lat]).addTo(this.map)
        this.set('area', area)
        this.set('path', EMPTY)
        this.set('stops', EMPTY)
        this.set('near', EMPTY)
        this.fit(area.geometry.coordinates[0])
    }

    async line(d, p) {
        await this.ready
        const all = d.paths.flatMap((x) => x.geometry.coordinates)
        const spot = d.near ? [[d.near.stop.lon, d.near.stop.lat]] : []

        if (p) {
            this.pin.setLngLat([p.lon, p.lat]).addTo(this.map)
            this.set('area', circle(p, RADIUS))
        }

        this.set('path', lines(d.paths))
        this.set('stops', points(d.stops))
        this.set('near', d.near ? point(d.near.stop) : EMPTY)
        this.fit([...(all.length ? all : d.stops.map((s) => [s.lon, s.lat])), ...spot, ...(p ? [[p.lon, p.lat]] : [])])
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
