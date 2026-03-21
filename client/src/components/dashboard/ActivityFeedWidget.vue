<script setup lang="ts">
import type { ActivityItem } from '@/composables/useDashboard'
import { useRouter } from 'vue-router'

const props = defineProps<{
    data: ActivityItem[]
    loading: boolean
}>()

const router = useRouter()

function navigate(item: ActivityItem) {
    if (item.transaction_no) {
        router.push({ name: 'view-document', params: { trxNo: item.transaction_no } })
    }
}

function iconForStatus(status: string) {
    switch (status) {
        case 'Released':          return { color: 'text-blue-500 bg-blue-50',   icon: 'send' }
        case 'Received':          return { color: 'text-green-500 bg-green-50', icon: 'inbox' }
        case 'Done':              return { color: 'text-teal-500 bg-teal-50',   icon: 'check' }
        case 'Forwarded':         return { color: 'text-purple-500 bg-purple-50', icon: 'forward' }
        case 'Returned To Sender':return { color: 'text-amber-500 bg-amber-50', icon: 'return' }
        case 'Closed':            return { color: 'text-gray-500 bg-gray-100',  icon: 'close' }
        default:                  return { color: 'text-gray-400 bg-gray-50',   icon: 'default' }
    }
}

function formatTimeAgo(dateStr: string): string {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const now = new Date()
    const diffMs = now.getTime() - d.getTime()
    const diffMin = Math.floor(diffMs / 60000)
    if (diffMin < 1)   return 'just now'
    if (diffMin < 60)  return `${diffMin}m ago`
    const diffHr = Math.floor(diffMin / 60)
    if (diffHr < 24)   return `${diffHr}h ago`
    const diffDay = Math.floor(diffHr / 24)
    if (diffDay < 7)   return `${diffDay}d ago`
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
}

function actionText(item: ActivityItem): string {
    const map: Record<string, string> = {
        'Released':           'Released to route',
        'Received':           'Received by office',
        'Done':               'Marked as Done',
        'Forwarded':          'Forwarded to another office',
        'Returned To Sender': 'Returned to sender',
        'Closed':             'Document closed',
        'Profiled':           'Document profiled',
    }
    return map[item.status] ?? item.status
}
</script>

<template>
    <div class="overflow-hidden bg-white border border-gray-200 rounded-md shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="text-gray-500 size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <h2 class="text-sm font-semibold text-gray-700">Activity Feed</h2>
            </div>
            <span class="text-xs text-gray-400">Last 20 actions</span>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="divide-y divide-gray-100">
            <div v-for="i in 5" :key="i" class="flex items-center gap-3 px-4 py-3 animate-pulse">
                <div class="bg-gray-200 rounded-full size-8 shrink-0"></div>
                <div class="flex-1">
                    <div class="h-3 bg-gray-200 rounded w-3/4 mb-1.5"></div>
                    <div class="w-1/2 h-3 bg-gray-200 rounded"></div>
                </div>
                <div class="w-12 h-3 bg-gray-200 rounded shrink-0"></div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="data.length === 0" class="flex flex-col items-center justify-center py-10 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                stroke="currentColor" class="mb-2 size-10">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm">No recent activity</p>
        </div>

        <!-- Feed list -->
        <div v-else class="overflow-auto divide-y divide-gray-100 max-h-96">
            <div v-for="item in data" :key="item.id"
                class="flex items-start gap-3 px-4 py-3 transition-colors cursor-pointer hover:bg-gray-50"
                @click="navigate(item)">

                <!-- Status icon -->
                <div :class="[iconForStatus(item.status).color, 'size-8 rounded-full flex items-center justify-center shrink-0 text-sm font-bold']">
                    <!-- Generic icon based on status group -->
                    <template v-if="iconForStatus(item.status).icon === 'send'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                    </template>
                    <template v-else-if="iconForStatus(item.status).icon === 'inbox'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m9 13.5 3 3m0 0 3-3m-3 3v-6m1.06-4.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                    </template>
                    <template v-else-if="iconForStatus(item.status).icon === 'check'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </template>
                    <template v-else>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 12h-15m0 0 6.75 6.75M4.5 12l6.75-6.75" />
                        </svg>
                    </template>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-gray-800 truncate">
                        {{ actionText(item) }}
                    </div>
                    <div class="text-[11px] text-gray-400 truncate">
                        {{ item.document_type }} &middot; {{ item.subject || item.document_no }}
                    </div>
                    <div class="text-[11px] text-gray-400 mt-0.5">
                        by <span class="text-gray-600">{{ item.office_name }}</span>
                    </div>
                </div>

                <!-- Time ago -->
                <span class="text-[11px] text-gray-400 shrink-0 mt-0.5">
                    {{ formatTimeAgo(item.created_at) }}
                </span>
            </div>
        </div>
    </div>
</template>
