<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useSearch } from "@/composables/useSearch";
import dayjs from "dayjs";

const router = useRouter();
const { quickSearch, quickResults, quickLoading } = useSearch();

// Modal state
const isModalOpen = ref(false);
const searchInput = ref("");
const inputRef = ref<HTMLInputElement | null>(null);

// Filter state
const filterStatus = ref("");
const filterDocType = ref("");

// Recently viewed documents (stored in localStorage)
interface RecentDocument {
    document_no: string;
    subject: string;
    status: string;
    document_type: string;
    created_at: string;
}

const RECENT_DOCS_KEY = 'dts_recent_documents';
const MAX_RECENT_DOCS = 5;
const recentDocuments = ref<RecentDocument[]>([]);

function loadRecentDocuments() {
    try {
        const stored = localStorage.getItem(RECENT_DOCS_KEY);
        recentDocuments.value = stored ? JSON.parse(stored) : [];
    } catch {
        recentDocuments.value = [];
    }
}

function saveRecentDocument(doc: RecentDocument) {
    const docs = recentDocuments.value.filter(d => d.document_no !== doc.document_no);
    docs.unshift(doc);
    recentDocuments.value = docs.slice(0, MAX_RECENT_DOCS);
    localStorage.setItem(RECENT_DOCS_KEY, JSON.stringify(recentDocuments.value));
}

function removeRecentDocument(docNo: string) {
    recentDocuments.value = recentDocuments.value.filter(d => d.document_no !== docNo);
    localStorage.setItem(RECENT_DOCS_KEY, JSON.stringify(recentDocuments.value));
}

function clearRecentDocuments() {
    recentDocuments.value = [];
    localStorage.removeItem(RECENT_DOCS_KEY);
}

// Debounce search
let debounceTimer: ReturnType<typeof setTimeout>;
watch(searchInput, (val) => {
    clearTimeout(debounceTimer);
    if (val.length >= 2) {
        debounceTimer = setTimeout(() => {
            quickSearch(val);
        }, 300);
    }
});

// Filtered results
const filteredResults = computed(() => {
    let results = quickResults.value;
    if (filterStatus.value) {
        results = results.filter(r => r.status === filterStatus.value);
    }
    if (filterDocType.value) {
        results = results.filter(r => r.document_type === filterDocType.value);
    }
    return results;
});

// Unique values for filters
const statusOptions = computed(() => {
    const statuses = new Set(quickResults.value.map(r => r.status));
    return Array.from(statuses);
});

const docTypeOptions = computed(() => {
    const types = new Set(quickResults.value.map(r => r.document_type));
    return Array.from(types);
});

const hasActiveFilters = computed(() => !!filterStatus.value || !!filterDocType.value);

function openModal() {
    isModalOpen.value = true;
    loadRecentDocuments();
    setTimeout(() => inputRef.value?.focus(), 100);
}

function closeModal() {
    isModalOpen.value = false;
    searchInput.value = "";
    filterStatus.value = "";
    filterDocType.value = "";
}

function clearFilters() {
    filterStatus.value = "";
    filterDocType.value = "";
}

// Handle result click
function viewDocument(docNo: string) {
    // Find the document from results and save it
    const doc = filteredResults.value.find(d => d.document_no === docNo);
    if (doc) {
        saveRecentDocument({
            document_no: doc.document_no,
            subject: doc.subject,
            status: doc.status,
            document_type: doc.document_type,
            created_at: doc.created_at
        });
    }
    closeModal();
    router.push({ name: "view-document", params: { trxNo: docNo } });
}

// View recent document
function viewRecentDocument(doc: RecentDocument) {
    // Re-save to move to top of recent list
    saveRecentDocument(doc);
    closeModal();
    router.push({ name: "view-document", params: { trxNo: doc.document_no } });
}

// Go to advanced search
function goToAdvancedSearch() {
    const query = searchInput.value;
    closeModal();
    router.push({ name: "advance-search", query: query ? { q: query } : {} });
}

// Keyboard shortcut (Cmd+K or Ctrl+K)
function handleKeydown(event: KeyboardEvent) {
    if ((event.metaKey || event.ctrlKey) && event.key === "k") {
        event.preventDefault();
        openModal();
    }
    if (event.key === "Escape" && isModalOpen.value) {
        closeModal();
    }
}

onMounted(() => {
    document.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleKeydown);
    clearTimeout(debounceTimer);
});

function formatDate(date: string) {
    return dayjs(date).format("MMM D, YYYY");
}

function getStatusClass(status: string) {
    switch (status) {
        case 'Draft': return 'bg-gray-100 text-gray-600';
        case 'Active': return 'bg-blue-100 text-blue-600';
        case 'Returned': return 'bg-amber-100 text-amber-600';
        case 'Completed': return 'bg-green-100 text-green-600';
        case 'Closed': return 'bg-purple-100 text-purple-600';
        default: return 'bg-gray-100 text-gray-600';
    }
}
</script>

