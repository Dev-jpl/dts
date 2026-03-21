import API from '@/api'
import { ref, reactive, computed } from 'vue'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface SearchFilters {
    q?: string
    document_no?: string
    document_type?: string
    action_type?: string
    origin_type?: string
    sender?: string
    sender_office?: string
    sender_email?: string
    origin_office?: string
    recipient_office?: string
    status?: string
    routing_type?: string
    urgency_level?: string
    overdue_only?: boolean
    has_attachments?: boolean
    has_notes?: boolean
    has_returned?: boolean
    return_reason?: string
    released_from?: string
    released_to?: string
    received_from?: string
    received_to?: string
    days_since_released?: number
    days_overdue?: number
    sort_by?: string
}

export interface SearchResult {
    document_no: string
    subject: string
    document_type: string
    action_type: string
    status: string
    office_id: string
    office_name: string
    created_at: string
    transactions: Array<{
        transaction_no: string
        status: string
        routing_type: string
        urgency_level: string
        recipients: Array<{
            office_id: string
            office_name: string
            recipient_type: string
            isActive: boolean
        }>
    }>
    attachments: Array<{
        id: number
        filename: string
    }>
    notes: Array<{
        id: number
        note: string
    }>
}

export interface SavedSearch {
    id: number
    name: string
    filters_json: SearchFilters
    sort_by: string
    created_at: string
    updated_at: string
}

export interface SearchFilterOptions {
    document_types: string[]
    action_types: string[]
    origin_types: string[]
    offices: Array<{ office_id: string; office_name: string }>
    statuses: string[]
    routing_types: string[]
    urgency_levels: string[]
}

export interface PaginatedResults {
    data: SearchResult[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

// ── Composable ────────────────────────────────────────────────────────────────

export function useSearch() {
    // State
    const loading = ref(false)
    const quickLoading = ref(false)
    const results = ref<PaginatedResults | null>(null)
    const quickResults = ref<SearchResult[]>([])
    const savedSearches = ref<SavedSearch[]>([])
    const filterOptions = ref<SearchFilterOptions | null>(null)
    const error = ref<string | null>(null)

    const filters = reactive<SearchFilters>({
        q: '',
        sort_by: 'relevance',
    })

    // Clear all filters
    const clearFilters = () => {
        Object.keys(filters).forEach(key => {
            if (key === 'sort_by') {
                filters[key as keyof SearchFilters] = 'relevance' as any
            } else {
                delete filters[key as keyof SearchFilters]
            }
        })
        filters.q = ''
    }

    // Has active filters
    const hasActiveFilters = computed(() => {
        return Object.entries(filters).some(([key, value]) => {
            if (key === 'sort_by') return false
            if (value === undefined || value === null || value === '') return false
            if (typeof value === 'boolean' && !value) return false
            return true
        })
    })

    // Fetch filter options
    const fetchFilterOptions = async () => {
        try {
            const response = await API.get('/search/filters')
            filterOptions.value = response.data.data
        } catch (e: any) {
            console.error('Failed to fetch filter options:', e)
        }
    }

    // Full search
    const search = async (page = 1) => {
        loading.value = true
        error.value = null
        try {
            const params: Record<string, any> = { ...filters, page }
            // Remove empty/undefined values
            Object.keys(params).forEach(key => {
                if (params[key] === undefined || params[key] === null || params[key] === '') {
                    delete params[key]
                }
            })
            const response = await API.get('/search', { params })
            results.value = response.data.data
        } catch (e: any) {
            error.value = e.response?.data?.message || e.message || 'Search failed'
            results.value = null
        } finally {
            loading.value = false
        }
    }

    // Quick search (for nav bar dropdown)
    const quickSearch = async (query: string) => {
        if (query.length < 2) {
            quickResults.value = []
            return
        }
        quickLoading.value = true
        try {
            const response = await API.get('/search/quick', { params: { q: query } })
            quickResults.value = response.data.data
        } catch (e: any) {
            console.error('Quick search failed:', e)
            quickResults.value = []
        } finally {
            quickLoading.value = false
        }
    }

    // Saved searches
    const fetchSavedSearches = async () => {
        try {
            const response = await API.get('/search/saved')
            savedSearches.value = response.data.data
        } catch (e: any) {
            console.error('Failed to fetch saved searches:', e)
        }
    }

    const saveSearch = async (name: string) => {
        try {
            const payload = {
                name,
                filters_json: { ...filters },
                sort_by: filters.sort_by || 'relevance',
            }
            const response = await API.post('/search/saved', payload)
            savedSearches.value.unshift(response.data.data)
            return response.data
        } catch (e: any) {
            throw e
        }
    }

    const deleteSavedSearch = async (id: number) => {
        try {
            await API.delete(`/search/saved/${id}`)
            savedSearches.value = savedSearches.value.filter(s => s.id !== id)
        } catch (e: any) {
            throw e
        }
    }

    const applySavedSearch = (saved: SavedSearch) => {
        clearFilters()
        Object.assign(filters, saved.filters_json)
        if (saved.sort_by) {
            filters.sort_by = saved.sort_by
        }
    }

    return {
        // State
        loading,
        quickLoading,
        results,
        quickResults,
        savedSearches,
        filterOptions,
        error,
        filters,

        // Computed
        hasActiveFilters,

        // Methods
        search,
        quickSearch,
        clearFilters,
        fetchFilterOptions,
        fetchSavedSearches,
        saveSearch,
        deleteSavedSearch,
        applySavedSearch,
    }
}
