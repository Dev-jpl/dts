// composables/useReceivedDocuments.ts
import { ref, computed } from 'vue'
import API from '@/api'
import type { MyDocumentRecipient, MyDocumentSignatory, MyDocumentsMeta } from './useMyDocuments'

// ─── Types ─────────────────────────────────────────────────────────────────────
export interface ReceivedTransaction {
    transaction_no: string
    transaction_type: 'Default' | 'Forward' | 'Reply'
    routing: 'Single' | 'Multiple' | 'Sequential'
    document_no: string
    document_type: string
    action_type: string
    origin_type: string
    subject: string
    remarks: string | null
    status: 'Draft' | 'Processing' | 'Completed'
    office_id: string
    office_name: string       // sender office
    created_by_name: string   // sender name
    created_at: string
    updated_at: string

    // Relations
    document: {
        document_no: string
        status: string
        created_at: string
    }
    recipients: MyDocumentRecipient[]
    signatories: MyDocumentSignatory[]
}

// ─── Composable ────────────────────────────────────────────────────────────────
export function useReceivedDocuments() {
    const transactions = ref<ReceivedTransaction[]>([])
    const meta = ref<MyDocumentsMeta | null>(null)
    const isLoading = ref(false)
    const error = ref<string | null>(null)

    async function fetchReceivedDocuments(params: {
        search?: string
        page?: number
        per_page?: number
    } = {}) {
        isLoading.value = true
        error.value = null

        try {
            const { data } = await API.get('/documents/received', { params })

            transactions.value = data.data
            meta.value = {
                current_page: data.current_page,
                last_page: data.last_page,
                per_page: data.per_page,
                total: data.total,
                from: data.from,
                to: data.to,
            }
        } catch (err: any) {
            error.value = err?.response?.data?.message ?? 'Failed to fetch received documents.'
            console.error('[useReceivedDocuments]', err)
        } finally {
            isLoading.value = false
        }
    }

    const isEmpty = computed(() => !isLoading.value && transactions.value.length === 0)

    return {
        transactions,
        meta,
        isLoading,
        error,
        isEmpty,
        fetchReceivedDocuments,
    }
}