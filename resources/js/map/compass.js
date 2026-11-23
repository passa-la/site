const STEP = 3

const tilt = () => screen.orientation?.angle ?? window.orientation ?? 0

const north = (e) => {
    if (typeof e.webkitCompassHeading === 'number') {
        return e.webkitCompassHeading
    }

    if ((e.absolute || e.type === 'deviceorientationabsolute') && typeof e.alpha === 'number') {
        return 360 - e.alpha
    }

    return null
}

export const compass = (fn) => {
    let last = null
    let next = null
    let frame = 0

    const read = (e) => {
        const h = north(e)

        if (h === null) {
            return
        }

        next = (h + tilt() + 360) % 360

        if (frame) {
            return
        }

        frame = requestAnimationFrame(() => {
            frame = 0

            if (last === null || Math.abs(((next - last + 540) % 360) - 180) >= STEP) {
                last = next
                fn(next)
            }
        })
    }

    window.addEventListener('ondeviceorientationabsolute' in window ? 'deviceorientationabsolute' : 'deviceorientation', read)

    if (typeof window.DeviceOrientationEvent?.requestPermission === 'function') {
        window.addEventListener('pointerdown', () => window.DeviceOrientationEvent.requestPermission().catch(() => {}), { once: true })
    }
}
