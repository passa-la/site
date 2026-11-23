export const pwa = { prompt: null }

const standalone = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true

export const installed = standalone

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    pwa.prompt = e
    window.dispatchEvent(new CustomEvent('installable'))
})

window.addEventListener('appinstalled', () => {
    pwa.prompt = null
    window.dispatchEvent(new CustomEvent('installable'))
})

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const v = document.querySelector('meta[name="build"]')?.content ?? 'dev'
        navigator.serviceWorker.register(`/sw.js?v=${v}`).catch(() => {})
    })
}
