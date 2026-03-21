<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import { ref, computed, onMounted, watch, nextTick, h } from "vue";
import { useRouter } from "vue-router";
import { useSearch, type SearchFilters, type SavedSearch } from "@/composables/useSearch";
import { useToast } from "@/composables/useToast";
import { useAuthStore } from "@/stores/auth";
import dayjs from "dayjs";

// ── Composables ────────────────────────────────────────────────────────────────
const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();
const {
    loading,
    results,
    savedSearches,
    filterOptions,
    error,
    filters,
    hasActiveFilters,
    search,
    clearFilters,
    fetchFilterOptions,
    fetchSavedSearches,
    saveSearch,
    deleteSavedSearch,
    applySavedSearch,
} = useSearch();

// ── Local State ────────────────────────────────────────────────────────────────
const showSaveModal = ref(false);
const saveSearchName = ref("");
const savingSearch = ref(false);
const sidebarTab = ref<'filters' | 'saved'>('filters');
const searchMode = ref<'filter' | 'smart'>('filter');

// ── Smart Chat State ───────────────────────────────────────────────────────────
interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
    isTyping?: boolean;
    documents?: Array<{ document_no: string; subject: string; status: string }>;
}

interface ChatSession {
    id: string;
    title: string;
    created_at: string;
    messages: ChatMessage[];
}

const chatInput = ref('');
const chatMessages = ref<ChatMessage[]>([]);
const chatHistory = ref<ChatSession[]>([]);
const activeChatId = ref<string | null>(null);
const isSending = ref(false);
const chatMessagesRef = ref<HTMLElement | null>(null);

const userInitials = computed(() => {
    const name = authStore.user?.name || 'U';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

// Suggested prompts for new chat
const suggestedPrompts = [
    {
        label: 'Documents pending action',
        description: 'Find documents awaiting my response',
        query: 'Show me all documents pending my action',
        icon: {
            render() {
                return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' })
                ]);
            }
        }
    },
    {
        label: 'Overdue documents',
        description: 'Documents past their due date',
        query: 'Show me overdue documents',
        icon: {
            render() {
                return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' })
                ]);
            }
        }
    },
    {
        label: 'Recent releases',
        description: 'Documents released this week',
        query: 'What documents were released this week?',
        icon: {
            render() {
                return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
                ]);
            }
        }
    },
    {
        label: 'Document summary',
        description: 'Get a quick overview of my documents',
        query: 'Give me a summary of my document activity',
        icon: {
            render() {
                return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' })
                ]);
            }
        }
    }
];

// Date helpers for chat history grouping
function isToday(dateStr: string): boolean {
    return dayjs(dateStr).isSame(dayjs(), 'day');
}

function isPastWeek(dateStr: string): boolean {
    const date = dayjs(dateStr);
    const now = dayjs();
    return !isToday(dateStr) && date.isAfter(now.subtract(7, 'day'));
}

function isOlder(dateStr: string): boolean {
    return dayjs(dateStr).isBefore(dayjs().subtract(7, 'day'));
}

// Chat methods
function startNewChat() {
    activeChatId.value = null;
    chatMessages.value = [];
    chatInput.value = '';
}

function loadChat(chatId: string) {
    const chat = chatHistory.value.find(c => c.id === chatId);
    if (chat) {
        activeChatId.value = chatId;
        chatMessages.value = [...chat.messages];
    }
}

function deleteChat(chatId: string) {
    chatHistory.value = chatHistory.value.filter(c => c.id !== chatId);
    if (activeChatId.value === chatId) {
        startNewChat();
    }
    toast.success('Chat deleted');
}

