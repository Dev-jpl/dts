<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import {
  useIncoming, type IncomingTab, type SortBy,
  INCOMING_TABS, deriveStatus, STATUS_CHIP, ROUTING_CHIP
} from '@/composables/useIncoming'
import ScrollableContainer from '@/components/ScrollableContainer.vue'
import { useExpandableTextArray } from '@/composables/useExpandableTextArray'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const {
  activeTab, documents, isLoading, error,
  pagination, counts, filters, filterOptions,
  showFilters, hasActiveFilters,
  fetchDocuments, fetchFilterOptions,
  switchTab, setSort, clearFilters, refresh,
} = useIncoming()

// ── Sliding tab indicator (matches existing pattern) ──
const tabRefs = ref<HTMLElement[]>([])
const indicatorStyle = ref({})

function updateIndicator() {
  const index = INCOMING_TABS.findIndex(t => t.key === activeTab.value)
  const el = tabRefs.value[index]
  if (el) {
    indicatorStyle.value = { left: el.offsetLeft + 'px', width: el.offsetWidth + 'px' }
  }
}

onMounted(async () => {
  updateIndicator()
  fetchFilterOptions()
  refresh()
})
watch(activeTab, () => updateIndicator())

// ── Expandable text ──
const { getDisplayText } = useExpandableTextArray(250)

// ── Search debounce ──
let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(() => filters.value.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchDocuments(1), 400)
})

// ── Watchers for immediate filters ──
watch([
  () => filters.value.document_type,
  () => filters.value.routing,
  () => filters.value.from_office_id,
  () => filters.value.date_from,
  () => filters.value.date_to,
], () => fetchDocuments(1))

// ── Page numbers with ellipsis (mirrors MyDocumentsView) ──
const pageNumbers = computed(() => {
  if (!pagination.value) return []
  const total = pagination.value.last_page
  const current = pagination.value.current_page
  const delta = 2
  const pages: (number | '...')[] = []
  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      pages.push(i)
    } else if (pages[pages.length - 1] !== '...') {
      pages.push('...')
    }
  }
  return pages
})

// ── Helpers ──
function formatDate(dateStr: string): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
  })
}

function getOverdueDaysLabel(dateStr: string): string {
  const days = Math.floor((Date.now() - new Date(dateStr).getTime()) / 86_400_000)
  if (days === 0) return 'Today'
  if (days === 1) return '1 day ago'
  return `${days} days ago`
}

function getOverdueClass(dateStr: string): string {
  const days = Math.floor((Date.now() - new Date(dateStr).getTime()) / 86_400_000)
  if (days <= 3) return 'text-yellow-600'
  if (days <= 6) return 'text-orange-500'
  return 'text-red-600 font-semibold'
}

function goToPage(page: number) {
  if (!pagination.value) return
  if (page < 1 || page > pagination.value.last_page) return
  fetchDocuments(page)
}

const hasPrevPage = computed(() => (pagination.value?.current_page ?? 1) > 1)
const hasNextPage = computed(() => (pagination.value?.current_page ?? 1) < (pagination.value?.last_page ?? 1))
</script>

