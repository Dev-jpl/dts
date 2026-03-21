<script setup lang="ts">
import type { DashboardForActionItem } from '@/composables/useDashboard'
import { useRouter } from 'vue-router'

const props = defineProps<{
    data: DashboardForActionItem[]
    loading: boolean
}>()

const emit = defineEmits<{
    (e: 'receive', trxNo: string): void
}>()

const router = useRouter()

function navigate(item: DashboardForActionItem) {
    router.push({ name: 'view-document', params: { trxNo: item.transaction_no } })
}

function urgencyChipClass(level: string | null | undefined): string {
    switch (level) {
        case 'Urgent':  return 'bg-red-100 text-red-700 border-red-300'
        case 'High':    return 'bg-orange-100 text-orange-700 border-orange-300'
        case 'Normal':  return 'bg-yellow-100 text-yellow-700 border-yellow-300'
        case 'Routine': return 'bg-green-100 text-green-700 border-green-300'
        default:        return 'bg-gray-100 text-gray-500 border-gray-300'
    }
}

function formatDate(dateStr: string) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
    <div class="overflow-hidden bg-white border border-gray-200 rounded-md shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 text-amber-500">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                <h2 class="text-sm font-semibold text-gray-700">For Action</h2>
            </div>
            <span class="text-xs font-semibold text-amber-600 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5">
                {{ data.length }}
            </span>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="divide-y divide-gray-100">
            <div v-for="i in 4" :key="i" class="flex items-center gap-3 px-4 py-3 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-14"></div>
                <div class="flex-1 h-4 bg-gray-200 rounded"></div>
                <div class="w-24 h-4 bg-gray-200 rounded"></div>
                <div class="w-20 bg-gray-200 rounded h-7"></div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="data.length === 0" class="flex flex-col items-center justify-center py-10 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                stroke="currentColor" class="mb-2 size-10">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm">No items requiring action</p>
        </div>

        <!-- List -->
        <div v-else class="divide-y divide-gray-100">
            <div v-for="item in data" :key="item.id"
                class="flex items-center gap-3 px-4 py-3 transition-colors cursor-pointer hover:bg-gray-50"
                @click="navigate(item)">

                <!-- Urgency chip -->
                <span :class="[urgencyChipClass(item.urgency_level), 'text-[10px] font-bold px-1.5 py-0.5 rounded border shrink-0']">
                    {{ item.urgency_level || 'High' }}
                </span>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-gray-800 truncate">
                        {{ item.transaction?.subject || item.document_no }}
                    </div>
                    <div class="text-[11px] text-gray-400 truncate">
                        {{ item.transaction?.document_type }} &middot; From: {{ item.transaction?.office_name || item.office_name }}
                    </div>
                </div>

                <!-- Date -->
                <div class="text-[11px] text-gray-400 shrink-0 hidden sm:block">
                    {{ formatDate(item.created_at) }}
                </div>

                <!-- Overdue badge -->
                <span v-if="item.is_overdue"
                    class="text-[10px] font-semibold text-red-600 bg-red-50 border border-red-200 rounded px-1.5 py-0.5 shrink-0">
                    Overdue
                </span>

                <!-- Receive button — stop propagation so row click doesn't also fire -->
                <button
                    class="shrink-0 text-[11px] font-semibold px-3 py-1.5 rounded-lg bg-teal-600 text-white hover:bg-teal-700 transition-colors"
                    @click.stop="emit('receive', item.transaction_no)">
                    Receive
                </button>
            </div>
        </div>
    </div>
</template>
