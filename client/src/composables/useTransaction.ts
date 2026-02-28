import API from '@/api'
import type { DocumentNote, Transaction } from '@/types/transaction'
import { ref, watch } from 'vue'

/**
 * useTransaction
 *
 * KEY FIX for button reactivity:
 * Every action (receive, release, forward, return, etc.) returns the full
 * updated transaction from the backend. We call setTransaction() with that
 * response, which updates transaction.value (including .logs).
 * useActionVisibility reads from transaction.value.logs, so it reacts
 * immediately without needing a separate fetchTransaction() call.
 */
export function useTransaction(initial?: Transaction) {
    const transaction = ref<Transaction | null>(null)
    const loading = ref(false)
    const error = ref<string | null>(null)

    const trxLogs = ref<any[]>([])
    const logsLoading = ref(false)
    const logsError = ref<string | null>(null)

    const notes = ref<DocumentNote[]>([])
    const notesLoading = ref(false)

    // ── Core setters ──────────────────────────────────────────────────────────

    function setTransaction(newTransaction: Transaction) {
        transaction.value = newTransaction
    }

    async function fetchTransaction(transactionNo: string) {
        loading.value = true
        error.value = null
        try {
            const { data } = await API.get(`/transactions/${transactionNo}/show`)
            setTransaction(data.data)
        } catch (e: any) {
            error.value = e.message
        } finally {
            loading.value = false
        }
    }

    async function fetchTransactionLogs(trxNo: string) {
        logsLoading.value = true
        logsError.value = null
        try {
            const { data } = await API.get(`/transactions/${trxNo}/history`)
            trxLogs.value = data
        } catch (e: any) {
            logsError.value = e.message
        } finally {
            logsLoading.value = false
        }
    }

    // ── Official Notes ────────────────────────────────────────────────────────

    async function fetchNotes(docNo: string) {
        notesLoading.value = true
        try {
            const { data } = await API.get(`/documents/${docNo}/notes`)
            notes.value = data.data
        } finally {
            notesLoading.value = false
        }
    }

    async function postNote(docNo: string, payload: { transaction_no: string; note: string }) {
        const { data } = await API.post(`/documents/${docNo}/notes`, payload)
        notes.value.unshift(data.data)
        return data
    }

    // ── Actions — each updates transaction.value directly from response ───────

    async function releaseDocument(trxNo: string, payload: {
        remarks?: string | null
        routed_office?: any
    }) {
        const { data } = await API.post(`/transactions/${trxNo}/release`, payload)
        if (data.data) setTransaction(data.data)
        await fetchTransactionLogs(trxNo)
        return data
    }

    async function subsequentRelease(trxNo: string, payload: {
        target_office_id: string
        target_office_name: string
        remarks?: string | null
    }) {
        const { data } = await API.post(`/transactions/${trxNo}/subsequent-release`, payload)
        if (data.data) setTransaction(data.data)
        await fetchTransactionLogs(trxNo)
        return data
    }

    async function receiveDocument(trxNo: string, payload: {
        remarks?: string | null
    }) {
        try {
            const { data } = await API.post(`/transactions/${trxNo}/receive`, payload)
            if (data.data) setTransaction(data.data)
            await fetchTransactionLogs(trxNo)
            return data
        } catch (e: any) {
            const msg = e.response?.data?.message || e.message || 'Failed to receive document'
            throw new Error(msg)
        }
    }

    async function markAsDone(trxNo: string, payload: {
        remarks?: string | null
    }) {
        const { data } = await API.post(`/transactions/${trxNo}/done`, payload)
        if (data.data) setTransaction(data.data)
        await fetchTransactionLogs(trxNo)
        return data
    }

    async function forwardDocument(trxNo: string, payload: {
        routed_office: { id: string; office_name: string }
        action: { action: string }
        remarks?: string | null
    }) {
        try {
            const { data } = await API.post(`/transactions/${trxNo}/forward`, payload)
            if (data.data) setTransaction(data.data)
            await fetchTransactionLogs(trxNo)
            return data
        } catch (e: any) {
            const msg = e.response?.data?.message || e.message || 'Failed to forward document'
            throw new Error(msg)
        }
    }

    async function returnToSender(trxNo: string, payload: {
        reason: string
        remarks?: string | null
    }) {
        try {
            const { data } = await API.post(`/transactions/${trxNo}/return`, payload)
            if (data.data) setTransaction(data.data)
            await fetchTransactionLogs(trxNo)
            return data
        } catch (e: any) {
            const msg = e.response?.data?.message || e.message || 'Failed to return document'
            throw new Error(msg)
        }
    }

    async function replyToDocument(trxNo: string, payload: {
        subject: string
        action_type: string
        document_type: string
        remarks?: string | null
        recipients: Array<{ office_id: string; office_name: string; recipient_type: string }>
    }) {
        const { data } = await API.post(`/transactions/${trxNo}/reply`, payload)
        if (data.data) setTransaction(data.data)
        await fetchTransactionLogs(trxNo)
        return data
    }

    async function manageRecipients(trxNo: string, payload: {
        add?: Array<{ office_id: string; office_name: string; recipient_type: string; sequence?: number }>
        remove?: number[]
        reorder?: Array<{ id: number; sequence: number }>
    }) {
        const { data } = await API.patch(`/transactions/${trxNo}/recipients`, payload)
        if (data.data) setTransaction(data.data)
        await fetchTransactionLogs(trxNo)
        return data
    }

    async function closeDocument(docNo: string, payload: {
        remarks: string
    }) {
        const { data } = await API.post(`/documents/${docNo}/close`, payload)
        return data
    }

    // ── Helper: full refresh (transaction + formatted logs) ───────────────────
    async function refreshTransaction(trxNo: string) {
        await Promise.all([
            fetchTransaction(trxNo),
            fetchTransactionLogs(trxNo),
        ])
    }

    // ── Auto-fetch logs when transaction changes ───────────────────────────────
    watch(
        () => transaction.value?.transaction_no,
        (trxNo) => {
            if (trxNo) fetchTransactionLogs(trxNo)
        },
        { immediate: true }
    )

    return {
        // Transaction
        transaction,
        setTransaction,
        fetchTransaction,
        loading,
        error,

        // Logs
        trxLogs,
        logsLoading,
        logsError,
        fetchTransactionLogs,

        // Official Notes
        notes,
        notesLoading,
        fetchNotes,
        postNote,

        // Actions
        releaseDocument,
        subsequentRelease,
        receiveDocument,
        markAsDone,
        forwardDocument,
        returnToSender,
        replyToDocument,
        manageRecipients,
        closeDocument,

        // Utility
        refreshTransaction,
    }
}
