import { desk } from '../screen'

const SNAP = { low: 0.3, mid: 0.56, high: 0.92 }
const MOVE = 6
const FLOOR = 96

export const sheet = () => ({
    snap: 'mid',
    drag: null,
    sheetH: `${SNAP.mid * 100}dvh`,

    snapTo(s) {
        if (desk() || !SNAP[s]) {
            return
        }

        this.snap = s
        this.sheetH = `${SNAP[s] * 100}dvh`
    },

    sheetPx() {
        return desk() ? 0 : Math.round(window.innerHeight * SNAP[this.snap])
    },

    grab(e) {
        this.drag = { y: e.clientY, h: this.$refs.sheet.offsetHeight }
        e.currentTarget.setPointerCapture(e.pointerId)
    },

    move(e) {
        if (!this.drag) {
            return
        }

        const h = Math.min(window.innerHeight * SNAP.high, Math.max(FLOOR, this.drag.h + this.drag.y - e.clientY))
        this.sheetH = `${h}px`
    },

    drop(e) {
        if (!this.drag) {
            return
        }

        const moved = Math.abs(e.clientY - this.drag.y) > MOVE
        const ratio = this.$refs.sheet.offsetHeight / window.innerHeight
        this.drag = null

        if (!moved) {
            this.snapTo(this.snap === 'high' ? 'low' : this.snap === 'low' ? 'mid' : 'high')
            return
        }

        const near = Object.entries(SNAP).sort((a, b) => Math.abs(a[1] - ratio) - Math.abs(b[1] - ratio))[0][0]
        this.snapTo(near)
    },
})
