const EARTH = 6371000

export const EMPTY = { type: 'FeatureCollection', features: [] }

export const point = (p) => ({
    type: 'Feature',
    properties: {},
    geometry: { type: 'Point', coordinates: [p.lon, p.lat] },
})

export const points = (all) => ({ type: 'FeatureCollection', features: all.map(point) })

export const lines = (paths) => ({
    type: 'FeatureCollection',
    features: paths.map((p) => ({ type: 'Feature', properties: { dir: p.dir }, geometry: p.geometry })),
})

export const circle = (p, meters, steps = 72) => {
    const lat = (p.lat * Math.PI) / 180
    const lon = (p.lon * Math.PI) / 180
    const d = meters / EARTH
    const ring = []

    for (let i = 0; i <= steps; i++) {
        const b = (i / steps) * 2 * Math.PI
        const y = Math.asin(Math.sin(lat) * Math.cos(d) + Math.cos(lat) * Math.sin(d) * Math.cos(b))
        const x = lon + Math.atan2(Math.sin(b) * Math.sin(d) * Math.cos(lat), Math.cos(d) - Math.sin(lat) * Math.sin(y))
        ring.push([(x * 180) / Math.PI, (y * 180) / Math.PI])
    }

    return { type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [ring] } }
}
