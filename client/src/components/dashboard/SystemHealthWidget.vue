<script setup lang="ts">
import type { SystemStats } from '@/composables/useDashboard'

const props = defineProps<{
    data: SystemStats | null
    loading: boolean
}>()
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-5 text-gray-600">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 6 0m-6 0H2.25m11.25-6a3 3 0 0 1 3 3m-3-3a3 3 0 1 0-6 0m6 0h2.25m-14.25 0H2.25m14.25 0a3 3 0 0 1 3 3m-3-3v-1.5m0 1.5v1.5m0 0h2.25m-14.25 0H2.25" />
            </svg>
            <h2 class="font-semibold text-sm text-gray-700">System Health</h2>
            <span class="ml-auto text-xs text-gray-400">Admin only</span>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="bg-gray-50 rounded-xl p-3 animate-pulse">
                <div class="h-3 bg-gray-200 rounded mb-2"></div>
                <div class="h-6 bg-gray-200 rounded"></div>
            </div>
        </div>

        <div v-else-if="!data" class="flex items-center justify-center py-10 text-gray-400 text-sm">
            No system data available
        </div>

        <div v-else class="p-4 space-y-4">
            <!-- Summary cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-xl p-3">
                    <div class="text-[11px] text-gray-500 font-medium mb-1">Total Processing</div>
                    <div class="text-2xl font-bold text-blue-700">{{ data.total_processing }}</div>
                </div>
                <div class="bg-red-50 rounded-xl p-3">
                    <div class="text-[11px] text-gray-500 font-medium mb-1">Total Overdue</div>
                    <div class="text-2xl font-bold text-red-700">{{ data.total_overdue }}</div>
                </div>
                <div class="bg-teal-50 rounded-xl p-3">
                    <div class="text-[11px] text-gray-500 font-medium mb-1">Today's Documents</div>
                    <div class="text-2xl font-bold text-teal-700">{{ data.total_documents_today }}</div>
                </div>
                <div class="bg-green-50 rounded-xl p-3">
                    <div class="text-[11px] text-gray-500 font-medium mb-1">30-Day Completion</div>
                    <div class="text-2xl font-bold text-green-700">{{ data.completion_rate_30d }}%</div>
                </div>
            </div>

            <!-- Top overdue offices -->
            <div v-if="data.top_overdue_offices.length > 0">
                <h3 class="text-xs font-semibold text-gray-500 mb-2">Top Overdue Offices</h3>
                <div class="space-y-1">
                    <div v-for="office in data.top_overdue_offices" :key="office.office_id"
                        class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg text-xs">
                        <span class="text-gray-700 font-medium">{{ office.office_name }}</span>
                        <span class="font-bold text-red-600">{{ office.overdue_count }} overdue</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
