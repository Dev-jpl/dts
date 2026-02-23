// client/src/composables/useIncoming.ts

import { ref, computed } from 'vue'
import API from '@/api'

// ─── Types ────────────────────────────────────────────────────────────────────

export type IncomingTab = 'all' | 'for_action' | 'actioned' | 'overdue'
export type SortDir = 'asc' | 'desc'
export type SortBy = 'created_at' | 'office_name'
export type PerPage = 15 | 30 | 50

export interface IncomingFilters {
    search: string
    document_type: string
    routing: string
    from_office_id: string
    date_from: string
    date_to: string
    sort_by: SortBy
    sort_dir: SortDir
    per_page: PerPage
}

export interface FilterOptions {
    document_types: string[]
    routing_types: string[]
    sender_offices: { office_id: string; office_name: string }[]
}

export interface IncomingDocument {
    id: number
    document_no: string
    transaction_no: string
    office_id: string
    office_name: string
    routed_office_id: string | null
    routed_office_name: string | null
    status: 'Profiled' | 'Received' | 'Released' | 'Archived' | 'Returned To Sender' | 'Forwarded'
    action_taken: string | null
    activity: string
    remarks: string | null
    assigned_personnel_id: string
    assigned_personnel_name: string
    created_at: string
    updated_at: string
    transaction?: {
        transaction_no: string
        transaction_type: string
        routing: 'Single' | 'Multiple' | 'Sequential'
        document_no: string
        document_type: string
        action_type: string
        subject: string
        status: string
        office_name: string
        created_by_name: string
        created_at: string
        document?: {
            document_no: string
            subject: string
            document_type: string
            status: string
        }
        recipients?: any[]
        attachments?: any[]
        signatories?: any[]
    }
}

export interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
}

export interface TabCounts {
    all: number
    for_action: number
    actioned: number
    overdue: number
}

// ─── Tab Config ───────────────────────────────────────────────────────────────

export const INCOMING_TABS: { key: IncomingTab; label: string; endpoint: string }[] = [
    { key: 'for_action', label: 'For Action', endpoint: '/incoming/for-action' },
    { key: 'actioned', label: 'Actioned', endpoint: '/incoming/actioned' },
    { key: 'overdue', label: 'Overdue', endpoint: '/incoming/overdue' },
    { key: 'all', label: 'All Incoming', endpoint: '/incoming' },
]

// ─── Status chip helper (exported so view can use it too) ─────────────────────

export type DerivedStatus =
    | 'Awaiting Receipt'
    | 'For Processing'
    | 'Returned To You'
    | 'Forwarded'
    | 'Returned To Sender'
    | 'Archived'

export function deriveStatus(doc: IncomingDocument, currentOfficeId: string): DerivedStatus {
    const status = doc.status

    // Released TO this office but never received yet
    if (status === 'Released' && doc.routed_office_id === currentOfficeId) {
        return 'Awaiting Receipt'
    }
    // Released to someone else but came back here
    if (status === 'Released' && doc.office_id !== currentOfficeId) {
        return 'Returned To You'
    }
    if (status === 'Received') return 'For Processing'
    if (status === 'Forwarded') return 'Forwarded'
    if (status === 'Returned To Sender') return 'Returned To Sender'
    if (status === 'Archived') return 'Archived'

    return 'Awaiting Receipt'
}

export const STATUS_CHIP: Record<DerivedStatus, { label: string; classes: string }> = {
    'Awaiting Receipt': { label: 'Awaiting Receipt', classes: 'bg-blue-100 text-blue-700' },
    'For Processing': { label: 'For Processing', classes: 'bg-amber-100 text-amber-700' },
    'Returned To You': { label: 'Returned To You', classes: 'bg-orange-100 text-orange-700' },
    'Forwarded': { label: 'Forwarded', classes: 'bg-purple-100 text-purple-700' },
    'Returned To Sender': { label: 'Returned to Sender', classes: 'bg-red-100 text-red-700' },
    'Archived': { label: 'Archived', classes: 'bg-gray-100 text-gray-500' },
}

export const ROUTING_CHIP: Record<string, string> = {
    'Single': 'bg-slate-100 text-slate-600',
    'Multiple': 'bg-indigo-100 text-indigo-600',
    'Sequential': 'bg-teal-100 text-teal-600',
}

// ─── Default filters ──────────────────────────────────────────────────────────

