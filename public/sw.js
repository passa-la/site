const V = new URL(location.href).searchParams.get('v') || 'dev'
const SHELL = `shell-${V}`
const STATIC = `static-${V}`
const HOME = '/'

const kept = (u) => u.pathname.startsWith('/build/') || u.pathname.startsWith('/icons/') || u.pathname === '/manifest.webmanifest'

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(SHELL).then((c) => c.add(HOME)).then(() => self.skipWaiting()))
})

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== SHELL && k !== STATIC).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    )
})

const page = async (r) => {
    try {
        const res = await fetch(r)
        const c = await caches.open(SHELL)
        await c.put(HOME, res.clone())
        return res
    } catch {
        return (await caches.match(HOME)) ?? Response.error()
    }
}

const asset = async (r) => {
    const hit = await caches.match(r)

    if (hit) {
        return hit
    }

    const res = await fetch(r)

    if (res.ok) {
        const c = await caches.open(STATIC)
        await c.put(r, res.clone())
    }

    return res
}

self.addEventListener('fetch', (e) => {
    const r = e.request
    const u = new URL(r.url)

    if (r.method !== 'GET' || u.origin !== location.origin) {
        return
    }

    if (r.mode === 'navigate') {
        e.respondWith(page(r))
    } else if (kept(u)) {
        e.respondWith(asset(r))
    }
})
