import { ref, onMounted, onBeforeUnmount } from 'vue'

export function useDeviceType() {
    const deviceType = ref<'mobile' | 'tablet' | 'desktop'>('desktop')

    const detectDeviceType = () => {
        const width = window.innerWidth

        if (width <= 767) {
            deviceType.value = 'mobile'
        } else if (width <= 1024) {
            deviceType.value = 'tablet'
        } else {
            deviceType.value = 'desktop'
        }
    }

    onMounted(() => {
        detectDeviceType()
        window.addEventListener('resize', detectDeviceType)
    })

    onBeforeUnmount(() => {
        window.removeEventListener('resize', detectDeviceType)
    })

    return { deviceType }
}