async function sendMessage(message: string) {
    if (!message.trim() || isSending.value) return;
    
    const userMessage = message.trim();
    chatInput.value = '';
    
    // Add user message
    chatMessages.value.push({
        role: 'user',
        content: userMessage
    });
    
    // Add typing indicator
    chatMessages.value.push({
        role: 'assistant',
        content: '',
        isTyping: true
    });
    
    // Scroll to bottom
    await nextTick();
    if (chatMessagesRef.value) {
        chatMessagesRef.value.scrollTop = chatMessagesRef.value.scrollHeight;
    }
    
    isSending.value = true;
    
    // Simulate AI response (replace with actual API call)
    setTimeout(() => {
        // Remove typing indicator
        chatMessages.value = chatMessages.value.filter(m => !m.isTyping);
        
        // Add mock response
        chatMessages.value.push({
            role: 'assistant',
            content: `I found some documents matching your query "${userMessage}". Here are the results:`,
            documents: [
                { document_no: 'DOC-2026-0042', subject: 'Request for Office Supplies Q1 2026', status: 'Active' },
                { document_no: 'DOC-2026-0039', subject: 'Monthly Performance Report - February', status: 'Completed' },
                { document_no: 'DOC-2026-0035', subject: 'Budget Proposal for New Equipment', status: 'Active' }
            ]
        });
        
        // Create or update chat session
        if (!activeChatId.value) {
            const newChat: ChatSession = {
                id: Date.now().toString(),
                title: userMessage.slice(0, 40) + (userMessage.length > 40 ? '...' : ''),
                created_at: new Date().toISOString(),
                messages: [...chatMessages.value]
            };
            chatHistory.value.unshift(newChat);
            activeChatId.value = newChat.id;
        } else {
            const chat = chatHistory.value.find(c => c.id === activeChatId.value);
            if (chat) {
                chat.messages = [...chatMessages.value];
            }
        }
        
        isSending.value = false;
        
        // Scroll to bottom
        nextTick(() => {
            if (chatMessagesRef.value) {
                chatMessagesRef.value.scrollTop = chatMessagesRef.value.scrollHeight;
            }
        });
    }, 1500);
}

function copyMessage(content: string) {
    navigator.clipboard.writeText(content);
    toast.success('Copied to clipboard');
}

function regenerateResponse(messageIndex: number) {
    // Find the previous user message and regenerate
    const previousMessages = chatMessages.value.slice(0, messageIndex);
    const lastUserMessage = previousMessages.reverse().find(m => m.role === 'user');
    if (lastUserMessage) {
        chatMessages.value = chatMessages.value.slice(0, messageIndex);
        sendMessage(lastUserMessage.content);
    }
}

// Collapsible filter sections
const expandedSections = ref<Record<string, boolean>>({
    basic: true,
    document: true,
    routing: false,
    dates: false,
    additional: false,
});

function toggleSection(key: string) {
    expandedSections.value[key] = !expandedSections.value[key];
}

// ── Methods ────────────────────────────────────────────────────────────────────
async function handleSearch() {
    await search(1);
}

async function handlePageChange(page: number) {
    await search(page);
}

function handleClearFilters() {
    clearFilters();
    results.value = null;
}

async function handleSaveSearch() {
    if (!saveSearchName.value.trim()) {
        toast.warning("Please enter a name for your search");
        return;
    }
    savingSearch.value = true;
    try {
        await saveSearch(saveSearchName.value.trim());
        toast.success("Search saved successfully");
        showSaveModal.value = false;
        saveSearchName.value = "";
    } catch (e: any) {
        toast.error(e.response?.data?.message || "Failed to save search");
    } finally {
        savingSearch.value = false;
    }
}

async function handleDeleteSavedSearch(saved: SavedSearch) {
    if (!confirm(`Delete saved search "${saved.name}"?`)) return;
    try {
        await deleteSavedSearch(saved.id);
        toast.success("Saved search deleted");
    } catch (e: any) {
        toast.error("Failed to delete saved search");
    }
}

function handleApplySavedSearch(saved: SavedSearch) {
    applySavedSearch(saved);
    handleSearch();
}

function viewDocument(docNo: string) {
    router.push({ name: "view-document", params: { docNo } });
}

function formatDate(date: string) {
    return dayjs(date).format("MMM D, YYYY");
}

function getStatusClass(status: string) {
    switch (status) {
        case 'Draft': return 'bg-gray-100 text-gray-600';
        case 'Active': return 'bg-blue-50 text-blue-700';
        case 'Returned': return 'bg-amber-50 text-amber-700';
        case 'Completed': return 'bg-emerald-50 text-emerald-700';
        case 'Closed': return 'bg-slate-100 text-slate-600';
        default: return 'bg-gray-100 text-gray-600';
    }
}

