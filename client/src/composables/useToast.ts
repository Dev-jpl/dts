import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
    id: number
    type: ToastType
    title: string
    message?: string
    duration?: number
}

const toasts = ref<Toast[]>([])
let counter = 0

export function useToast() {
    function show(type: ToastType, title: string, message?: string, duration = 4000) {
        const id = ++counter
        toasts.value.push({ id, type, title, message, duration })

        if (duration > 0) {
            setTimeout(() => dismiss(id), duration)
        }

        return id
    }

    function dismiss(id: number) {
        toasts.value = toasts.value.filter(t => t.id !== id)
    }

    function success(title: string, message?: string) {
        return show('success', title, message)
    }

    function error(title: string, message?: string) {
        return show('error', title, message, 6000)
    }

    function warning(title: string, message?: string) {
        return show('warning', title, message)
    }

    function info(title: string, message?: string) {
        return show('info', title, message)
    }

    return { toasts, show, dismiss, success, error, warning, info }
}