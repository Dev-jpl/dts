<script setup lang="ts">
import type { TeamRow } from '@/composables/useDashboard'

const props = defineProps<{
    data: TeamRow[]
    loading: boolean
}>()
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 text-violet-500">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <h2 class="font-semibold text-sm text-gray-700">Team Performance</h2>
            </div>
            <span class="text-xs text-gray-400">{{ data.length }} office{{ data.length !== 1 ? 's' : '' }}</span>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="divide-y divide-gray-100">
            <div v-for="i in 3" :key="i" class="px-4 py-3 animate-pulse flex gap-4">
                <div class="h-4 bg-gray-200 rounded w-40"></div>
                <div class="h-4 bg-gray-200 rounded w-16"></div>
                <div class="h-4 bg-gray-200 rounded w-16"></div>
                <div class="h-4 bg-gray-200 rounded w-16"></div>
                <div class="h-4 bg-gray-200 rounded w-16"></div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="data.length === 0" class="flex flex-col items-center justify-center py-10 text-gray-400">
            <p class="text-sm">No subordinate offices found</p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-4 py-2 font-semibold text-gray-500">Office</th>
                        <th class="px-4 py-2 font-semibold text-gray-500 text-center">Received</th>
                        <th class="px-4 py-2 font-semibold text-gray-500 text-center">On-Time Rate</th>
                        <th class="px-4 py-2 font-semibold text-gray-500 text-center">Avg Turnaround</th>
                        <th class="px-4 py-2 font-semibold text-gray-500 text-center">Overdue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in data" :key="row.office_id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ row.office_name }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ row.total_received }}</td>
                        <td class="px-4 py-3 text-center">
                            <span v-if="row.on_time_rate !== null"
                                :class="[
                                    row.on_time_rate >= 80 ? 'text-green-600' :
                                    row.on_time_rate >= 60 ? 'text-amber-600' : 'text-red-600',
                                    'font-semibold'
                                ]">
                                {{ row.on_time_rate }}%
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700">
                            {{ row.avg_turnaround !== null ? row.avg_turnaround + ' days' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span v-if="row.overdue_count > 0"
                                class="font-semibold text-red-600">
                                {{ row.overdue_count }}
                            </span>
                            <span v-else class="text-green-500">✓</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
