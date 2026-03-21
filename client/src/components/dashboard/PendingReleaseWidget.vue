<script setup lang="ts">
import type { DashboardDraftItem } from '@/composables/useDashboard'
import { useRouter } from 'vue-router'
import BaseButton from '../ui/buttons/BaseButton.vue';

const props = defineProps<{
    data: DashboardDraftItem[]
    loading: boolean
}>()

const emit = defineEmits<{
    (e: 'release', trxNo: string): void
}>()

const router = useRouter()

function navigate(item: DashboardDraftItem) {
    router.push({ name: 'view-document', params: { trxNo: item.transaction_no } })
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
                    stroke="currentColor" class="text-blue-500 size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <h2 class="text-sm font-semibold text-gray-700">Pending Release</h2>
            </div>
            <span
                class="text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-full px-2 py-0.5">
                {{ data.length }}
            </span>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="divide-y divide-gray-100">
            <div v-for="i in 3" :key="i" class="flex items-center gap-3 px-4 py-3 animate-pulse">
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
            <p class="text-sm">No pending drafts</p>
        </div>

        <!-- List -->
        <div v-else class="divide-y divide-gray-100 overflow-auto max-h-[15rem]">
            <div v-for="item in data" :key="item.transaction_no"
                class="flex items-center gap-3 px-4 py-3 transition-colors cursor-pointer hover:bg-gray-50"
                @click="navigate(item)">

                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-gray-800 truncate">
                        {{ item.subject || '(No subject)' }}
                    </div>
                    <div class="text-[11px] text-gray-400">
                        {{ item.document_type }} &middot; {{ item.document_no }}
                    </div>
                </div>

                <div class="text-[11px] text-gray-400 shrink-0 hidden sm:block">
                    {{ formatDate(item.updated_at) }}
                </div>

                <!-- Release button -->
                <BaseButton
                    class="shrink-0 text-[11px] font-semibold px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                    @click.stop="emit('release', item.transaction_no)">
                    Release
                </BaseButton>
            </div>
        </div>
    </div>
</template>