<template>
<div>
    <!-- Search Trigger Button -->
    <button
        @click="openModal"
        class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-500 bg-gray-50 border border-gray-200 rounded-full hover:bg-gray-100 hover:border-gray-300 transition"
    >
        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span class="hidden sm:inline">Search...</span>
        <kbd class="hidden sm:inline-flex items-center gap-0.5 px-1.5 py-0.5 text-xs font-medium text-gray-400 bg-gray-100 rounded">
            <span class="text-xs">⌘</span>K
        </kbd>
    </button>

    <!-- Search Modal -->
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-start justify-center pt-[10vh] px-4"
            >
                <!-- Backdrop -->
                <div 
                    class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                    @click="closeModal"
                ></div>

                <!-- Modal Content -->
                <div class="relative w-full max-w-xl overflow-hidden bg-white shadow-2xl rounded-xl">
                    <!-- Search Input -->
                    <div class="relative border-b border-gray-200">
                        <svg
                            class="absolute w-5 h-5 text-gray-400 -translate-y-1/2 left-4 top-1/2"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="inputRef"
                            v-model="searchInput"
                            type="text"
                            placeholder="Search documents..."
                            class="w-full py-4 pl-12 pr-12 text-base border-0 focus:ring-0 focus:outline-none"
                            @keyup.enter="goToAdvancedSearch"
                        />
                        <!-- Loading / Close -->
                        <div class="absolute -translate-y-1/2 right-4 top-1/2">
                            <div v-if="quickLoading" class="w-5 h-5 border-2 border-teal-500 rounded-full animate-spin border-t-transparent"></div>
                            <button v-else @click="closeModal" class="p-1 text-gray-400 transition hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Results Area -->
                    <div class="max-h-[50vh] overflow-y-auto">
                        <!-- Results List -->
                        <div v-if="filteredResults.length > 0">
                            <button
                                v-for="doc in filteredResults"
                                :key="doc.document_no"
                                @click="viewDocument(doc.document_no)"
                                class="w-full px-4 py-3 text-left transition border-b border-gray-100 hover:bg-gray-50 last:border-b-0"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900">{{ doc.document_no }}</span>
                                    <span :class="[getStatusClass(doc.status), 'text-xs px-2 py-0.5 rounded-full']">
                                        {{ doc.status }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 truncate mt-0.5">{{ doc.subject }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-400">{{ doc.document_type }}</span>
                                    <span class="text-xs text-gray-300">•</span>
                                    <span class="text-xs text-gray-400">{{ formatDate(doc.created_at) }}</span>
                                </div>
                            </button>
                        </div>

                        <!-- No Results -->
                        <div v-else-if="searchInput.length >= 2 && !quickLoading" class="px-4 py-8 text-center">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-gray-500">No documents found</p>
                            <p class="mt-1 text-xs text-gray-400">Try different keywords</p>
                        </div>

                        <!-- Recently Viewed Documents (when input empty) -->
                        <div v-else-if="searchInput.length < 2 && recentDocuments.length > 0" class="py-2">
                            <div class="flex items-center justify-between px-4 py-2">
                                <span class="text-xs font-medium text-gray-500">Recently Viewed</span>
                                <button @click="clearRecentDocuments" class="text-xs text-gray-400 hover:text-gray-600">
                                    Clear all
                                </button>
                            </div>
                            <button
                                v-for="doc in recentDocuments"
                                :key="doc.document_no"
                                @click="viewRecentDocument(doc)"
                                class="w-full px-4 py-3 text-left transition border-b border-gray-100 hover:bg-gray-50 last:border-b-0 group"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-sm font-medium text-gray-900">{{ doc.document_no }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span :class="[getStatusClass(doc.status), 'text-xs px-2 py-0.5 rounded-full']">
                                            {{ doc.status }}
                                        </span>
                                        <button
                                            @click.stop="removeRecentDocument(doc.document_no)"
                                            class="p-1 text-gray-400 transition opacity-0 hover:text-gray-600 group-hover:opacity-100"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 truncate mt-0.5 pl-6">{{ doc.subject }}</p>
                                <div class="flex items-center gap-2 pl-6 mt-1">
                                    <span class="text-xs text-gray-400">{{ doc.document_type }}</span>
                                    <span class="text-xs text-gray-300">•</span>
                                    <span class="text-xs text-gray-400">{{ formatDate(doc.created_at) }}</span>
                                </div>
                            </button>
                        </div>

                        <!-- Empty State (no recent documents) -->
                        <div v-else-if="searchInput.length < 2" class="px-4 py-8 text-center">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="text-sm text-gray-500">Type to search documents</p>
                            <p class="mt-1 text-xs text-gray-400">Recently viewed documents will appear here</p>
                        </div>
                    </div>

                    <!-- Filters (show only when there are results) -->
                    <div v-if="quickResults.length > 0" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-gray-500">Filter:</span>
                            
                            <!-- Status Filter -->
                            <select
                                v-model="filterStatus"
                                class="px-2 py-1 text-xs bg-white border border-gray-200 rounded-md focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                            >
                                <option value="">All Status</option>
                                <option v-for="status in statusOptions" :key="status" :value="status">
                                    {{ status }}
                                </option>
                            </select>

                            <!-- Doc Type Filter -->
                            <select
                                v-model="filterDocType"
                                class="px-2 py-1 text-xs bg-white border border-gray-200 rounded-md focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                            >
                                <option value="">All Types</option>
                                <option v-for="type in docTypeOptions" :key="type" :value="type">
                                    {{ type }}
                                </option>
                            </select>

                            <!-- Clear Filters -->
                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="text-xs font-medium text-teal-600 hover:text-teal-700"
                            >
                                Clear
                            </button>

                            <!-- Results count -->
                            <span class="ml-auto text-xs text-gray-400">
                                {{ filteredResults.length }} result{{ filteredResults.length !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center gap-4 text-xs text-gray-400">
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 bg-gray-200 rounded text-[10px]">↵</kbd>
                                to search
                            </span>
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 bg-gray-200 rounded text-[10px]">esc</kbd>
                                to close
                            </span>
                        </div>
                        <button
                            @click="goToAdvancedSearch"
                            class="flex items-center gap-1 text-xs font-medium text-teal-600 hover:text-teal-700"
                        >
                            Advanced Search
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
