<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import { useExpandableTextArray } from "@/composables/useExpandableTextArray";
import { ref, onMounted, computed, watch } from "vue";
import api from "@/api";

// ── Types ──────────────────────────────────────────────────────────────────────
interface Transaction {
    transaction_no: string;
    routing: string;
    action_type: string;
    recipients: { office_id: string; office_name: string; recipient_type: string }[];
}

interface ArchivedDoc {
    document_no: string;
    subject: string;
    document_type: string;
    action_type: string;
    office_id: string;
    office_name: string;
    created_by_name: string;
    status: string;
    created_at: string;
    updated_at: string;
    transactions: Transaction[];
}

interface Stats {
    total_archived: number;
    originated_by_me: number;
    received_by_me: number;
    document_types: number;
    bulk_closed: number;
    force_closed: number;
    period: { start_date: string; end_date: string };
}

interface Pagination {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
}

// ── State ──────────────────────────────────────────────────────────────────────
const documents = ref<ArchivedDoc[]>([]);
const stats = ref<Stats | null>(null);
const pagination = ref<Pagination | null>(null);
const isLoading = ref(false);
const statsLoading = ref(false);
const error = ref<string | null>(null);
const exporting = ref(false);

// Filters
const search = ref("");
const selectedDocType = ref("");
const showFilters = ref(false);

// Date range
const today = new Date();
const sixMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 6, 1);
const startDate = ref(sixMonthsAgo.toISOString().split('T')[0]);
const endDate = ref(today.toISOString().split('T')[0]);

const isEmpty = computed(() => documents.value.length === 0);
const hasActiveFilters = computed(() => !!selectedDocType.value || !!search.value);

const hasPrevPage = computed(() => (pagination.value?.current_page ?? 1) > 1);
const hasNextPage = computed(() => (pagination.value?.current_page ?? 1) < (pagination.value?.last_page ?? 1));

// ── API Functions ──────────────────────────────────────────────────────────────
async function fetchArchivedDocuments(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
        const res = await api.get('/documents/archived', {
            params: {
                start_date: startDate.value,
                end_date: endDate.value,
                search: search.value || undefined,
                document_type: selectedDocType.value || undefined,
                page,
                per_page: 15,
            }
        });
        documents.value = res.data.data;
        pagination.value = {
            current_page: res.data.current_page,
            last_page: res.data.last_page,
            from: res.data.from,
            to: res.data.to,
            total: res.data.total,
        };
    } catch (e: any) {
        error.value = e.response?.data?.message || 'Failed to load archived documents';
    } finally {
        isLoading.value = false;
    }
}

async function fetchStats() {
    statsLoading.value = true;
    try {
        const res = await api.get('/documents/archived/stats', {
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
        const res = await api.get('/documents/archived/export', {
            params: { start_date: startDate.value, end_date: endDate.value }
        });
        downloadCSV(res.data.data, `archived-documents-${startDate.value}-to-${endDate.value}.csv`);
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
    fetchArchivedDocuments(1);
    fetchStats();
}

function goToPage(page: number) {
    if (!pagination.value) return;
    if (page < 1 || page > pagination.value.last_page) return;
    fetchArchivedDocuments(page);
}

function clearFilters() {
    search.value = "";
    selectedDocType.value = "";
}

// Visible page numbers
const pageNumbers = computed(() => {
    if (!pagination.value) return [];
    const total = pagination.value.last_page;
    const current = pagination.value.current_page;
    const delta = 2;
    const pages: (number | '...')[] = [];
    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
            pages.push(i);
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...');
        }
    }
    return pages;
});

// Debounce search
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchArchivedDocuments(1), 400);
});

watch(selectedDocType, () => fetchArchivedDocuments(1));

onMounted(() => {
    fetchArchivedDocuments();
    fetchStats();
});

// ── Expandable text ────────────────────────────────────────────────────────────
const { isExpanded, toggleExpanded, getDisplayText } = useExpandableTextArray(250);

// ── Helpers ────────────────────────────────────────────────────────────────────
function formatDate(dateStr: string) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleDateString("en-PH", {
        month: "short", day: "numeric", year: "numeric",
    });
}

const routingStyle: Record<string, string> = {
    Single: "bg-teal-50 text-teal-700",
    Multiple: "bg-purple-50 text-purple-700",
    Sequential: "bg-indigo-50 text-indigo-700",
};
</script>

