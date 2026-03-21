<script setup lang="ts">
import type { DashboardOverdueItem } from '@/composables/useDashboard'
import { useRouter } from 'vue-router'

const props = defineProps<{
    data: DashboardOverdueItem[]
    loading: boolean
}>()

const router = useRouter()

function navigate(item: DashboardOverdueItem) {
    router.push({ name: 'view-document', params: { trxNo: item.transaction_no } })
}

function urgencyClass(level: string | null | undefined): string {
    switch (level) {
        case 'Urgent':  return 'bg-red-100 text-red-700 border-red-200'
        case 'High':    return 'bg-orange-100 text-orange-700 border-orange-200'
        case 'Normal':  return 'bg-yellow-100 text-yellow-700 border-yellow-200'
        case 'Routine': return 'bg-gray-100 text-gray-600 border-gray-200'
        default:        return 'bg-gray-100 text-gray-500 border-gray-200'
    }
}
</script>

<template>
    <div v-if="loading || data.length > 0" class="overflow-hidden bg-white border border-red-200 rounded-md shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-red-200 bg-red-50">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="text-red-600 size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" />
                </svg>
                <h2 class="text-sm font-semibold text-red-700">Overdue</h2>
            </div>
            <span class="text-xs font-semibold text-red-600 bg-red-100 border border-red-200 rounded-full px-2 py-0.5">
                {{ data.length }}
            </span>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="divide-y divide-gray-100">
            <div v-for="i in 3" :key="i" class="flex gap-3 px-4 py-3 animate-pulse">
                <div class="w-24 h-4 bg-gray-200 rounded"></div>
                <div class="flex-1 h-4 bg-gray-200 rounded"></div>
                <div class="w-16 h-4 bg-gray-200 rounded"></div>
            </div>
        </div>

        <!-- Table -->
        <div v-else class="overflow-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left bg-gray-50">
                        <!-- <th class="px-4 py-2 font-semibold text-gray-500 w-36">Document No</th> -->
                        <th class="px-4 py-2 font-semibold text-gray-500">Subject</th>
                        <th class="px-4 py-2 font-semibold text-gray-500 w-28">Action Type</th>
                        <th class="w-24 px-4 py-2 font-semibold text-center text-gray-500">Days Overdue</th>
                        <th class="w-20 px-4 py-2 font-semibold text-center text-gray-500">Urgency</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="item in data" :key="item.id"
                        class="transition-colors cursor-pointer hover:bg-red-50"
                        @click="navigate(item)">
                        <!-- <td class="px-4 py-3 font-mono text-gray-600 truncate max-w-[140px]">
                            {{ item.document_no }}
                        </td> -->
                        <td class="px-4 py-3 text-gray-700 max-w-[300px]">
                            <div class="truncate">{{ item.transaction?.subject || '—' }}</div>
                            <div class="text-gray-400 text-[11px]">{{ item.transaction?.document_type }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 truncate">
                            {{ item.transaction?.action_type || '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-red-600">
                                {{ item.days_overdue }} day{{ item.days_overdue !== 1 ? 's' : '' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="[urgencyClass(item.transaction?.urgency_level), 'text-[10px] font-semibold px-1.5 py-0.5 rounded border']">
                                {{ item.transaction?.urgency_level || 'High' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
