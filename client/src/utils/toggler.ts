import { ref } from "vue"

export function useToggle(initial = false) {
    const isToggled = ref(initial)

    function toggle() {
        isToggled.value = !isToggled.value
    }

    function on() {
        isToggled.value = true
    }

    function off() {
        isToggled.value = false
    }

    return {
        isToggled,
        toggle,
        on,
        off,
    }
}