// ── Active Filter Tags ─────────────────────────────────────────────────────────
const activeFilterTags = computed(() => {
    const tags: { key: keyof SearchFilters; label: string; value: string }[] = [];
    
    if (filters.q) tags.push({ key: 'q', label: 'Search', value: filters.q });
    if (filters.document_no) tags.push({ key: 'document_no', label: 'Doc #', value: filters.document_no });
    if (filters.status) tags.push({ key: 'status', label: 'Status', value: filters.status });
    if (filters.document_type) tags.push({ key: 'document_type', label: 'Type', value: filters.document_type });
    if (filters.action_type) tags.push({ key: 'action_type', label: 'Action', value: filters.action_type });
    if (filters.origin_type) tags.push({ key: 'origin_type', label: 'Origin Type', value: filters.origin_type });
    if (filters.sender) tags.push({ key: 'sender', label: 'Sender', value: filters.sender });
    if (filters.sender_office) tags.push({ key: 'sender_office', label: 'Sender Office', value: filters.sender_office });
    if (filters.sender_email) tags.push({ key: 'sender_email', label: 'Sender Email', value: filters.sender_email });
    if (filters.origin_office) {
        const office = filterOptions.value?.offices?.find(o => o.office_id === filters.origin_office);
        tags.push({ key: 'origin_office', label: 'Origin', value: office?.office_name || filters.origin_office });
    }
    if (filters.recipient_office) {
        const office = filterOptions.value?.offices?.find(o => o.office_id === filters.recipient_office);
        tags.push({ key: 'recipient_office', label: 'Recipient', value: office?.office_name || filters.recipient_office });
    }
    if (filters.routing_type) tags.push({ key: 'routing_type', label: 'Routing', value: filters.routing_type });
    if (filters.urgency_level) tags.push({ key: 'urgency_level', label: 'Urgency', value: filters.urgency_level });
    if (filters.released_from) tags.push({ key: 'released_from', label: 'From', value: dayjs(filters.released_from).format('MMM D, YYYY') });
    if (filters.released_to) tags.push({ key: 'released_to', label: 'To', value: dayjs(filters.released_to).format('MMM D, YYYY') });
    if (filters.overdue_only) tags.push({ key: 'overdue_only', label: 'Overdue', value: 'Yes' });
    if (filters.has_attachments) tags.push({ key: 'has_attachments', label: 'Attachments', value: 'Yes' });
    if (filters.has_notes) tags.push({ key: 'has_notes', label: 'Notes', value: 'Yes' });
    if (filters.has_returned) tags.push({ key: 'has_returned', label: 'Returned', value: 'Yes' });
    
    return tags;
});

function removeFilter(key: keyof SearchFilters) {
    if (typeof filters[key] === 'boolean') {
        (filters as any)[key] = false;
    } else {
        (filters as any)[key] = '';
    }
}

// ── Lifecycle ──────────────────────────────────────────────────────────────────
onMounted(async () => {
    await Promise.all([
        fetchFilterOptions(),
        fetchSavedSearches(),
    ]);
});
</script>

