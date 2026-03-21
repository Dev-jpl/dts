<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import api from "@/api";

// ── Types ──────────────────────────────────────────────────────────────────────
interface TransactionProgress {
    total_recipients: number;
    received: number;
    completed: number;
    pending: number;
}

interface LatestActivity {
    status: string;
    office_name: string;
    created_at: string;
}

interface Transaction {
    transaction_no: string;
    transaction_type: string;
    status: string;
    routing: string;
    urgency_level: string;
    recipient_names: string[];
    progress: TransactionProgress;
    latest_activity: LatestActivity | null;
}

interface OutgoingDocument {
    document_no: string;
    subject: string;
    document_type: string;
    action_type: string;
    status: string;
    updated_at: string;
    transactions: Transaction[];
}

interface Stats {
    active: number;
    returned: number;
    completed: number;
    closed: number;
    total_transactions: number;
}

// ── State ──────────────────────────────────────────────────────────────────────
const documents = ref<{ active: OutgoingDocument[]; returned: OutgoingDocument[]; completed: OutgoingDocument[] }>({
    active: [],
    returned: [],
    completed: []
});
const stats = ref<Stats | null>(null);
const isLoading = ref(false);
const statsLoading = ref(false);
const error = ref<string | null>(null);
const exporting = ref(false);
const expandedDocs = ref<Set<string>>(new Set());

// Tabs
type Tab = 'active' | 'returned' | 'completed';
const activeTab = ref<Tab>('active');
const tabs: { key: Tab; label: string }[] = [
    { key: 'active', label: 'Active' },
    { key: 'returned', label: 'Returned' },
    { key: 'completed', label: 'Completed' },
];

// Date range filters
const today = new Date();
const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
const startDate = ref(firstOfMonth.toISOString().split('T')[0]);
const endDate = ref(today.toISOString().split('T')[0]);

const router = useRouter();

const currentItems = computed(() => documents.value[activeTab.value] ?? []);
const isEmpty = computed(() => currentItems.value.length === 0);

// ── API Functions ──────────────────────────────────────────────────────────────
async function fetchOutgoingDocuments() {
    isLoading.value = true;
    error.value = null;
    try {
        // Fetch all 3 statuses in parallel
        const [activeRes, returnedRes, completedRes] = await Promise.all([
            api.get('/dashboard/outgoing', { params: { status: 'Active', per_page: 100 } }),
            api.get('/dashboard/outgoing', { params: { status: 'Returned', per_page: 100 } }),
            api.get('/dashboard/outgoing', { params: { status: 'Completed', per_page: 100 } }),
        ]);
        
        documents.value = {
            active: activeRes.data.data?.data || [],
            returned: returnedRes.data.data?.data || [],
            completed: completedRes.data.data?.data || [],
        };
    } catch (e: any) {
        error.value = e.response?.data?.message || 'Failed to load documents';
    } finally {
        isLoading.value = false;
    }
}

async function fetchStats() {
    statsLoading.value = true;
    try {
        const activeCount = documents.value.active.length;
        const returnedCount = documents.value.returned.length;
        const completedCount = documents.value.completed.length;
        const totalTrx = [...documents.value.active, ...documents.value.returned, ...documents.value.completed]
            .reduce((sum, doc) => sum + (doc.transactions?.length || 0), 0);
        
        stats.value = {
            active: activeCount,
            returned: returnedCount,
            completed: completedCount,
            closed: 0,
            total_transactions: totalTrx
        };
    } finally {
        statsLoading.value = false;
    }
}

