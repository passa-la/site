import { installed, pwa } from '../pwa'
import { store } from '../store'

export const install = () => ({
    installable: false,

    watch() {
        const sync = () => (this.installable = !!pwa.prompt && !installed() && !store.get('noinstall', false))

        sync()
        window.addEventListener('installable', sync)
    },

    async setup() {
        if (!pwa.prompt) {
            return
        }

        pwa.prompt.prompt()
        await pwa.prompt.userChoice
        pwa.prompt = null
        this.installable = false
    },

    later() {
        store.set('noinstall', true)
        this.installable = false
    },
})
