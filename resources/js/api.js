const get = async (path, params = {}) => {
    const q = new URLSearchParams(Object.entries(params).filter(([, v]) => v !== null && v !== undefined))
    const r = await fetch(`/api/v1/${path}${q.size ? `?${q}` : ''}`, {
        headers: { Accept: 'application/json' },
    })

    if (!r.ok) {
        throw new Error(`${r.status} ${path}`)
    }

    return r.json()
}

const at = (p, prefix = '') => (p ? { [`${prefix}lat`]: p.lat, [`${prefix}lon`]: p.lon } : {})

export const api = {
    places: async (q) => (await get('places', { q })).data,
    lines: async (p, day) => (await get('lines', { ...at(p), day })).data,
    line: async (id, { day, to, from, stop }) =>
        (await get(`lines/${encodeURIComponent(id)}`, { ...at(to), ...at(from, 'from_'), day, stop })).data,
    walk: async (from, to) => (await get('walk', { ...at(to), ...at(from, 'from_') })).data,
}
