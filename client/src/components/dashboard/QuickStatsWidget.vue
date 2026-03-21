<script setup lang="ts">
import type { DashboardStats } from '@/composables/useDashboard'

const props = defineProps<{
    stats: DashboardStats | null
    loading: boolean
}>()

const emit = defineEmits<{
    (e: 'update:period', value: 'week' | 'month' | 'quarter' | 'year'): void
}>()

const modelPeriod = defineModel<'week' | 'month' | 'quarter' | 'year'>('period', { default: 'month' })

const periods: Array<{ key: 'week' | 'month' | 'quarter' | 'year'; label: string }> = [
    { key: 'week',    label: 'Week' },
    { key: 'month',   label: 'Month' },
    { key: 'quarter', label: 'Quarter' },
    { key: 'year',    label: 'Year' },
]

const statCards = [
    { key: 'sent',       label: 'Sent',       icon: 'send' },
    { key: 'received',   label: 'Received',   icon: 'inbox' },
    { key: 'for_action', label: 'For Action', icon: 'action' },
    { key: 'completed',  label: 'Completed',  icon: 'check' },
    { key: 'returned',   label: 'Returned',   icon: 'return', isWarning: true },
    { key: 'overdue',    label: 'Overdue',    icon: 'clock', isDanger: true },
]

function deltaIcon(delta: number | undefined): string {
    if (delta === undefined || delta === 0) return ''
    return delta > 0 ? '↑' : '↓'
}

function deltaClass(delta: number | undefined, key: string): string {
    if (delta === undefined || delta === 0) return 'text-gray-400'
    // For overdue and returned, going up is bad (red), going down is good (green)
    const inverseKeys = ['overdue', 'returned']
    const isInverse = inverseKeys.includes(key)
    if (isInverse) {
        return delta > 0 ? 'text-red-500' : 'text-emerald-500'
    }
    return delta > 0 ? 'text-emerald-500' : 'text-red-500'
}

function getValue(key: string): number {
    return (props.stats?.current as any)?.[key] ?? 0
}

function getDelta(key: string): number | undefined {
    return (props.stats?.delta as any)?.[key]
}
</script>

<template>
    <div class="overflow-hidden bg-white border border-gray-200 rounded-md shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-teal-800 to-teal-900">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="text-white/90 size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <h2 class="text-sm font-semibold text-white">Quick Stats</h2>
            </div>

            <!-- Period selector -->
            <div class="flex overflow-hidden text-xs bg-teal-800 rounded-lg backdrop-blur-sm">
                <button v-for="p in periods" :key="p.key"
                    :class="[
                        'px-2.5 py-1 font-medium transition-all',
                        modelPeriod === p.key
                            ? 'bg-teal-700 text-white shadow-sm'
                            : 'text-white/80 hover:text-white hover:bg-white/10'
                    ]"
                    @click="modelPeriod = p.key">
                    {{ p.label }}
                </button>
            </div>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-6 ">
            <div v-for="card in statCards" :key="card.key"
                :class="[
                    'rounded-lg p-3 border transition-all hover:shadow-sm cursor-default',
                    card.isDanger ? 'bg-red-50/50 border-red-100' :
                    card.isWarning ? 'bg-amber-50/50 border-amber-100' :
                    'bg-gray-50/50 border-gray-100'
                ]">
                <div class="flex items-center justify-between mb-2">
                    <span :class="[
                        'text-[11px] font-medium uppercase tracking-wide',
                        card.isDanger ? 'text-red-600' :
                        card.isWarning ? 'text-amber-600' :
                        'text-gray-500'
                    ]">{{ card.label }}</span>
                </div>

                <!-- Loading skeleton -->
                <div v-if="loading" class="bg-gray-200 rounded h-7 animate-pulse"></div>

                <div v-else>
                    <div :class="[
                        'text-2xl font-bold leading-none',
                        card.isDanger && getValue(card.key) > 0 ? 'text-red-600' :
                        card.isWarning && getValue(card.key) > 0 ? 'text-amber-600' :
                        'text-teal-700'
                    ]">
                        {{ getValue(card.key) }}
                    </div>
                    <div v-if="getDelta(card.key) !== undefined" 
                         :class="[deltaClass(getDelta(card.key), card.key), 'text-[11px] mt-1.5 font-medium flex items-center gap-1']">
                        <span>{{ deltaIcon(getDelta(card.key)) }}</span>
                        <span>{{ Math.abs(getDelta(card.key)!) }}%</span>
                        <span class="font-normal text-gray-400">vs prev</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
