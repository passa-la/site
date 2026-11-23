const EARTH = 6371000

export const EMPTY = { type: 'FeatureCollection', features: [] }

export const point = (p) => ({
    type: 'Feature',
    properties: {},
    geometry: { type: 'Point', coordinates: [p.lon, p.lat] },
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

export const gap = (coords, p) => {
    const k = Math.cos((p.lat * Math.PI) / 180) * (Math.PI / 180) * EARTH
    const m = (Math.PI / 180) * EARTH
    const xy = ([lon, lat]) => [(lon - p.lon) * k, (lat - p.lat) * m]

    if (coords.length === 1) {
        return Math.hypot(...xy(coords[0]))
    }

    let best = Infinity

    for (let i = 1; i < coords.length; i++) {
        const [ax, ay] = xy(coords[i - 1])
        const [bx, by] = xy(coords[i])
        const dx = bx - ax
        const dy = by - ay
        const len = dx * dx + dy * dy
        const t = len ? Math.max(0, Math.min(1, -(ax * dx + ay * dy) / len)) : 0

        best = Math.min(best, Math.hypot(ax + t * dx, ay + t * dy))
    }

    return best
}