<template>
    <div class="w-full bg-gray-50">
        <!-- Top Header Bar -->
        <div class="px-6 py-4 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Advanced Search</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Find documents using filters</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Search Mode Tabs -->
                    <div class="flex p-1 bg-gray-100 rounded-lg">
                        <button
                            @click="searchMode = 'filter'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded-md transition',
                                searchMode === 'filter'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Filter Search
                        </button>
                        <button
                            @click="searchMode = 'smart'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded-md transition flex items-center gap-1.5',
                                searchMode === 'smart'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Smart Chat
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Search Mode -->
        <div v-show="searchMode === 'filter'" class="flex">
            <!-- Left Sidebar -->
            <aside class="flex flex-col flex-shrink-0 bg-white border-r border-gray-200 w-72" style="height: calc(100vh - 130px)">
                <!-- Tabs -->
                <div class="flex border-b border-gray-200">
                    <button
                        @click="sidebarTab = 'filters'"
                        :class="[
                            'flex-1 px-4 py-2.5 text-xs font-medium transition',
                            sidebarTab === 'filters'
                                ? 'text-teal-700 border-b-2 border-teal-600'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Filters
                    </button>
                    <button
                        @click="sidebarTab = 'saved'"
                        :class="[
                            'flex-1 px-4 py-2.5 text-xs font-medium transition relative',
                            sidebarTab === 'saved'
                                ? 'text-teal-700 border-b-2 border-teal-600'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Saved
                        <span v-if="savedSearches.length > 0" class="ml-1.5 px-1.5 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                            {{ savedSearches.length }}
                        </span>
                    </button>
                </div>

                <!-- Filters Tab Content -->
                <div v-show="sidebarTab === 'filters'" class="flex flex-col flex-1 overflow-hidden">
                    <!-- Scrollable Filter Content -->
                    <div class="flex-1 p-4 space-y-1 overflow-y-auto">
                        <!-- Quick Search Input -->
                        <div class="pb-3 mb-2 border-b border-gray-100">
                            <label class="block mb-1.5 text-xs font-medium text-gray-600">Search</label>
                            <div class="relative">
                                <input
                                    v-model="filters.q"
                                    type="text"
                                    placeholder="Keywords..."
                                    class="w-full py-1.5 pl-8 pr-3 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    @keyup.enter="handleSearch"
                                />
                                <svg class="absolute left-2.5 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    
                        <!-- BASIC FILTERS -->
                        <div class="pb-3 border-b border-gray-100">
                            <button
                                @click="toggleSection('basic')"
                                class="flex items-center justify-between w-full py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase hover:text-gray-700"
                            >
                                Basic
                                <svg :class="['h-4 w-4 transition-transform', expandedSections.basic ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="expandedSections.basic" class="pt-2 space-y-3">
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Document Number</label>
                                    <input
                                        v-model="filters.document_no"
                                        type="text"
                                        placeholder="e.g. DOC-2026-001"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Status</label>
                                    <select
                                        v-model="filters.status"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">All</option>
                                        <option v-for="status in filterOptions?.statuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DOCUMENT DETAILS -->
                        <div class="pb-3 border-b border-gray-100">
                            <button
                                @click="toggleSection('document')"
                                class="flex items-center justify-between w-full py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase hover:text-gray-700"
                            >
                                Document Details
                                <svg :class="['h-4 w-4 transition-transform', expandedSections.document ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="expandedSections.document" class="pt-2 space-y-3">
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Document Type</label>
                                    <select
                                        v-model="filters.document_type"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">All</option>
                                        <option v-for="dtype in filterOptions?.document_types" :key="dtype" :value="dtype">{{ dtype }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Action Type</label>
                                    <select
                                        v-model="filters.action_type"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">All</option>
                                        <option v-for="action in filterOptions?.action_types" :key="action" :value="action">{{ action }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Origin Type</label>
                                    <select
                                        v-model="filters.origin_type"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">All</option>
                                        <option v-for="ot in filterOptions?.origin_types" :key="ot" :value="ot">{{ ot }}</option>
                                    </select>
                                </div>
                                <!-- External sender fields (only show when origin_type is External) -->
                                <template v-if="filters.origin_type === 'External'">
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">Sender Name</label>
                                        <input
                                            v-model="filters.sender"
                                            type="text"
                                            placeholder="Search by sender name"
                                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">Sender Office</label>
                                        <input
                                            v-model="filters.sender_office"
                                            type="text"
                                            placeholder="Search by sender office"
                                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-600">Sender Email</label>
                                        <input
                                            v-model="filters.sender_email"
                                            type="text"
                                            placeholder="Search by sender email"
                                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- ROUTING -->
                        <div class="pb-3 border-b border-gray-100">
                            <button
                                @click="toggleSection('routing')"
                                class="flex items-center justify-between w-full py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase hover:text-gray-700"
                            >
                                Routing
                                <svg :class="['h-4 w-4 transition-transform', expandedSections.routing ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="expandedSections.routing" class="pt-2 space-y-3">
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Origin Office</label>
                                    <select
                                        v-model="filters.origin_office"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">All</option>
                                        <option v-for="office in filterOptions?.offices" :key="office.office_id" :value="office.office_id">{{ office.office_name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Recipient Office</label>
                                    <select
                                        v-model="filters.recipient_office"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">All</option>
                                        <option v-for="office in filterOptions?.offices" :key="office.office_id" :value="office.office_id">{{ office.office_name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Routing Type</label>
                                    <select
                                        v-model="filters.routing_type"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">All</option>
                                        <option v-for="rt in filterOptions?.routing_types" :key="rt" :value="rt">{{ rt }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Urgency</label>
                                    <select
                                        v-model="filters.urgency_level"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    >
                                        <option value="">All</option>
                                        <option v-for="ul in filterOptions?.urgency_levels" :key="ul" :value="ul">{{ ul }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DATE RANGE -->
                        <div class="pb-3 border-b border-gray-100">
                            <button
                                @click="toggleSection('dates')"
                                class="flex items-center justify-between w-full py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase hover:text-gray-700"
                            >
                                Date Range
                                <svg :class="['h-4 w-4 transition-transform', expandedSections.dates ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="expandedSections.dates" class="pt-2 space-y-3">
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Released From</label>
                                    <input
                                        v-model="filters.released_from"
                                        type="date"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Released To</label>
                                    <input
                                        v-model="filters.released_to"
                                        type="date"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- ADDITIONAL FILTERS -->
                        <div class="pb-3 border-b border-gray-100">
                            <button
                                @click="toggleSection('additional')"
                                class="flex items-center justify-between w-full py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase hover:text-gray-700"
                            >
                                Additional
                                <svg :class="['h-4 w-4 transition-transform', expandedSections.additional ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="expandedSections.additional" class="pt-2 space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="filters.overdue_only" type="checkbox" class="w-4 h-4 text-teal-600 border-gray-300 rounded" />
                                    <span class="text-xs text-gray-700">Overdue Only</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="filters.has_attachments" type="checkbox" class="w-4 h-4 text-teal-600 border-gray-300 rounded" />
                                    <span class="text-xs text-gray-700">Has Attachments</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="filters.has_notes" type="checkbox" class="w-4 h-4 text-teal-600 border-gray-300 rounded" />
                                    <span class="text-xs text-gray-700">Has Notes</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="filters.has_returned" type="checkbox" class="w-4 h-4 text-teal-600 border-gray-300 rounded" />
                                    <span class="text-xs text-gray-700">Has Been Returned</span>
                                </label>
                            </div>
                        </div>

                        <!-- SORT -->
                        <div class="pb-3">
                            <label class="block py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                            >
                                <option value="relevance">Relevance</option>
                                <option value="date">Date (Newest)</option>
                                <option value="status">Status</option>
                                <option value="urgency">Urgency</option>
                                <option value="overdue">Overdue First</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sticky Search Button -->
                    <div class="p-4 bg-white border-t border-gray-200">
                        <button
                            @click="handleSearch"
                            :disabled="loading"
                            class="w-full px-4 py-2.5 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            {{ loading ? 'Searching...' : 'Search' }}
                        </button>
                    </div>
                </div>

                <!-- Saved Searches Tab Content -->
                <div v-show="sidebarTab === 'saved'" class="flex-1 p-4 overflow-y-auto">
                    <div v-if="savedSearches.length === 0" class="py-8 text-center">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <p class="text-xs text-gray-500">No saved searches yet</p>
                        <p class="mt-1 text-xs text-gray-400">Set filters and click "Save Search"</p>
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="saved in savedSearches"
                            :key="saved.id"
                            class="p-3 transition border border-gray-200 rounded-lg cursor-pointer group hover:border-teal-300 hover:bg-teal-50/50"
                            @click="handleApplySavedSearch(saved)"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-800 truncate">{{ saved.name }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        {{ formatDate(saved.created_at) }}
                                    </p>
                                </div>
                                <button
                                    @click.stop="handleDeleteSavedSearch(saved)"
                                    class="p-1 text-gray-400 transition rounded opacity-0 hover:text-red-500 hover:bg-red-50 group-hover:opacity-100"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content: Results -->
            <main class="flex-1 p-6 overflow-y-auto" style="height: calc(100vh - 130px)">
                <!-- Active Filter Tags -->
                <div v-if="activeFilterTags.length > 0" class="flex items-start justify-between gap-4 pb-4 mb-4 border-b border-gray-100">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs text-gray-500">Filters:</span>
                        <span
                            v-for="tag in activeFilterTags"
                            :key="tag.key"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded group"
                        >
                            <span class="text-gray-400">{{ tag.label }}:</span>
                            <span class="truncate max-w-32">{{ tag.value }}</span>
                            <button
                                @click="removeFilter(tag.key)"
                                class="ml-0.5 text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </div>
                    <div class="flex items-center flex-shrink-0 gap-2">
                        <button
                            @click="handleClearFilters"
                            class="px-2.5 py-1 text-xs text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded transition"
                        >
                            Clear All
                        </button>
                        <button
                            @click="showSaveModal = true"
                            class="flex items-center gap-1 shadow px-2.5 py-1 bg-green-50 text-xs text-teal-700 border border-gray-200 rounded hover:bg-green-50 transition"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                            Save
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="flex items-center justify-center py-20">
                    <div class="w-8 h-8 border-2 border-teal-600 rounded-full animate-spin border-t-transparent"></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="py-20 text-center">
                    <p class="text-xs text-red-600">{{ error }}</p>
                </div>

                <!-- Empty State (before search) -->
                <div v-else-if="!results" class="py-20 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-600">Start searching</h3>
                    <p class="mt-1 text-xs text-gray-400">Use filters on the left or type a search term</p>
                </div>

                <!-- No Results -->
                <div v-else-if="results.data.length === 0" class="py-20 text-center">
                    <h3 class="text-xs font-medium text-gray-600">No documents found</h3>
                    <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
                </div>

                <!-- Results -->
                <div v-else>
                    <!-- Results Header -->
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs text-gray-600">
                            <span class="font-medium">{{ results.total }}</span> documents found
                        </p>
                    </div>

                    <!-- Results Table -->
                    <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Document</th>
                                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Type / Action</th>
                                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Origin</th>
                                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Routing</th>
                                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Urgency</th>
                                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Date</th>
                                    <th class="w-10 px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="doc in results.data"
                                    :key="doc.document_no"
                                    class="cursor-pointer hover:bg-gray-50"
                                    @click="viewDocument(doc.document_no)"
                                >
                                    <td class="px-4 py-3">
                                        <div class="text-xs font-medium text-gray-900">{{ doc.document_no }}</div>
                                        <div class="max-w-xs text-xs text-gray-500 truncate">{{ doc.subject }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs text-gray-700">{{ doc.document_type }}</div>
                                        <div class="text-xs text-gray-400">{{ doc.action_type }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs text-gray-700">{{ doc.office_name }}</div>
                                        <div class="text-xs text-gray-400">{{ doc.origin_type }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        {{ doc.transactions?.[0]?.routing || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'px-2 py-0.5 text-xs font-medium rounded',
                                            doc.transactions?.[0]?.urgency_level === 'Urgent' ? 'bg-red-100 text-red-700' :
                                            doc.transactions?.[0]?.urgency_level === 'High' ? 'bg-orange-100 text-orange-700' :
                                            doc.transactions?.[0]?.urgency_level === 'Normal' ? 'bg-blue-100 text-blue-700' :
                                            doc.transactions?.[0]?.urgency_level === 'Routine' ? 'bg-gray-100 text-gray-700' :
                                            'bg-gray-100 text-gray-500'
                                        ]">
                                            {{ doc.transactions?.[0]?.urgency_level || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[getStatusClass(doc.status), 'px-2 py-0.5 text-xs font-medium rounded']">
                                            {{ doc.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ formatDate(doc.created_at) }}</td>
                                    <td class="px-4 py-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div v-if="results.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                            <p class="text-xs text-gray-600">Page {{ results.current_page }} of {{ results.last_page }}</p>
                            <div class="flex gap-2">
                                <button
                                    @click="handlePageChange(results.current_page - 1)"
                                    :disabled="results.current_page === 1"
                                    class="px-3 py-1 text-xs text-gray-600 border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50"
                                >
                                    Previous
                                </button>
                                <button
                                    @click="handlePageChange(results.current_page + 1)"
                                    :disabled="results.current_page === results.last_page"
                                    class="px-3 py-1 text-xs text-gray-600 border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Smart Chat Mode -->
        <div v-show="searchMode === 'smart'" class="flex" style="height: calc(100vh - 130px)">
            <!-- Chat History Sidebar -->
            <aside class="flex flex-col flex-shrink-0 bg-white border-r border-gray-200 w-72">
                <!-- New Chat Button -->
                <div class="p-4 border-b border-gray-200">
                    <button
                        @click="startNewChat"
                        class="flex items-center justify-center w-full gap-2 px-4 py-2.5 text-xs font-medium text-white transition bg-teal-700 rounded-lg hover:bg-teal-800"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Chat
                    </button>
                </div>

                <!-- Chat History List -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-2 space-y-1">
                        <!-- Today Section -->
                        <div class="px-3 py-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">Today</div>
                        <button
                            v-for="chat in chatHistory.filter(c => isToday(c.created_at))"
                            :key="chat.id"
                            @click="loadChat(chat.id)"
                            :class="[
                                'w-full px-3 py-2 text-left text-xs rounded-lg transition group',
                                activeChatId === chat.id
                                    ? 'bg-teal-50 text-teal-800'
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="truncate">{{ chat.title }}</span>
                                <button
                                    @click.stop="deleteChat(chat.id)"
                                    class="hidden p-1 text-gray-400 rounded hover:text-red-500 hover:bg-red-50 group-hover:block"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </button>

                        <!-- Previous 7 Days Section -->
                        <template v-if="chatHistory.filter(c => isPastWeek(c.created_at)).length > 0">
                            <div class="px-3 py-2 mt-4 text-xs font-semibold tracking-wide text-gray-400 uppercase">Previous 7 Days</div>
                            <button
                                v-for="chat in chatHistory.filter(c => isPastWeek(c.created_at))"
                                :key="chat.id"
                                @click="loadChat(chat.id)"
                                :class="[
                                    'w-full px-3 py-2 text-left text-xs rounded-lg transition group',
                                    activeChatId === chat.id
                                        ? 'bg-teal-50 text-teal-800'
                                        : 'text-gray-700 hover:bg-gray-100'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="truncate">{{ chat.title }}</span>
                                    <button
                                        @click.stop="deleteChat(chat.id)"
                                        class="hidden p-1 text-gray-400 rounded hover:text-red-500 hover:bg-red-50 group-hover:block"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </button>
                        </template>

                        <!-- Older Section -->
                        <template v-if="chatHistory.filter(c => isOlder(c.created_at)).length > 0">
                            <div class="px-3 py-2 mt-4 text-xs font-semibold tracking-wide text-gray-400 uppercase">Older</div>
                            <button
                                v-for="chat in chatHistory.filter(c => isOlder(c.created_at))"
                                :key="chat.id"
                                @click="loadChat(chat.id)"
                                :class="[
                                    'w-full px-3 py-2 text-left text-xs rounded-lg transition group',
                                    activeChatId === chat.id
                                        ? 'bg-teal-50 text-teal-800'
                                        : 'text-gray-700 hover:bg-gray-100'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="truncate">{{ chat.title }}</span>
                                    <button
                                        @click.stop="deleteChat(chat.id)"
                                        class="hidden p-1 text-gray-400 rounded hover:text-red-500 hover:bg-red-50 group-hover:block"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </button>
                        </template>

                        <!-- Empty State -->
                        <div v-if="chatHistory.length === 0" class="px-3 py-8 text-center">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <p class="text-xs text-gray-400">No chat history yet</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Chat Area -->
            <main class="flex flex-col flex-1 bg-gray-50">
                <!-- Chat Messages -->
                <div ref="chatMessagesRef" class="flex-1 overflow-y-auto">
                    <!-- Empty State / Welcome -->
                    <div v-if="chatMessages.length === 0" class="flex flex-col items-center justify-center h-full px-6">
                        <div class="flex items-center justify-center w-16 h-16 mb-6 rounded-full bg-gradient-to-br from-teal-100 to-emerald-100">
                            <svg class="w-8 h-8 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h2 class="mb-2 text-xl font-semibold text-gray-900">Smart Search Assistant</h2>
                        <p class="max-w-md mb-8 text-sm text-center text-gray-500">
                            Ask me anything about your documents. I can help you find, analyze, and understand your document workflow.
                        </p>

                        <!-- Suggested Prompts -->
                        <div class="grid w-full max-w-2xl grid-cols-2 gap-3">
                            <button
                                v-for="prompt in suggestedPrompts"
                                :key="prompt.label"
                                @click="sendMessage(prompt.query)"
                                class="flex items-start gap-3 p-4 text-left transition bg-white border border-gray-200 rounded-xl hover:border-teal-300 hover:shadow-sm group"
                            >
                                <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg bg-gray-50 group-hover:bg-teal-50">
                                    <component :is="prompt.icon" class="w-4 h-4 text-gray-400 group-hover:text-teal-600" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-800">{{ prompt.label }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ prompt.description }}</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Messages List -->
                    <div v-else class="max-w-3xl px-6 py-6 mx-auto space-y-6">
                        <div
                            v-for="(msg, idx) in chatMessages"
                            :key="idx"
                            :class="[
                                'flex gap-3',
                                msg.role === 'user' ? 'justify-end' : 'justify-start'
                            ]"
                        >
                            <!-- Assistant Avatar -->
                            <div v-if="msg.role === 'assistant'" class="flex items-start justify-center flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-teal-100 to-emerald-100">
                                <svg class="w-4 h-4 mt-2 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>

                            <!-- Message Bubble -->
                            <div
                                :class="[
                                    'max-w-[75%] px-4 py-3 rounded-2xl text-sm',
                                    msg.role === 'user'
                                        ? 'bg-teal-700 text-white rounded-br-md'
                                        : 'bg-white border border-gray-200 text-gray-800 rounded-bl-md shadow-sm'
                                ]"
                            >
                                <!-- Typing Indicator -->
                                <div v-if="msg.isTyping" class="flex items-center gap-1">
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                </div>
                                
                                <!-- Message Content -->
                                <div v-else>
                                    <p class="whitespace-pre-wrap">{{ msg.content }}</p>
                                    
                                    <!-- Document Results -->
                                    <div v-if="msg.documents && msg.documents.length > 0" class="mt-3 space-y-2">
                                        <div
                                            v-for="doc in msg.documents"
                                            :key="doc.document_no"
                                            @click="viewDocument(doc.document_no)"
                                            class="p-3 transition bg-gray-50 border border-gray-100 rounded-lg cursor-pointer hover:border-teal-200 hover:bg-teal-50/50"
                                        >
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-medium text-teal-700">{{ doc.document_no }}</span>
                                                <span :class="['px-2 py-0.5 text-xs rounded-full', getStatusClass(doc.status)]">
                                                    {{ doc.status }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-700 line-clamp-2">{{ doc.subject }}</p>
                                        </div>
                                    </div>

                                    <!-- Action Buttons for Assistant Messages -->
                                    <div v-if="msg.role === 'assistant' && !msg.isTyping" class="flex items-center gap-2 mt-3 pt-2 border-t border-gray-100">
                                        <button
                                            @click="copyMessage(msg.content)"
                                            class="p-1.5 text-gray-400 rounded hover:text-gray-600 hover:bg-gray-100"
                                            title="Copy"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="regenerateResponse(idx)"
                                            class="p-1.5 text-gray-400 rounded hover:text-gray-600 hover:bg-gray-100"
                                            title="Regenerate"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- User Avatar -->
                            <div v-if="msg.role === 'user'" class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-xs font-medium text-white bg-teal-700 rounded-full">
                                {{ userInitials }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-4 bg-white border-t border-gray-200">
                    <div class="max-w-3xl mx-auto">
                        <div class="relative">
                            <textarea
                                v-model="chatInput"
                                @keydown.enter.exact.prevent="sendMessage(chatInput)"
                                placeholder="Ask about your documents..."
                                rows="1"
                                class="w-full px-4 py-3 pr-12 text-sm bg-gray-50 border border-gray-200 rounded-xl resize-none focus:ring-2 focus:ring-teal-500 focus:border-transparent focus:bg-white"
                                :disabled="isSending"
                            ></textarea>
                            <button
                                @click="sendMessage(chatInput)"
                                :disabled="!chatInput.trim() || isSending"
                                class="absolute p-2 text-white transition bg-teal-700 rounded-lg right-2 top-1.5 hover:bg-teal-800 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isSending" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-center text-gray-400">
                            Smart Chat can search documents and provide insights. Press Enter to send.
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Save Search Modal -->
    <Teleport to="body">
        <div
            v-if="showSaveModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="showSaveModal = false"
        >
            <div class="w-full max-w-sm p-5 bg-white rounded-lg shadow-lg">
                <h3 class="mb-3 text-base font-semibold text-gray-900">Save Search</h3>
                <input
                    v-model="saveSearchName"
                    type="text"
                    placeholder="Search name..."
                    class="w-full px-3 py-2 mb-4 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                    @keyup.enter="handleSaveSearch"
                />
                <div class="flex justify-end gap-2">
                    <button @click="showSaveModal = false" class="px-4 py-2 text-xs text-gray-600 hover:text-gray-800">Cancel</button>
                    <button
                        @click="handleSaveSearch"
                        :disabled="savingSearch || !saveSearchName.trim()"
                        class="px-4 py-2 text-xs text-white bg-teal-700 rounded hover:bg-teal-800 disabled:opacity-50"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>