// composables/useMyDocuments.ts
import { ref, computed } from 'vue'
import API from '@/api'

export interface MyDocumentRecipient {
    document_no: string
    office_id: string
    office_name: string
    recipient_type: 'default' | 'cc' | 'bcc'
    sequence: number
}

export interface MyDocumentSignatory {
    document_no: string
    employee_id: string
    employee_name: string
    office_name: string
}

export interface MyDocumentTransaction {
    transaction_no: string
    transaction_type: 'Default' | 'Forward' | 'Reply'
    routing: 'Single' | 'Multiple' | 'Sequential'
    status: 'Draft' | 'Processing' | 'Completed'
    created_at: string
}

export interface MyDocument {
    document_no: string
    document_type: string
    action_type: string
    origin_type: string
    subject: string
    remarks: string | null
    status: 'Draft' | 'Processing' | 'Archived'
    office_id: string
    office_name: string
    created_by_id: string
    created_by_name: string
    created_at: string
    updated_at: string
    transactions: MyDocumentTransaction[]
    recipients: MyDocumentRecipient[]
    signatories: MyDocumentSignatory[]
}

export interface MyDocumentsMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

export interface DocumentFilterOptions {
    document_types: string[]
    recipient_offices: { office_id: string; office_name: string }[]
}

export interface FetchParams {
    status?: string
    search?: string
    document_type?: string
    recipient_office_id?: string
    date_from?: string
    date_to?: string
    page?: number
    per_page?: number
}

export function useMyDocuments() {
    const documents = ref<MyDocument[]>([])
    const meta = ref<MyDocumentsMeta | null>(null)
    const isLoading = ref(false)
    const error = ref<string | null>(null)
    const filterOptions = ref<DocumentFilterOptions>({ document_types: [], recipient_offices: [] })
    const filtersLoading = ref(false)

    async function fetchMyDocuments(params: FetchParams = {}) {
        isLoading.value = true
        error.value = null

        const clean = Object.fromEntries(
            Object.entries(params).filter(([, v]) => v !== '' && v !== undefined && v !== null)
        )

        try {
            const { data } = await API.get('/documents', { params: clean })
            documents.value = data.data
            meta.value = {
                current_page: data.current_page,
                last_page: data.last_page,
                per_page: data.per_page,
                total: data.total,
                from: data.from,
                to: data.to,
            }
        } catch (err: any) {
            error.value = err?.response?.data?.message ?? 'Failed to fetch documents.'
            console.error('[useMyDocuments]', err)
        } finally {
            isLoading.value = false
        }
    }

    async function fetchFilterOptions() {
        filtersLoading.value = true
        try {
            const { data } = await API.get('/documents/filters')
            filterOptions.value = data
        } catch (err) {
            console.error('[useMyDocuments] fetchFilterOptions failed', err)
        } finally {
            filtersLoading.value = false
        }
    }

    const isEmpty = computed(() => !isLoading.value && documents.value.length === 0)
    const hasNextPage = computed(() => (meta.value?.current_page ?? 0) < (meta.value?.last_page ?? 0))
    const hasPrevPage = computed(() => (meta.value?.current_page ?? 1) > 1)

    return {
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
    }
}