<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
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
  INCOMING_TABS: tabs,
} = useIncoming()

// ── Sliding tab indicator ──
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
  await refresh()
  await fetchFilterOptions()
  updateIndicator()
})
watch(activeTab, () => updateIndicator())

// ── Expandable subject text ──
const { getDisplayText } = useExpandableTextArray(250)

// ── Search debounce ──
let searchTimer: ReturnType<typeof setTimeout>
function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchDocuments(1), 400)
}

// ── Helpers ──
function getDateLabel(dateStr: string): string {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleTimeString('en-PH', {
    hour: 'numeric', minute: '2-digit', hour12: true,
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

function sortIcon(col: SortBy): string {
  if (filters.value.sort_by !== col) return '↕'
  return filters.value.sort_dir === 'asc' ? '↑' : '↓'
}
</script>

<template>
  <ScrollableContainer padding="0" px="50px" background="white" class="bg-white">
    <div class="w-full">

      <!-- ══ Header + Tabs ══════════════════════════════════════════════ -->
      <div
        class="flex flex-col items-start justify-start w-full border-b border-gray-200 sm:items-end sm:flex-row sm:justify-between">
        <div class="p-4">
          <h1 class="font-bold text-gray-600">Incoming Documents</h1>
        </div>

        <div class="relative flex divide-gray-200">
          <div v-for="(tab, index) in INCOMING_TABS" :key="tab.key"
            :ref="el => { if (el) tabRefs[index] = el as HTMLElement }" @click="switchTab(tab.key as IncomingTab)"
            class="px-3 mb-2 sm:w-[100px] py-2 text-xs text-center cursor-pointer relative"
            :class="activeTab === tab.key ? 'text-teal-700 font-bold' : 'text-gray-500'">
            {{ tab.label }}
            <span v-if="counts[tab.key] > 0"
              class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold rounded-full"
              :class="tab.key === 'overdue' ? 'bg-red-500 text-white' : 'bg-teal-600 text-white'">
              {{ counts[tab.key] > 99 ? '99+' : counts[tab.key] }}
            </span>
          </div>
          <div class="absolute bottom-0 h-1 transition-all duration-300 bg-amber-500 rounded-t-md"
            :style="indicatorStyle" />
        </div>
      </div>

      <!-- ══ Toolbar: Search + Filter toggle + Per-page ════════════════ -->
      <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-100 bg-gray-50">

        <!-- Search -->
        <div class="relative flex-1 max-w-xs">
          <span class="absolute inset-y-0 left-2.5 flex items-center text-gray-400 text-xs">🔍</span>
          <input v-model="filters.search" @input="onSearchInput" type="text"
            placeholder="Search subject, doc no, type, office..."
            class="w-full pl-7 pr-3 py-1.5 text-xs border border-gray-200 rounded-md bg-white focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>

        <!-- Filter toggle -->
        <button @click="showFilters = !showFilters"
          class="flex items-center gap-1 px-3 py-1.5 text-xs border rounded-md transition" :class="hasActiveFilters
            ? 'border-teal-500 bg-teal-50 text-teal-700 font-semibold'
            : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'">
          Filters
          <span v-if="hasActiveFilters" class="ml-1 w-1.5 h-1.5 rounded-full bg-teal-500 inline-block" />
        </button>

        <!-- Clear filters -->
        <button v-if="hasActiveFilters" @click="clearFilters"
          class="text-xs text-red-500 hover:text-red-700 px-2 py-1.5">
          ✕ Clear
        </button>

        <div class="flex-1" />

        <!-- Sort -->
        <div class="flex items-center gap-1 text-xs text-gray-500">
          <span>Sort:</span>
          <button @click="setSort('created_at')" class="px-2 py-1 text-xs border rounded" :class="filters.sort_by === 'created_at'
            ? 'border-teal-500 text-teal-700 bg-teal-50'
            : 'border-gray-200 hover:border-gray-300'">
            Date
          </button>
          <button @click="setSort('office_name')" class="px-2 py-1 text-xs border rounded" :class="filters.sort_by === 'office_name'
            ? 'border-teal-500 text-teal-700 bg-teal-50'
            : 'border-gray-200 hover:border-gray-300'">
            From
          </button>
        </div>

        <!-- Per-page -->
        <select v-model="filters.per_page" @change="fetchDocuments(1)"
          class="text-xs border border-gray-200 rounded-md px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-teal-500">
          <option :value="15">15 / page</option>
          <option :value="30">30 / page</option>
          <option :value="50">50 / page</option>
        </select>
      </div>

      <!-- ══ Filter Panel (expandable) ══════════════════════════════════ -->
      <div v-if="showFilters" class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50">
        <!-- Document Type -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Doc Type</label>
          <select v-model="filters.document_type" @change="fetchDocuments(1)"
            class="text-xs border border-gray-200 rounded-md px-2 py-1.5 bg-white min-w-[140px] focus:outline-none focus:ring-1 focus:ring-teal-500">
            <option value="">All Types</option>
            <option v-for="type in filterOptions.document_types" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>

        <!-- Routing Type -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Routing</label>
          <select v-model="filters.routing" @change="fetchDocuments(1)"
            class="text-xs border border-gray-200 rounded-md px-2 py-1.5 bg-white min-w-[130px] focus:outline-none focus:ring-1 focus:ring-teal-500">
            <option value="">All Routing</option>
            <option v-for="r in filterOptions.routing_types" :key="r" :value="r">{{ r }}</option>
          </select>
        </div>

        <!-- From Office -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">From Office</label>
          <select v-model="filters.from_office_id" @change="fetchDocuments(1)"
            class="text-xs border border-gray-200 rounded-md px-2 py-1.5 bg-white min-w-[180px] focus:outline-none focus:ring-1 focus:ring-teal-500">
            <option value="">All Offices</option>
            <option v-for="o in filterOptions.sender_offices" :key="o.office_id" :value="o.office_id">
              {{ o.office_name }}
            </option>
          </select>
        </div>

        <!-- Date From -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Date From</label>
          <input v-model="filters.date_from" @change="fetchDocuments(1)" type="date"
            class="text-xs border border-gray-200 rounded-md px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>

        <!-- Date To -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Date To</label>
          <input v-model="filters.date_to" @change="filters.date_from ? fetchDocuments(1) : null" type="date"
            class="text-xs border border-gray-200 rounded-md px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
      </div>

      <!-- ══ Table Header ═══════════════════════════════════════════════ -->
      <table class="w-full">
        <thead>
          <tr class="sticky bg-gray-100">
            <td width="15%" class="px-4 py-2 text-xs font-semibold">From</td>
            <td width="57%" class="px-4 py-2 text-xs font-semibold">Subject</td>
            <td width="15%" class="px-4 py-2 text-xs font-semibold">Status</td>
            <td width="10%" class="px-4 py-2 text-xs font-semibold text-right">
              {{ activeTab === 'overdue' ? 'Elapsed' : 'Sent' }}
            </td>
          </tr>
        </thead>
      </table>

      <!-- ══ Table Body ═════════════════════════════════════════════════ -->
      <div class="h-[calc(100svh-260px)] overflow-auto">

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
              @click="$router.push({ name: 'view-document', params: { doc_no: doc.document_no } })">
              <!-- From -->
              <td width="15%" class="p-3 text-xs font-light align-top">
                {{ doc.office_name }}
              </td>

              <!-- Subject + routing chip -->
              <td width="57%" class="p-3 align-top">
                <div class="text-xs font-normal">
                  <span class="font-bold">{{ doc.transaction?.document_type ?? '—' }}</span>
                  -
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
                <span class="inline-block px-2 py-0.5 text-[10px] rounded-full font-medium whitespace-nowrap"
                  :class="STATUS_CHIP[deriveStatus(doc, auth.user?.office_id ?? '')]?.classes">
                  {{ STATUS_CHIP[deriveStatus(doc, auth.user?.office_id ?? '')]?.label }}
                </span>
              </td>

              <!-- Sent / Elapsed -->
              <td width="10%" class="p-3 text-xs font-light text-right align-top whitespace-nowrap">
                <span :class="activeTab === 'overdue' ? getOverdueClass(doc.created_at) : ''">
                  {{ activeTab === 'overdue'
                    ? getOverdueDaysLabel(doc.created_at)
                    : getDateLabel(doc.created_at)
                  }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ══ Pagination Footer ══════════════════════════════════════════ -->
      <div v-if="pagination && pagination.last_page >= 1"
        class="flex items-center justify-between px-4 py-2 text-xs text-gray-500 border-t border-gray-100 bg-gray-50">
        <!-- Result summary -->
        <span>
          Showing {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }}
          of {{ pagination.total }} result{{ pagination.total !== 1 ? 's' : '' }}
        </span>

        <!-- Page controls -->
        <div class="flex items-center gap-1">
          <!-- First -->
          <button @click="fetchDocuments(1)" :disabled="pagination.current_page <= 1 || isLoading"
            class="px-2 py-1 border border-gray-200 rounded disabled:opacity-30 hover:bg-gray-100">«</button>

          <!-- Prev -->
          <button @click="fetchDocuments(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1 || isLoading"
            class="px-2 py-1 border border-gray-200 rounded disabled:opacity-30 hover:bg-gray-100">‹</button>

          <!-- Page numbers -->
          <template v-for="p in pagination.last_page" :key="p">
            <button v-if="Math.abs(p - pagination.current_page) <= 2 || p === 1 || p === pagination.last_page"
              @click="fetchDocuments(p)" class="px-2.5 py-1 rounded border text-xs" :class="p === pagination.current_page
                ? 'border-teal-500 bg-teal-600 text-white font-bold'
                : 'border-gray-200 hover:bg-gray-100'">{{ p }}</button>
            <span v-else-if="Math.abs(p - pagination.current_page) === 3" class="px-1">…</span>
          </template>

          <!-- Next -->
          <button @click="fetchDocuments(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page || isLoading"
            class="px-2 py-1 border border-gray-200 rounded disabled:opacity-30 hover:bg-gray-100">›</button>

          <!-- Last -->
          <button @click="fetchDocuments(pagination.last_page)"
            :disabled="pagination.current_page >= pagination.last_page || isLoading"
            class="px-2 py-1 border border-gray-200 rounded disabled:opacity-30 hover:bg-gray-100">»</button>
        </div>
      </div>

    </div>
  </ScrollableContainer>
</template>