<template>
    <ScrollableContainer padding="0" px="50px" background="white" class="bg-white">
        <div class="w-full">

            <!-- ── Header ──────────────────────────────────────────────────── -->
            <div class="flex items-center justify-between w-full p-4 border-b border-gray-200">
                <div>
                    <h1 class="text-lg font-bold text-gray-700">Archived Documents</h1>
                    <p class="text-xs text-gray-400">Closed documents — read-only repository</p>
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
                        <svg v-if="!exporting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span v-if="exporting">Exporting...</span>
                        <span v-else>Export CSV</span>
                    </button>
                </div>
            </div>

            <!-- ── Stats Bar ───────────────────────────────────────────────── -->
            <div class="grid grid-cols-4 gap-4 p-4 border-b border-gray-100 bg-gray-50/50">
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Total Archived</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-gray-700">{{ stats?.total_archived ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Originated by Me
                    </div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-teal-700">{{ stats?.originated_by_me ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Received by Me</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-blue-600">{{ stats?.received_by_me ?? 0 }}</div>
                </div>
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Document Types</div>
                    <div v-if="statsLoading" class="w-12 h-6 mt-1 bg-gray-200 rounded animate-pulse"></div>
                    <div v-else class="mt-1 text-xl font-bold text-purple-600">{{ stats?.document_types ?? 0 }}</div>
                </div>
            </div>

            <!-- ── Search + Filter Toggle ──────────────────────────────────── -->
            <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-100 bg-gray-50/60">
                <!-- Search -->
                <div class="relative flex-1 max-w-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                        class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-gray-400 pointer-events-none">
                        <path fill-rule="evenodd"
                            d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
                            clip-rule="evenodd" />
                    </svg>
                    <input v-model="search" type="text"
                        placeholder="Search subject, document no., type..."
                        class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition" />
                </div>

                <!-- Filter toggle -->
                <button @click="showFilters = !showFilters"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs border rounded-lg transition" :class="showFilters || hasActiveFilters
                        ? 'bg-teal-700 text-white border-teal-700'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-teal-400 hover:text-teal-700'">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
                        <path fill-rule="evenodd"
                            d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z"
                            clip-rule="evenodd" />
                    </svg>
                    Filters
                </button>

                <!-- Clear -->
                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="text-xs text-gray-400 transition hover:text-red-500">
                    Clear
                </button>

                <!-- Result count -->
                <span v-if="pagination" class="ml-auto text-[11px] text-gray-400">
                    {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }} document{{
                        pagination.total !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- ── Filter Panel ─────────────────────────────────────────────── -->
            <div v-if="showFilters" class="grid grid-cols-4 gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50/40">
                <div>
                    <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                        Document Type
                    </label>
                    <input v-model="selectedDocType" type="text" placeholder="Filter by type..."
                        class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500" />
                </div>
            </div>

            <!-- ── Table Header ─────────────────────────────────────────────── -->
            <table class="w-full">
                <thead>
                    <tr class="sticky bg-gray-100">
                        <td width="15%" class="px-4 py-2 text-xs font-semibold">Origin</td>
                        <td width="45%" class="px-4 py-2 text-xs font-semibold">Subject</td>
                        <td width="15%" class="px-4 py-2 text-xs font-semibold">Recipients</td>
                        <td width="10%" class="px-4 py-2 text-xs font-semibold text-right">Created</td>
                        <td width="10%" class="px-4 py-2 text-xs font-semibold text-right">Closed</td>
                    </tr>
                </thead>
            </table>

            <!-- ── Table Body ───────────────────────────────────────────────── -->
            <div :class="showFilters ? 'h-[calc(100svh-26rem)]' : 'h-[calc(100svh-22rem)]'" class="overflow-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-200">

                        <!-- Loading -->
                        <tr v-if="isLoading">
                            <td colspan="5" class="px-4 py-10 text-xs text-center text-gray-400">
                                <div class="flex items-center justify-center gap-2">
                                    <div
                                        class="w-4 h-4 border-2 rounded-full border-teal-600 border-t-transparent animate-spin">
                                    </div>
                                    Loading archived documents...
                                </div>
                            </td>
                        </tr>

                        <!-- Error -->
                        <tr v-else-if="error">
                            <td colspan="5" class="px-4 py-10 text-xs text-center text-red-400">{{ error }}</td>
                        </tr>

                        <!-- Empty -->
                        <tr v-else-if="isEmpty">
                            <td colspan="5" class="px-4 py-10 text-xs text-center text-gray-400">
                                No archived documents found for this period.
                            </td>
                        </tr>

                        <!-- Rows -->
                        <tr v-else v-for="doc in documents" :key="doc.document_no"
                            class="transition-colors hover:bg-gray-50 hover:cursor-pointer"
                            @click="$router.push({ name: 'view-document', params: { trxNo: doc.transactions[0]?.transaction_no } })">

                            <!-- Origin -->
                            <td width="15%" class="p-3 text-xs align-top">
                                <div class="font-medium text-gray-700">{{ doc.office_name }}</div>
                                <div class="text-gray-400">{{ doc.created_by_name }}</div>
                            </td>

                            <!-- Subject -->
                            <td width="45%" class="p-3 align-top">
                                <div class="text-xs font-normal">
                                    <span class="font-bold">{{ doc.document_type }}</span>
                                    <span class="text-gray-400"> - </span>
                                    {{ getDisplayText(doc.document_no, doc.subject) }}
                                    <span v-if="doc.subject.length > 250"
                                        class="ml-1 text-xs text-teal-700 cursor-pointer hover:underline"
                                        @click.stop="toggleExpanded(doc.document_no)">
                                        {{ isExpanded(doc.document_no) ? "See less" : "See more" }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-500">
                                        <span class="inline-block rounded-full size-1.5 bg-gray-400"></span>
                                        Closed
                                    </span>
                                    <span v-if="doc.transactions[0]?.routing"
                                        class="px-1.5 py-0.5 text-[10px] font-medium rounded"
                                        :class="routingStyle[doc.transactions[0].routing] ?? 'bg-gray-100 text-gray-500'">
                                        {{ doc.transactions[0].routing }}
                                    </span>
                                </div>
                            </td>

                            <!-- Recipients -->
                            <td width="15%" class="p-3 text-xs text-gray-600 align-top">
                                <template v-if="doc.transactions[0]?.recipients?.length">
                                    <div>{{ doc.transactions[0].recipients.filter(r => r.recipient_type ===
                                        'default')[0]?.office_name ?? '—' }}</div>
                                    <span v-if="doc.transactions[0].recipients.filter(r => r.recipient_type === 'default').length > 1"
                                        class="text-[10px] text-gray-400">
                                        +{{ doc.transactions[0].recipients.filter(r => r.recipient_type ===
                                            'default').length - 1 }} more
                                    </span>
                                </template>
                                <span v-else class="text-gray-300">—</span>
                            </td>

                            <!-- Created -->
                            <td width="10%" class="p-3 text-xs font-light text-right text-gray-500 align-top">
                                {{ formatDate(doc.created_at) }}
                            </td>

                            <!-- Closed -->
                            <td width="10%" class="p-3 text-xs font-light text-right text-gray-500 align-top">
                                {{ formatDate(doc.updated_at) }}
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <!-- ── Pagination ───────────────────────────────────────────────── -->
            <div v-if="pagination && pagination.last_page > 1"
                class="flex items-center justify-between px-4 py-2 bg-white border-t border-gray-200">
                <span class="text-[11px] text-gray-400">
                    Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }} documents
                </span>

                <div class="flex items-center gap-1">
                    <!-- Prev -->
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="!hasPrevPage || isLoading"
                        class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7"
                        :class="hasPrevPage && !isLoading
                            ? 'border-gray-200 text-gray-600 hover:bg-gray-50'
                            : 'border-gray-100 text-gray-300 cursor-not-allowed'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            class="size-3.5">
                            <path fill-rule="evenodd"
                                d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Page numbers -->
                    <template v-for="(page, i) in pageNumbers" :key="i">
                        <span v-if="page === '...'" class="px-1 text-xs text-gray-400">…</span>
                        <button v-else @click="goToPage(page as number)" :disabled="isLoading"
                            class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7"
                            :class="page === pagination.current_page
                                ? 'border-teal-500 bg-teal-600 text-white font-semibold'
                                : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                            {{ page }}
                        </button>
                    </template>

                    <!-- Next -->
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="!hasNextPage || isLoading"
                        class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7"
                        :class="hasNextPage && !isLoading
                            ? 'border-gray-200 text-gray-600 hover:bg-gray-50'
                            : 'border-gray-100 text-gray-300 cursor-not-allowed'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            class="size-3.5">
                            <path fill-rule="evenodd"
                                d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </ScrollableContainer>
</template>
