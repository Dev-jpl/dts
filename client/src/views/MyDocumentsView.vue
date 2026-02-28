<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import { useExpandableTextArray } from "@/composables/useExpandableTextArray";
import { useMyDocuments } from "@/composables/useMyDocuments";
import { ref, watch, onMounted, computed } from "vue";

// ── API ────────────────────────────────────────────────────────────────────────
const {
    documents,
    meta,
    isLoading,
    error,
    isEmpty,
    hasNextPage,
    hasPrevPage,
    filterOptions,
    filtersLoading,
    fetchMyDocuments,
    fetchFilterOptions,
} = useMyDocuments();

// ── Tabs ───────────────────────────────────────────────────────────────────────
const tabs = [
    { tabLabel: "All",       status: undefined },
    { tabLabel: "Active",    status: "Active" },
    { tabLabel: "Draft",     status: "Draft" },
    { tabLabel: "Returned",  status: "Returned" },
    { tabLabel: "Completed", status: "Completed" },
    { tabLabel: "Closed",    status: "Closed" },
];

const activeTab = ref(tabs[0].tabLabel);
const tabRefs = ref<HTMLElement[]>([]);
const indicatorStyle = ref({});

function updateIndicator() {
    const index = tabs.findIndex(t => t.tabLabel === activeTab.value);
    const el = tabRefs.value[index];
    if (el) {
        indicatorStyle.value = { left: el.offsetLeft + "px", width: el.offsetWidth + "px" };
    }
}

// ── Filter state ───────────────────────────────────────────────────────────────
const search = ref("");
const selectedDocType = ref("");
const selectedOfficeId = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const showFilters = ref(false);
const currentPage = ref(1);
const perPage = 15;

const hasActiveFilters = computed(() =>
    !!selectedDocType.value || !!selectedOfficeId.value || !!dateFrom.value || !!dateTo.value
);

// ── Fetch helpers ─────────────────────────────────────────────────────────────
function buildParams() {
    const tab = tabs.find(t => t.tabLabel === activeTab.value);
    return {
        status: tab?.status,
        search: search.value || undefined,
        document_type: selectedDocType.value || undefined,
        recipient_office_id: selectedOfficeId.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        page: currentPage.value,
        per_page: perPage,
    };
}

function doFetch() {
    fetchMyDocuments(buildParams());
}

// Reset to page 1 whenever any filter/tab changes, then fetch
function resetAndFetch() {
    currentPage.value = 1;
    doFetch();
}

function goToPage(page: number) {
    if (!meta.value) return;
    if (page < 1 || page > meta.value.last_page) return;
    currentPage.value = page;
    doFetch();
}

function clearFilters() {
    selectedDocType.value = "";
    selectedOfficeId.value = "";
    dateFrom.value = "";
    dateTo.value = "";
}

// Visible page numbers (up to 5 around current page)
const pageNumbers = computed(() => {
    if (!meta.value) return [];
    const total = meta.value.last_page;
    const current = meta.value.current_page;
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

// Debounce search; immediate for everything else
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(resetAndFetch, 400);
});

watch([activeTab, selectedDocType, selectedOfficeId, dateFrom, dateTo], () => {
    updateIndicator();
    resetAndFetch();
});

onMounted(() => {
    updateIndicator();
    fetchFilterOptions();
    doFetch();
});

// ── Expandable text ────────────────────────────────────────────────────────────
const { isExpanded, toggleExpanded, getDisplayText } = useExpandableTextArray(250);

// ── Display helpers ────────────────────────────────────────────────────────────
function formatDate(dateStr: string) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleDateString("en-PH", {
        month: "short", day: "numeric", year: "numeric",
    });
}

const statusStyle: Record<string, string> = {
    Draft:     "bg-gray-100 text-gray-500",
    Active:    "bg-blue-50 text-blue-600",
    Returned:  "bg-amber-50 text-amber-700",
    Completed: "bg-green-50 text-green-700",
    Closed:    "bg-gray-100 text-gray-500",
};

const routingStyle: Record<string, string> = {
    Single: "bg-teal-50 text-teal-700",
    Multiple: "bg-purple-50 text-purple-700",
    Sequential: "bg-indigo-50 text-indigo-700",
};

const transactionTypeStyle: Record<string, string> = {
    Default: "bg-gray-100 text-gray-500",
    Forward: "bg-orange-50 text-orange-600",
    Reply: "bg-green-50 text-green-600",
};
</script>