function defaultFilters(): IncomingFilters {
    return {
        search: '',
        document_type: '',
        routing: '',
        from_office_id: '',
        date_from: '',
        date_to: '',
        sort_by: 'created_at',
        sort_dir: 'desc',
        per_page: 15,
    }
}

// ─── Composable ───────────────────────────────────────────────────────────────

export function useIncoming() {
    const activeTab = ref<IncomingTab>('for_action')
    const documents = ref<IncomingDocument[]>([])
    const isLoading = ref(false)
    const error = ref<string | null>(null)
    const pagination = ref<Pagination | null>(null)
    const counts = ref<TabCounts>({ all: 0, for_action: 0, actioned: 0, overdue: 0 })
    const countsLoading = ref(false)
    const filters = ref<IncomingFilters>(defaultFilters())
    const filterOptions = ref<FilterOptions>({ document_types: [], routing_types: [], sender_offices: [] })
    const filterOptionsLoading = ref(false)
    const showFilters = ref(false)

    const currentTab = computed(() => INCOMING_TABS.find(t => t.key === activeTab.value)!)

    const hasActiveFilters = computed(() =>
        filters.value.search !== '' ||
        filters.value.document_type !== '' ||
        filters.value.routing !== '' ||
        filters.value.from_office_id !== '' ||
        filters.value.date_from !== '' ||
        filters.value.date_to !== ''
    )

    // ── Build query params from current filter state ──
    function buildParams(page = 1) {
        const f = filters.value
        const params: Record<string, any> = { page, per_page: f.per_page }
        if (f.search) params.search = f.search
        if (f.document_type) params.document_type = f.document_type
        if (f.routing) params.routing = f.routing
        if (f.from_office_id) params.from_office_id = f.from_office_id
        if (f.date_from) params.date_from = f.date_from
        if (f.date_to) params.date_to = f.date_to
        params.sort_by = f.sort_by
        params.sort_dir = f.sort_dir
        return params
    }

    // ── Fetch documents ──
    async function fetchDocuments(page = 1) {
        isLoading.value = true
        error.value = null
        try {
            const { data } = await API.get(currentTab.value.endpoint, {
                params: buildParams(page),
            })
            documents.value = data.data.data
            pagination.value = {
                current_page: data.data.current_page,
                last_page: data.data.last_page,
                per_page: data.data.per_page,
                total: data.data.total,
                from: data.data.from,
                to: data.data.to,
            }
        } catch (err: any) {
            error.value = err?.response?.data?.message ?? 'Failed to fetch incoming documents'
        } finally {
            isLoading.value = false
        }
    }

    // ── Fetch tab badge counts ──
    async function fetchCounts() {
        countsLoading.value = true
        try {
            const { data } = await API.get('/incoming/counts')
            counts.value = data.counts
        } catch { /* silently fail */ }
        finally { countsLoading.value = false }
    }

    // ── Fetch filter dropdown options ──
    async function fetchFilterOptions() {
        filterOptionsLoading.value = true
        try {
            const { data } = await API.get('/incoming/filters')
            filterOptions.value = {
                document_types: data.document_types,
                routing_types: data.routing_types,
                sender_offices: data.sender_offices,
            }
        } catch { /* silently fail */ }
        finally { filterOptionsLoading.value = false }
    }

    // ── Switch tab (resets page but keeps filters) ──
    async function switchTab(tab: IncomingTab) {
        activeTab.value = tab
        await fetchDocuments(1)
    }

    // ── Toggle sort direction, or set new sort field ──
    async function setSort(sortBy: SortBy) {
        if (filters.value.sort_by === sortBy) {
            filters.value.sort_dir = filters.value.sort_dir === 'desc' ? 'asc' : 'desc'
        } else {
            filters.value.sort_by = sortBy
            filters.value.sort_dir = 'desc'
        }
        await fetchDocuments(1)
    }

    // ── Clear all filters ──
    async function clearFilters() {
        const { sort_by, sort_dir, per_page } = filters.value
        filters.value = { ...defaultFilters(), sort_by, sort_dir, per_page }
        await fetchDocuments(1)
    }

    // ── Full refresh ──
    async function refresh() {
        await Promise.all([fetchDocuments(1), fetchCounts()])
    }

    return {
        // State
        activeTab, documents, isLoading, error,
        pagination, counts, countsLoading,
        filters, filterOptions, filterOptionsLoading,
        showFilters, hasActiveFilters, currentTab,

        // Actions
        fetchDocuments, fetchCounts, fetchFilterOptions,
        switchTab, setSort, clearFilters, refresh,

        // Constants
        INCOMING_TABS,
    }
}