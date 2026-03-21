<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import { useExpandableTextArray } from "@/composables/useExpandableTextArray";
import { ref, onMounted, computed } from "vue";
import api from "@/api";

// ── Types ──────────────────────────────────────────────────────────────────────
interface Recipient {
    id: number;
    office_id: string;
    office_name: string;
    recipient_type: string;
    isActive: boolean;
}

interface Transaction {
    transaction_no: string;
    document_no: string;
    subject: string;
    document_type: string;
    action_type: string;
    routing: string;
    office_name: string;
    created_by_name: string;
    created_at: string;
    status: string;
    recipients: Recipient[];
}

interface Stats {
    total_released: number;
    completed: number;
    processing: number;
    returned: number;
    total_recipients: number;
    recipients_received: number;
    period: { start_date: string; end_date: string };
}

// ── State ──────────────────────────────────────────────────────────────────────
const transactions = ref<Transaction[]>([]);
const stats = ref<Stats | null>(null);
const isLoading = ref(false);
const statsLoading = ref(false);
const error = ref<string | null>(null);
const exporting = ref(false);

// Date range filters
const today = new Date();
const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
const startDate = ref(firstOfMonth.toISOString().split('T')[0]);
const endDate = ref(today.toISOString().split('T')[0]);

const isEmpty = computed(() => transactions.value.length === 0);

// ── API Functions ──────────────────────────────────────────────────────────────
async function fetchReleasedDocuments() {
    isLoading.value = true;
    error.value = null;
    try {
        const res = await api.get('/documents/released', {
            params: { start_date: startDate.value, end_date: endDate.value }
        });
        transactions.value = res.data.data || res.data;
    } catch (e: any) {
        error.value = e.response?.data?.message || 'Failed to load documents';
    } finally {
        isLoading.value = false;
    }
}

async function fetchStats() {
    statsLoading.value = true;
    try {
        const res = await api.get('/documents/released/stats', {
            params: { start_date: startDate.value, end_date: endDate.value }
        });
        stats.value = res.data.data;
    } catch (e) {
        console.error('Failed to load stats', e);
    } finally {
        statsLoading.value = false;
    }
}

