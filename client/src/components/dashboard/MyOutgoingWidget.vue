<script setup lang="ts">
import type { DashboardOutgoingItem } from '@/composables/useDashboard'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps<{
    data: { active: DashboardOutgoingItem[]; returned: DashboardOutgoingItem[]; completed: DashboardOutgoingItem[] }
    loading: boolean
}>()

const emit = defineEmits<{
    (e: 'close', docNo: string): void
}>()

const router = useRouter()

type Tab = 'active' | 'returned' | 'completed'
const activeTab = ref<Tab>('active')

const tabs: { key: Tab; label: string }[] = [
    { key: 'active',    label: 'Active' },
    { key: 'returned',  label: 'Returned' },
    { key: 'completed', label: 'Completed' },
]

function navigate(item: DashboardOutgoingItem) {
    router.push({ name: 'view-document', params: { trxNo: item.transactions?.[0]?.transaction_no ?? item.document_no } })
}

function currentItems(): DashboardOutgoingItem[] {
    return props.data[activeTab.value] ?? []
}

function formatDate(dateStr: string) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function statusDotClass(status: string): string {
    switch (status) {
        case 'Active':    return 'bg-green-500'
        case 'Returned':  return 'bg-amber-500'
        case 'Completed': return 'bg-blue-500'
        default:          return 'bg-gray-400'
    }
}
</script>

<template>
    <div class="overflow-hidden bg-white border border-gray-200 rounded-md shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="text-teal-600 size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <h2 class="text-sm font-semibold text-gray-700">My Outgoing</h2>
            </div>
            <!-- Tab bar -->
            <div class="flex overflow-hidden text-xs border border-gray-200 rounded-md">
                <button v-for="tab in tabs" :key="tab.key"
                    :class="[
                        'px-3 py-1 font-medium transition-colors',
                        activeTab === tab.key
                            ? 'bg-teal-700 text-white'
                            : 'bg-white text-gray-600 hover:bg-gray-50'
                    ]"
                    @click="activeTab = tab.key">
                    {{ tab.label }}
                    <span class="ml-1 text-[10px]">({{ props.data[tab.key]?.length ?? 0 }})</span>
                </button>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="divide-y divide-gray-100">
            <div v-for="i in 4" :key="i" class="flex items-center gap-3 px-4 py-3 animate-pulse">
                <div class="flex-1 h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded w-28"></div>
                <div class="w-20 h-4 bg-gray-200 rounded"></div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="currentItems().length === 0" class="flex flex-col items-center justify-center py-10 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                stroke="currentColor" class="mb-2 size-10">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm">No {{ activeTab }} documents</p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left bg-gray-50">
                        <th class="px-4 py-2 font-semibold text-gray-500 w-36">Document No</th>
                        <th class="px-4 py-2 font-semibold text-gray-500">Subject</th>
                        <th class="hidden w-24 px-4 py-2 font-semibold text-gray-500 sm:table-cell">Updated</th>
                        <th class="w-20 px-4 py-2 font-semibold text-center text-gray-500">Status</th>
                        <th v-if="activeTab === 'completed'" class="w-16 px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="item in currentItems()" :key="item.document_no"
                        class="transition-colors cursor-pointer hover:bg-gray-50"
                        @click="navigate(item)">
                        <td class="px-4 py-3 font-mono text-gray-600 truncate max-w-[140px]">
                            {{ item.document_no }}
                        </td>
                        <td class="px-4 py-3 max-w-[300px]">
                            <div class="text-gray-800 truncate">{{ item.subject }}</div>
                            <div class="text-[11px] text-gray-400">{{ item.document_type }}</div>
                        </td>
                        <td class="hidden px-4 py-3 text-gray-400 sm:table-cell">
                            {{ formatDate(item.updated_at) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1">
                                <span :class="[statusDotClass(item.status), 'size-2 rounded-full inline-block']"></span>
                                <span class="text-gray-600">{{ item.status }}</span>
                            </span>
                        </td>
                        <td v-if="activeTab === 'completed'" class="px-4 py-3 text-center">
                            <button
                                class="text-[10px] font-semibold px-2 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors"
                                @click.stop="emit('close', item.document_no)">
                                Close
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
