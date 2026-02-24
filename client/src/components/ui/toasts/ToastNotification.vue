<script setup lang="ts">
import { useToast } from '@/composables/useToast'
import type { Toast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()

const icons = {
    success: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`,
    error: `<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`,
    warning: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />`,
    info: `<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />`,
}

const colorMap = {
    success: {
        bg: 'bg-white border-l-4 border-l-emerald-500',
        icon: 'text-emerald-500',
        title: 'text-gray-800',
        message: 'text-gray-500',
        close: 'text-gray-400 hover:text-gray-600',
        progress: 'bg-emerald-500',
    },
    error: {
        bg: 'bg-white border-l-4 border-l-red-500',
        icon: 'text-red-500',
        title: 'text-gray-800',
        message: 'text-gray-500',
        close: 'text-gray-400 hover:text-gray-600',
        progress: 'bg-red-500',
    },
    warning: {
        bg: 'bg-white border-l-4 border-l-amber-500',
        icon: 'text-amber-500',
        title: 'text-gray-800',
        message: 'text-gray-500',
        close: 'text-gray-400 hover:text-gray-600',
        progress: 'bg-amber-500',
    },
    info: {
        bg: 'bg-white border-l-4 border-l-blue-500',
        icon: 'text-blue-500',
        title: 'text-gray-800',
        message: 'text-gray-500',
        close: 'text-gray-400 hover:text-gray-600',
        progress: 'bg-blue-500',
    },
}
</script>

<template>
    <!-- Teleport to body so it's always on top regardless of parent stacking context -->
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-80 pointer-events-none">
            <TransitionGroup enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-x-full opacity-0" enter-to-class="translate-x-0 opacity-100"
                leave-active-class="absolute transition duration-200 ease-in w-80"
                leave-from-class="translate-x-0 opacity-100" leave-to-class="translate-x-full opacity-0">
                <div v-for="toast in toasts" :key="toast.id"
                    :class="['pointer-events-auto rounded-lg shadow-lg overflow-hidden', colorMap[toast.type].bg]">
                    <div class="flex items-start gap-3 px-4 py-3">
                        <!-- Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" :class="['size-5 mt-0.5 shrink-0', colorMap[toast.type].icon]"
                            v-html="icons[toast.type]" />

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p :class="['text-sm font-semibold leading-snug', colorMap[toast.type].title]">
                                {{ toast.title }}
                            </p>
                            <p v-if="toast.message"
                                :class="['text-xs mt-0.5 leading-relaxed', colorMap[toast.type].message]">
                                {{ toast.message }}
                            </p>
                        </div>

                        <!-- Close -->
                        <button @click="dismiss(toast.id)"
                            :class="['shrink-0 mt-0.5 transition-colors', colorMap[toast.type].close]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Auto-dismiss progress bar -->
                    <div v-if="toast.duration && toast.duration > 0"
                        :class="['h-0.5 w-full origin-left', colorMap[toast.type].progress]" :style="{
                            animation: `shrink ${toast.duration}ms linear forwards`
                        }" />
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes shrink {
    from {
        transform: scaleX(1);
    }

    to {
        transform: scaleX(0);
    }
}
</style>