async function exportData() {
    exporting.value = true;
    try {
        const res = await api.get('/documents/released/export', {
            params: { start_date: startDate.value, end_date: endDate.value }
        });
        downloadCSV(res.data.data, `released-documents-${startDate.value}-to-${endDate.value}.csv`);
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
    fetchReleasedDocuments();
    fetchStats();
}

onMounted(() => {
    fetchReleasedDocuments();
    fetchStats();
});

// ── Expandable text ────────────────────────────────────────────────────────────
const { isExpanded, toggleExpanded, getDisplayText } = useExpandableTextArray(250);

// ── Helpers ────────────────────────────────────────────────────────────────────
function formatDate(dateStr: string) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

function getRecipientNames(recipients: Recipient[]): string {
    return recipients
        .filter(r => r.recipient_type === 'default')
        .map(r => r.office_name)
        .join(', ') || '—';
}

function getRecipientProgress(trx: Transaction): { received: number; total: number } {
    const defaultRecipients = trx.recipients?.filter(r => r.recipient_type === 'default') || [];
    const total = defaultRecipients.length;
    const received = defaultRecipients.filter(r => !r.isActive).length;
    return { received, total };
}
</script>

<template>
    <ScrollableContainer padding="0" px="50px" background="white" class="bg-white">
        <div class="w-full">

            <!-- ── Header ──────────────────────────────────────────────────── -->
            <div class="flex items-center justify-between w-full p-4 border-b border-gray-200">
                <div>
                    <h1 class="text-lg font-bold text-gray-700">Released Documents</h1>
                    <p class="text-xs text-gray-400">Documents released by your office</p>
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
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Total Released</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-teal-700">{{ stats?.total_released ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Completed</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-emerald-600">{{ stats?.completed ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Processing</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-blue-600">{{ stats?.processing ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Returned</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-orange-600">{{ stats?.returned ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Recipients Received</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-gray-700">
                        {{ stats?.recipients_received ?? 0 }}<span class="text-sm font-normal text-gray-400">/{{ stats?.total_recipients ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Table Header ─────────────────────────────────────────────── -->
            <table class="w-full">
                <thead>
                    <tr class="sticky bg-gray-100">
                        <td width="40%" class="px-4 py-2 text-xs font-semibold">Subject</td>
                        <td width="20%" class="px-4 py-2 text-xs font-semibold">Recipients</td>
                        <td width="10%" class="px-4 py-2 text-xs font-semibold text-center">Progress</td>
                        <td width="10%" class="px-4 py-2 text-xs font-semibold text-center">Status</td>
                        <td width="10%" class="px-4 py-2 text-xs font-semibold text-right">Released</td>
                    </tr>
                </thead>
            </table>

            <!-- ── Table Body ───────────────────────────────────────────────── -->
            <div class="h-[calc(100svh-340px)] overflow-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-200">

                        <!-- Loading state -->
                        <tr v-if="isLoading">
                            <td colspan="5" class="px-4 py-10 text-xs text-center text-gray-400">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-4 h-4 border-2 rounded-full border-teal-600 border-t-transparent animate-spin"></div>
                                    Loading documents...
                                </div>
                            </td>
                        </tr>

                        <!-- Error state -->
                        <tr v-else-if="error">
                            <td colspan="5" class="px-4 py-10 text-xs text-center text-red-400">{{ error }}</td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-else-if="isEmpty">
                            <td colspan="5" class="px-4 py-10 text-xs text-center text-gray-400">
                                No released documents for this period.
                            </td>
                        </tr>

                        <!-- Rows -->
                        <tr v-else v-for="trx in transactions" :key="trx.transaction_no"
                            class="hover:bg-gray-50 hover:cursor-pointer transition-colors" 
                            @click="$router.push({ name: 'view-document', params: { trxNo: trx.transaction_no } })">
                            
                            <!-- Subject -->
                            <td width="40%" class="p-3 align-top">
                                <div class="text-xs font-normal">
                                    <span class="font-bold">{{ trx.document_type }}</span> -
                                    {{ getDisplayText(trx.transaction_no, trx.subject) }}
                                    <span v-if="trx.subject.length > 250"
                                        class="ml-1 text-xs text-teal-700 cursor-pointer hover:underline"
                                        @click.stop="toggleExpanded(trx.transaction_no)">
                                        {{ isExpanded(trx.transaction_no) ? "See less" : "See more" }}
                                    </span>
                                </div>
                                <div class="mt-1 text-[10px] text-gray-400">
                                    {{ trx.action_type }} · {{ trx.routing }}
                                </div>
                            </td>

                            <!-- Recipients -->
                            <td width="20%" class="p-3 text-xs text-gray-600 align-top">
                                <div class="truncate max-w-[180px]" :title="getRecipientNames(trx.recipients)">
                                    {{ getRecipientNames(trx.recipients) }}
                                </div>
                            </td>

                            <!-- Progress -->
                            <td width="10%" class="p-3 text-center align-top">
                                <div class="flex items-center justify-center gap-1">
                                    <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full transition-all bg-teal-500 rounded-full"
                                            :style="{ width: `${getRecipientProgress(trx).total > 0 ? (getRecipientProgress(trx).received / getRecipientProgress(trx).total) * 100 : 0}%` }">
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-gray-500">
                                        {{ getRecipientProgress(trx).received }}/{{ getRecipientProgress(trx).total }}
                                    </span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td width="10%" class="p-3 text-center align-top">
                                <span :class="[
                                    'px-2 py-0.5 text-[10px] font-medium rounded-full',
                                    trx.status === 'Completed' ? 'bg-emerald-100 text-emerald-700' :
                                    trx.status === 'Processing' ? 'bg-blue-100 text-blue-700' :
                                    trx.status === 'Returned' ? 'bg-orange-100 text-orange-700' :
                                    'bg-gray-100 text-gray-600'
                                ]">{{ trx.status }}</span>
                            </td>

                            <!-- Date -->
                            <td width="10%" class="p-3 text-xs font-light text-right align-top">
                                {{ formatDate(trx.created_at) }}
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </div>
    </ScrollableContainer>
</template>