<template>
    <ScrollableContainer padding="0" rem="50px" background="white" class="bg-white">
        <div class="w-full">

            <!-- ── Header + Tabs ───────────────────────────────────────────── -->
            <div class="flex items-end justify-between w-full border-b border-gray-200">
                <div class="p-4">
                    <h1 class="font-bold text-gray-600">My Documents</h1>
                </div>
                <div class="relative flex pr-4">
                    <div v-for="(tab, index) in tabs" :key="tab.tabLabel"
                        :ref="el => { if (el) tabRefs[index] = el as HTMLElement }" @click="activeTab = tab.tabLabel"
                        class="px-2 mb-2 w-[80px] py-2 text-xs text-center cursor-pointer"
                        :class="activeTab === tab.tabLabel ? 'text-teal-700 font-bold' : 'text-gray-500'">
                        {{ tab.tabLabel }}
                    </div>
                    <div class="absolute bottom-0 h-1 transition-all duration-300 bg-amber-500 rounded-t-md"
                        :style="indicatorStyle" />
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
                    <input v-model="search" type="text" placeholder="Search subject, document no., type..."
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
                    <span v-if="hasActiveFilters"
                        class="flex items-center justify-center w-4 h-4 text-[9px] font-bold bg-white text-teal-700 rounded-full">
                        {{ [selectedDocType, selectedOfficeId, dateFrom || dateTo].filter(Boolean).length }}
                    </span>
                </button>

                <!-- Clear -->
                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="text-xs text-gray-400 transition hover:text-red-500">
                    Clear
                </button>

                <!-- Result count -->
                <span v-if="meta" class="ml-auto text-[11px] text-gray-400">
                    {{ meta.from }}–{{ meta.to }} of {{ meta.total }} document{{ meta.total !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- ── Filter Panel ─────────────────────────────────────────────── -->
            <div v-if="showFilters" class="grid grid-cols-4 gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50/40">

                <!-- Document Type -->
                <div>
                    <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                        Document Type
                    </label>
                    <select v-model="selectedDocType" :disabled="filtersLoading"
                        class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        <option value="">All types</option>
                        <option v-for="type in filterOptions.document_types" :key="type" :value="type">
                            {{ type }}
                        </option>
                    </select>
                </div>

                <!-- Recipient Office -->
                <div>
                    <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                        Recipient Office
                    </label>
                    <select v-model="selectedOfficeId" :disabled="filtersLoading"
                        class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        <option value="">All offices</option>
                        <option v-for="office in filterOptions.recipient_offices" :key="office.office_id"
                            :value="office.office_id">
                            {{ office.office_name }}
                        </option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                        Date From
                    </label>
                    <input v-model="dateFrom" type="date"
                        class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500" />
                </div>

                <!-- Date To -->
                <div>
                    <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                        Date To
                    </label>
                    <input v-model="dateTo" type="date" :min="dateFrom || undefined"
                        class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500" />
                </div>

            </div>

            <!-- ── Table Header ─────────────────────────────────────────────── -->
            <table class="w-full">
                <thead>
                    <tr class="sticky bg-gray-100">
                        <td width="55%" class="px-4 py-2 text-xs font-semibold">Subject</td>
                        <td width="20%" class="px-4 py-2 text-xs font-semibold">Recipients</td>
                        <td width="13%" class="px-4 py-2 text-xs font-semibold">Status</td>
                        <td width="12%" class="px-4 py-2 text-xs font-semibold text-right">Created</td>
                    </tr>
                </thead>
            </table>

            <!-- ── Table Body ───────────────────────────────────────────────── -->
            <div :class="showFilters ? 'h-[calc(100svh-20.7rem)]' : 'h-[calc(100svh-16rem)]'" class="overflow-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-200">

                        <tr v-if="isLoading">
                            <td colspan="4" class="px-4 py-10 text-xs text-center text-gray-400">
                                Loading documents...
                            </td>
                        </tr>

                        <tr v-else-if="error">
                            <td colspan="4" class="px-4 py-10 text-xs text-center text-red-400">
                                {{ error }}
                            </td>
                        </tr>

                        <tr v-else-if="isEmpty">
                            <td colspan="4" class="px-4 py-10 text-xs text-center text-gray-400">
                                No documents found.
                            </td>
                        </tr>

                        <tr v-else v-for="doc in documents" :key="doc.document_no"
                            class="hover:bg-gray-100 hover:cursor-pointer" @click="$router.push({
                                name: 'view-document',
                                params: { trxNo: doc.transactions[0]?.transaction_no },
                            })">
                            <!-- Subject -->
                            <td width="55%" class="p-3 align-top">
                                <div class="flex flex-wrap items-center gap-1 mb-1">
                                    <span class="text-xs font-semibold text-gray-700">{{ doc.document_type }}</span>
                                    <span class="text-gray-300">·</span>
                                    <span class="text-xs text-gray-400">{{ doc.action_type }}</span>
                                </div>
                                <div class="text-xs leading-relaxed text-gray-500">
                                    {{ getDisplayText(doc.document_no, doc.subject) }}
                                    <span v-if="doc.subject.length > 250"
                                        class="ml-1 text-teal-600 cursor-pointer hover:underline"
                                        @click.stop="toggleExpanded(doc.document_no)">
                                        {{ isExpanded(doc.document_no) ? "See less" : "See more" }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    <span v-if="doc.transactions[0]?.routing"
                                        class="px-1.5 py-0.5 text-[10px] font-medium rounded"
                                        :class="routingStyle[doc.transactions[0].routing] ?? 'bg-gray-100 text-gray-500'">
                                        {{ doc.transactions[0].routing }}
                                    </span>
                                    <span v-if="doc.transactions[0]?.transaction_type"
                                        class="px-1.5 py-0.5 text-[10px] font-medium rounded"
                                        :class="transactionTypeStyle[doc.transactions[0].transaction_type] ?? 'bg-gray-100 text-gray-500'">
                                        {{ doc.transactions[0].transaction_type }}
                                    </span>
                                </div>
                            </td>

                            <!-- Recipients -->
                            <td width="20%" class="p-3 text-xs align-top">
                                <template v-if="doc.recipients?.length">
                                    <div class="text-gray-600">{{ doc.recipients[0].office_name }}</div>
                                    <span v-if="doc.recipients.length > 1" class="text-[10px] text-gray-400">
                                        +{{ doc.recipients.length - 1 }} more
                                    </span>
                                </template>
                                <span v-else class="text-gray-300">—</span>
                            </td>

                            <!-- Status -->
                            <td width="13%" class="p-3 align-top">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full"
                                    :class="statusStyle[doc.status] ?? 'bg-gray-100 text-gray-500'">
                                    <span class="size-1.5 rounded-full inline-block" :class="{
                                        'bg-gray-400':  doc.status === 'Draft' || doc.status === 'Closed',
                                        'bg-blue-500':  doc.status === 'Active',
                                        'bg-amber-500': doc.status === 'Returned',
                                        'bg-green-500': doc.status === 'Completed',
                                    }" />
                                    {{ doc.status }}
                                </span>
                            </td>

                            <!-- Date -->
                            <td width="12%" class="p-3 text-xs font-light text-right text-gray-500 align-top">
                                {{ formatDate(doc.created_at) }}
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <!-- ── Pagination ───────────────────────────────────────────────── -->
            <div v-if="meta && meta.last_page > 1"
                class="flex items-center justify-between px-4 py-2 bg-white border-t border-gray-200">
                <!-- Left: showing X–Y of Z -->
                <span class="text-[11px] text-gray-400">
                    Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }} documents
                </span>

                <!-- Right: page controls -->
                <div class="flex items-center gap-1">

                    <!-- Prev -->
                    <button @click="goToPage(meta.current_page - 1)" :disabled="!hasPrevPage || isLoading"
                        class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7" :class="hasPrevPage && !isLoading
                            ? 'border-gray-200 text-gray-600 hover:bg-gray-100'
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
                        <span v-if="page === '...'"
                            class="flex items-center justify-center text-xs text-gray-400 w-7 h-7">
                            …
                        </span>
                        <button v-else @click="goToPage(page as number)" :disabled="isLoading"
                            class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7"
                            :class="page === meta.current_page
                                ? 'bg-teal-700 text-white border-teal-700'
                                : 'border-gray-200 text-gray-600 hover:bg-gray-100'">
                            {{ page }}
                        </button>
                    </template>

                    <!-- Next -->
                    <button @click="goToPage(meta.current_page + 1)" :disabled="!hasNextPage || isLoading"
                        class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7" :class="hasNextPage && !isLoading
                            ? 'border-gray-200 text-gray-600 hover:bg-gray-100'
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