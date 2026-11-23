export const store = {
    get(key, fallback) {
        try {
            const v = localStorage.getItem(`passa:${key}`)
            return v === null ? fallback : JSON.parse(v)
        } catch {
            return fallback
        }
    },

    set(key, value) {
        try {
            localStorage.setItem(`passa:${key}`, JSON.stringify(value))
        } catch {}
    },
}