<template>
  <ScrollableContainer padding="0" rem="50px" background="white" class="bg-white">
    <div class="w-full">

      <!-- ── Header + Tabs ─────────────────────────────────────────────── -->
      <div
        class="flex flex-col items-start justify-start w-full border-b border-gray-200 sm:items-end sm:flex-row sm:justify-between">
        <div class="p-4">
          <h1 class="font-bold text-gray-600">Incoming Documents</h1>
        </div>

        <div class="relative flex pr-4">
          <div v-for="(tab, index) in INCOMING_TABS" :key="tab.key"
            :ref="el => { if (el) tabRefs[index] = el as HTMLElement }" @click="switchTab(tab.key as IncomingTab)"
            class="px-3 w-[8rem] py-2 text-xs text-center cursor-pointer relative"
            :class="activeTab === tab.key ? 'text-teal-700 font-bold bg-white rounded-tl-md transition-all duration-150 ease-in-out rounded-tr-md shadow rounded-b-0' : 'text-gray-500'">
            {{ tab.label }}
            <span v-if="counts[tab.key] > 0"
              class="absolute right-2 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold rounded-full"
              :class="tab.key === 'overdue' ? 'bg-red-500 text-white' : 'bg-teal-600 text-white'">
              {{ counts[tab.key] > 99 ? '99+' : counts[tab.key] }}
            </span>
          </div>
          <div class="absolute bottom-0 h-1 transition-all duration-300 bg-amber-500 rounded-t-md"
            :style="indicatorStyle" />
        </div>
      </div>

      <!-- ── Search + Filter Toggle ────────────────────────────────────── -->
      <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-100 bg-gray-50/60">

        <!-- Search -->
        <div class="relative flex-1 max-w-xs">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
            class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-gray-400 pointer-events-none">
            <path fill-rule="evenodd"
              d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
              clip-rule="evenodd" />
          </svg>
          <input v-model="filters.search" type="text" placeholder="Search subject, doc no., type, office..."
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
            {{ [filters.document_type, filters.routing, filters.from_office_id, filters.date_from ||
              filters.date_to].filter(Boolean).length }}
          </span>
        </button>

        <!-- Clear -->
        <button v-if="hasActiveFilters" @click="clearFilters"
          class="text-xs text-gray-400 transition hover:text-red-500">
          Clear
        </button>

        <!-- Result count (right side) -->
        <span v-if="pagination" class="ml-auto text-[11px] text-gray-400">
          {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }} document{{ pagination.total !== 1 ? 's' :
          '' }}
        </span>
      </div>

      <!-- ── Filter Panel ──────────────────────────────────────────────── -->
      <div v-if="showFilters" class="grid grid-cols-4 gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50/40">

        <!-- Document Type -->
        <div>
          <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
            Document Type
          </label>
          <select v-model="filters.document_type"
            class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
            <option value="">All Types</option>
            <option v-for="type in filterOptions.document_types" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>

        <!-- Routing -->
        <div>
          <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
            Routing Type
          </label>
          <select v-model="filters.routing"
            class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
            <option value="">All Routing</option>
            <option v-for="r in filterOptions.routing_types" :key="r" :value="r">{{ r }}</option>
          </select>
        </div>

        <!-- From Office -->
        <div>
          <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
            From Office
          </label>
          <select v-model="filters.from_office_id"
            class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
            <option value="">All Offices</option>
            <option v-for="o in filterOptions.sender_offices" :key="o.office_id" :value="o.office_id">
              {{ o.office_name }}
            </option>
          </select>
        </div>

        <!-- Date Range -->
        <div>
          <label class="block mb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
            Date Range
          </label>
          <div class="flex items-center gap-1">
            <input v-model="filters.date_from" type="date"
              class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500" />
            <span class="text-xs text-gray-400">–</span>
            <input v-model="filters.date_to" type="date"
              class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500" />
          </div>
        </div>
      </div>

      <!-- ── Table Header ──────────────────────────────────────────────── -->
      <table class="w-full">
        <thead>
          <tr class="sticky bg-gray-100">
            <td width="15%" class="px-4 py-2 text-xs font-semibold">From</td>
            <td width="55%" class="px-4 py-2 text-xs font-semibold">Subject</td>
            <td width="15%" class="px-4 py-2 text-xs font-semibold">Status</td>
            <td width="12%" class="px-4 py-2 text-xs font-semibold text-right">
              {{ activeTab === 'overdue' ? 'Elapsed' : 'Date' }}
            </td>
          </tr>
        </thead>
      </table>

      <!-- ── Scrollable Table Body ─────────────────────────────────────── -->
      <div class="h-[calc(100svh-270px)] overflow-auto">

        <!-- Loading -->
        <div v-if="isLoading" class="flex items-center justify-center py-16">
          <div class="border-4 border-teal-600 rounded-full w-7 h-7 border-t-transparent animate-spin" />
        </div>

        <!-- Error -->
        <div v-else-if="error" class="py-16 text-xs text-center text-red-500">
          {{ error }}
        </div>

        <!-- Empty -->
        <div v-else-if="documents.length === 0" class="py-16 text-xs text-center text-gray-400">
          {{ hasActiveFilters ? 'No documents match your filters.' : 'No documents found.' }}
        </div>

        <!-- Rows -->
        <table v-else class="w-full">
          <tbody class="divide-y divide-gray-200">
            <tr v-for="doc in documents" :key="doc.id" class="hover:bg-gray-50 hover:cursor-pointer"
              @click="$router.push({ name: 'view-document', params: { trxNo: doc.transaction_no } })">
              <!-- From -->
              <td width="15%" class="p-3 text-xs font-light text-gray-600 align-top">
                {{ doc.office_name }}
              </td>

              <!-- Subject + chips -->
              <td width="55%" class="p-3 align-top">
                <div class="text-xs">
                  <span class="font-bold">{{ doc.transaction?.document_type ?? '—' }}</span>
                  <span class="text-gray-400"> - </span>
                  {{ getDisplayText(String(doc.id), doc.transaction?.document?.subject ?? doc.activity) }}
                </div>
                <!-- Routing chip -->
                <span v-if="doc.transaction?.routing"
                  class="inline-block mt-1 px-1.5 py-0.5 text-[10px] rounded font-medium"
                  :class="ROUTING_CHIP[doc.transaction.routing] ?? 'bg-gray-100 text-gray-500'">
                  {{ doc.transaction.routing }}
                </span>
              </td>

              <!-- Status chip -->
              <td width="15%" class="p-3 align-top">
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] rounded-full font-medium whitespace-nowrap"
                  :class="STATUS_CHIP[deriveStatus(doc, auth.user?.office_id ?? '')]?.classes ?? 'bg-gray-100 text-gray-500'">
                  <span class="size-1.5 rounded-full inline-block flex-shrink-0" :class="{
                    'bg-blue-500':   deriveStatus(doc, auth.user?.office_id ?? '') === 'Awaiting Receipt',
                    'bg-amber-500':  deriveStatus(doc, auth.user?.office_id ?? '') === 'In Progress',
                    'bg-orange-500': deriveStatus(doc, auth.user?.office_id ?? '') === 'Returned To You',
                    'bg-purple-500': deriveStatus(doc, auth.user?.office_id ?? '') === 'Forwarded',
                    'bg-red-500':    deriveStatus(doc, auth.user?.office_id ?? '') === 'Returned To Sender',
                    'bg-green-500':  deriveStatus(doc, auth.user?.office_id ?? '') === 'Done',
                    'bg-gray-400':   deriveStatus(doc, auth.user?.office_id ?? '') === 'Closed',
                  }" />
                  {{ STATUS_CHIP[deriveStatus(doc, auth.user?.office_id ?? '')]?.label }}
                </span>
              </td>

              <!-- Date / Elapsed -->
              <td width="12%" class="p-3 text-xs font-light text-right text-gray-500 align-top">
                <span :class="activeTab === 'overdue' ? getOverdueClass(doc.created_at) : ''">
                  {{ activeTab === 'overdue'
                    ? getOverdueDaysLabel(doc.created_at)
                    : formatDate(doc.created_at)
                  }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ── Pagination (matches MyDocumentsView pattern) ──────────────── -->
      <div v-if="pagination && pagination.last_page > 1"
        class="flex items-center justify-between px-4 py-2 bg-white border-t border-gray-200">
        <!-- Left: showing X–Y of Z -->
        <span class="text-[11px] text-gray-400">
          Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }} documents
        </span>

        <!-- Right: page controls -->
        <div class="flex items-center gap-1">

          <!-- Prev -->
          <button @click="goToPage((pagination?.current_page ?? 1) - 1)" :disabled="!hasPrevPage || isLoading"
            class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7" :class="hasPrevPage && !isLoading
              ? 'border-gray-200 text-gray-600 hover:bg-gray-50'
              : 'border-gray-100 text-gray-300 cursor-not-allowed'">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
              <path fill-rule="evenodd"
                d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                clip-rule="evenodd" />
            </svg>
          </button>

          <!-- Page numbers with ellipsis -->
          <template v-for="(p, i) in pageNumbers" :key="i">
            <span v-if="p === '...'" class="px-1 text-xs text-gray-400">…</span>
            <button v-else @click="goToPage(p as number)"
              class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7" :class="p === pagination.current_page
                ? 'border-teal-500 bg-teal-600 text-white font-semibold'
                : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
              {{ p }}
            </button>
          </template>

          <!-- Next -->
          <button @click="goToPage((pagination?.current_page ?? 1) + 1)" :disabled="!hasNextPage || isLoading"
            class="flex items-center justify-center text-xs transition border rounded-md w-7 h-7" :class="hasNextPage && !isLoading
              ? 'border-gray-200 text-gray-600 hover:bg-gray-50'
              : 'border-gray-100 text-gray-300 cursor-not-allowed'">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
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