async function exportData() {
    exporting.value = true;
    try {
        const allDocs = [...documents.value.active, ...documents.value.returned, ...documents.value.completed];
        const exportRows: any[] = [];
        
        allDocs.forEach(doc => {
            doc.transactions.forEach(trx => {
                exportRows.push({
                    document_no: doc.document_no,
                    subject: doc.subject,
                    document_type: doc.document_type,
                    action_type: doc.action_type,
                    document_status: doc.status,
                    transaction_no: trx.transaction_no,
                    transaction_type: trx.transaction_type,
                    routing: trx.routing,
                    urgency_level: trx.urgency_level,
                    transaction_status: trx.status,
                    total_recipients: trx.progress?.total_recipients || 0,
                    received: trx.progress?.received || 0,
                    completed: trx.progress?.completed || 0,
                    pending: trx.progress?.pending || 0,
                    recipients: trx.recipient_names?.join('; ') || '',
                    latest_activity: trx.latest_activity?.status || '',
                    latest_activity_by: trx.latest_activity?.office_name || '',
                    updated_at: doc.updated_at
                });
            });
        });
        
        downloadCSV(exportRows, `outgoing-documents-${startDate.value}-to-${endDate.value}.csv`);
    } catch (e) {
        console.error('Export failed', e);
    } finally {
        exporting.value = false;
    }
}

function downloadCSV(data: any[], filename: string) {
    if (!data.length) return;
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(h => `"${(row[h] ?? '').toString().replace(/"/g, '""')}"`).join(','))
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

function applyDateFilter() {
    fetchOutgoingDocuments().then(fetchStats);
}

onMounted(() => {
    fetchOutgoingDocuments().then(fetchStats);
});

// ── Helpers ────────────────────────────────────────────────────────────────────
function toggleExpand(docNo: string) {
    if (expandedDocs.value.has(docNo)) {
        expandedDocs.value.delete(docNo);
    } else {
        expandedDocs.value.add(docNo);
    }
}

function navigate(transactionNo: string) {
    router.push({ name: 'view-document', params: { trxNo: transactionNo } });
}

function formatDate(dateStr: string) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

function formatRelativeTime(dateStr: string) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return formatDate(dateStr);
}

function statusDotClass(status: string): string {
    switch (status) {
        case 'Active':
        case 'Processing': return 'bg-green-500';
        case 'Returned': return 'bg-amber-500';
        case 'Completed': return 'bg-blue-500';
        case 'Closed': return 'bg-gray-500';
        default: return 'bg-gray-400';
    }
}

function transactionTypeBadge(type: string): { bg: string; text: string } {
    switch (type?.toLowerCase()) {
        case 'forward': return { bg: 'bg-purple-100', text: 'text-purple-700' };
        case 'reply': return { bg: 'bg-cyan-100', text: 'text-cyan-700' };
        default: return { bg: 'bg-gray-100', text: 'text-gray-600' };
    }
}

function routingTypeBadge(routing: string): { bg: string; text: string } {
    switch (routing) {
        case 'Sequential': return { bg: 'bg-amber-100', text: 'text-amber-700' };
        case 'Multiple': return { bg: 'bg-teal-100', text: 'text-teal-700' };
        default: return { bg: 'bg-slate-100', text: 'text-slate-600' };
    }
}

function urgencyBadge(urgency: string): { bg: string; text: string } {
    switch (urgency) {
        case 'Urgent': return { bg: 'bg-red-100', text: 'text-red-700' };
        case 'High': return { bg: 'bg-orange-100', text: 'text-orange-700' };
        case 'Normal': return { bg: 'bg-blue-100', text: 'text-blue-700' };
        case 'Routine': return { bg: 'bg-gray-100', text: 'text-gray-600' };
        default: return { bg: 'bg-gray-100', text: 'text-gray-600' };
    }
}

function progressPercent(progress: TransactionProgress | null): number {
    if (!progress || progress.total_recipients === 0) return 0;
    return Math.round((progress.completed / progress.total_recipients) * 100);
}

function progressColor(progress: TransactionProgress | null): string {
    const pct = progressPercent(progress);
    if (pct === 100) return 'bg-green-500';
    if (pct >= 50) return 'bg-teal-500';
    return 'bg-amber-500';
}
</script>

