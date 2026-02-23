import type { DirectiveBinding } from 'vue'

export const vOutsideClick = {
    beforeMount(
        el: HTMLElement & { _clickOutsideHandler?: (event: MouseEvent) => void },
        binding: DirectiveBinding<() => void>
    ) {
        // Define the handler
        el._clickOutsideHandler = (event: MouseEvent) => {
            const target = event.target as Node
            if (el && !el.contains(target)) {
                binding.value() // Trigger bound function
            }
        }

        document.addEventListener('mousedown', el._clickOutsideHandler)
    },

    unmounted(el: HTMLElement & { _clickOutsideHandler?: (event: MouseEvent) => void }) {
        if (el._clickOutsideHandler) {
            document.removeEventListener('mousedown', el._clickOutsideHandler)
        }
    }
} as const
