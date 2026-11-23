const get = async (path, params = {}) => {
    const q = new URLSearchParams(params)
    const r = await fetch(`/api/v1/${path}${q.size ? `?${q}` : ''}`, {
        headers: { Accept: 'application/json' },
    })

    if (!r.ok) {
        throw new Error(`${r.status} ${path}`)
    }

    return r.json()
}

const at = (p) => (p ? { lat: p.lat, lon: p.lon } : {})

export const api = {
    places: async (q) => (await get('places', { q })).data,
    lines: async (p) => (await get('lines', at(p))).data,
    line: async (id, p) => (await get(`lines/${encodeURIComponent(id)}`, at(p))).data,
}