<template>
    <ScrollableContainer padding="0" px="50px" background="white" class="bg-white">
        <div class="w-full">

            <!-- ── Header ──────────────────────────────────────────────────── -->
            <div class="flex items-center justify-between w-full p-4 border-b border-gray-200">
                <div>
                    <h1 class="text-lg font-bold text-gray-700">Outgoing Documents</h1>
                    <p class="text-xs text-gray-400">Track documents you've released with transaction-level progress</p>
                </div>

                <!-- Date Range & Export -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <input type="date" v-model="startDate" 
                            class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
                        <span class="text-xs text-gray-400">to</span>
                        <input type="date" v-model="endDate" 
                            class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
                        <button @click="applyDateFilter"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors">
                            Apply
                        </button>
                    </div>
                    <button @click="exportData" :disabled="exporting"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-teal-700 border border-teal-200 rounded-lg hover:bg-teal-50 transition-colors disabled:opacity-50">
                        <svg v-if="!exporting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span v-if="exporting">Exporting...</span>
                        <span v-else>Export CSV</span>
                    </button>
                </div>
            </div>

            <!-- ── Stats Bar ───────────────────────────────────────────────── -->
            <div class="grid grid-cols-5 gap-4 p-4 border-b border-gray-100 bg-gray-50/50">
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Active</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-green-600">{{ stats?.active ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Returned</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-amber-600">{{ stats?.returned ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Completed</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-blue-600">{{ stats?.completed ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Total Transactions</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-teal-700">{{ stats?.total_transactions ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Period</div>
                    <div class="mt-1 text-sm font-medium text-gray-600">
                        {{ formatDate(startDate) }} - {{ formatDate(endDate) }}
                    </div>
                </div>
            </div>

            <!-- ── Tabs ────────────────────────────────────────────────────── -->
            <div class="flex gap-1 px-4 pt-4 border-b border-gray-100">
                <button 
                    v-for="tab in tabs" 
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'px-4 py-2 text-xs font-medium rounded-t-lg transition-colors',
                        activeTab === tab.key 
                            ? 'bg-teal-700 text-white' 
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                >
                    {{ tab.label }}
                    <span class="ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full" 
                        :class="activeTab === tab.key ? 'bg-teal-600' : 'bg-gray-200'">
                        {{ documents[tab.key]?.length ?? 0 }}
                    </span>
                </button>
            </div>

            <!-- ── Content ─────────────────────────────────────────────────── -->
            <div class="h-[calc(100svh-380px)] overflow-y-auto">
                
                <!-- Loading state -->
                <div v-if="isLoading" class="flex items-center justify-center py-20">
                    <div class="flex items-center gap-2 text-gray-400">
                        <div class="w-4 h-4 border-2 rounded-full border-teal-600 border-t-transparent animate-spin"></div>
                        <span class="text-sm">Loading documents...</span>
                    </div>
                </div>

                <!-- Error state -->
                <div v-else-if="error" class="py-20 text-center text-red-400">
                    <p class="text-sm">{{ error }}</p>
                </div>

                <!-- Empty state -->
                <div v-else-if="isEmpty" class="flex flex-col items-center justify-center py-20 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                        stroke="currentColor" class="mb-2 size-12">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p class="text-sm">No {{ activeTab }} documents</p>
                </div>

                <!-- Documents list -->
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="doc in currentItems" :key="doc.document_no" class="group">
                        
                        <!-- Document row -->
                        <div 
                            class="flex items-center gap-4 px-4 py-4 transition-colors cursor-pointer hover:bg-gray-50"
                            @click="toggleExpand(doc.document_no)"
                        >
                            <!-- Expand chevron -->
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke-width="2" 
                                stroke="currentColor" 
                                :class="[
                                    'size-5 text-gray-400 transition-transform duration-200 shrink-0',
                                    expandedDocs.has(doc.document_no) ? 'rotate-90' : ''
                                ]"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>

                            <!-- Document info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-gray-500">{{ doc.document_no }}</span>
                                    <span :class="[statusDotClass(doc.status), 'size-2 rounded-full shrink-0']"></span>
                                    <span class="text-[10px] text-gray-400">{{ doc.status }}</span>
                                </div>
                                <div class="text-sm text-gray-800 truncate">{{ doc.subject }}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    {{ doc.document_type }} · {{ doc.action_type }}
                                </div>
                            </div>

                            <!-- Right side info -->
                            <div class="flex items-center gap-4 shrink-0">
                                <span class="text-[10px] px-2 py-1 rounded-full bg-gray-100 text-gray-600 font-medium">
                                    {{ doc.transactions?.length ?? 0 }} transaction{{ (doc.transactions?.length ?? 0) === 1 ? '' : 's' }}
                                </span>
                                <span class="text-xs text-gray-400 w-24 text-right">
                                    {{ formatDate(doc.updated_at) }}
                                </span>
                            </div>
                        </div>

                        <!-- Expanded transactions -->
                        <div 
                            v-if="expandedDocs.has(doc.document_no) && doc.transactions?.length"
                            class="px-4 pb-4 pl-12 space-y-3 bg-gray-50/50"
                        >
                            <div 
                                v-for="trx in doc.transactions" 
                                :key="trx.transaction_no"
                                class="p-4 transition-colors bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-teal-300 hover:shadow-sm"
                                @click.stop="navigate(trx.transaction_no)"
                            >
                                <!-- Transaction header -->
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="font-mono text-xs text-gray-500">{{ trx.transaction_no }}</span>
                                    <span :class="[
                                        transactionTypeBadge(trx.transaction_type).bg,
                                        transactionTypeBadge(trx.transaction_type).text,
                                        'text-[10px] px-2 py-0.5 rounded font-medium uppercase'
                                    ]">
                                        {{ trx.transaction_type || 'Default' }}
                                    </span>
                                    <span :class="[
                                        routingTypeBadge(trx.routing).bg,
                                        routingTypeBadge(trx.routing).text,
                                        'text-[10px] px-2 py-0.5 rounded font-medium'
                                    ]">
                                        {{ trx.routing }}
                                    </span>
                                    <span :class="[
                                        urgencyBadge(trx.urgency_level).bg,
                                        urgencyBadge(trx.urgency_level).text,
                                        'text-[10px] px-2 py-0.5 rounded font-medium'
                                    ]">
                                        {{ trx.urgency_level }}
                                    </span>
                                    <span :class="[statusDotClass(trx.status), 'size-2 rounded-full ml-auto']"></span>
                                    <span class="text-xs text-gray-500">{{ trx.status }}</span>
                                </div>

                                <!-- Progress bar -->
                                <div class="mb-3">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-xs text-gray-500">
                                            Completion Progress
                                        </span>
                                        <span class="text-xs font-medium text-gray-600">
                                            {{ trx.progress?.completed ?? 0 }} / {{ trx.progress?.total_recipients ?? 0 }} 
                                            <span class="text-gray-400">({{ progressPercent(trx.progress) }}%)</span>
                                        </span>
                                    </div>
                                    <div class="h-2 overflow-hidden bg-gray-200 rounded-full">
                                        <div 
                                            :class="[progressColor(trx.progress), 'h-full rounded-full transition-all duration-300']"
                                            :style="{ width: progressPercent(trx.progress) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between mt-1 text-[10px] text-gray-400">
                                        <span>Received: {{ trx.progress?.received ?? 0 }}</span>
                                        <span>Pending: {{ trx.progress?.pending ?? 0 }}</span>
                                    </div>
                                </div>

                                <!-- Recipients -->
                                <div v-if="trx.recipient_names?.length" class="flex items-start gap-2 mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mt-0.5 text-gray-400 size-4 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                    </svg>
                                    <div class="text-xs text-gray-600">
                                        {{ trx.recipient_names.join(', ') }}
                                    </div>
                                </div>

                                <!-- Latest activity -->
                                <div v-if="trx.latest_activity" class="flex items-center gap-2 pt-2 text-xs text-gray-400 border-t border-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span class="font-medium text-gray-500">{{ trx.latest_activity.status }}</span>
                                    <span>by {{ trx.latest_activity.office_name }}</span>
                                    <span class="ml-auto">{{ formatRelativeTime(trx.latest_activity.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </ScrollableContainer>
</template>
