import API from '@/api'
import type { Transaction } from '@/types/transaction'
import { ref, watch } from 'vue'

/**
 * useTransaction
 *
 * KEY FIX for button reactivity:
 * Every action (receive, release, forward, return) now returns the full
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

    const comments = ref<any[]>([])
    const commentsLoading = ref(false)
    const commentsError = ref<string | null>(null)

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

    // ── Comments ──────────────────────────────────────────────────────────────

    async function fetchComments(trxNo: string) {
        commentsLoading.value = true
        commentsError.value = null
        try {
            const { data } = await API.get(`/transactions/${trxNo}/comments`)
            comments.value = data.data
        } catch (e: any) {
            commentsError.value = e.message
        } finally {
            commentsLoading.value = false
        }
    }

    async function postComment(trxNo: string, comment: string) {
        const { data } = await API.post(`/transactions/${trxNo}/comments`, { comment })
        comments.value.push(data.data)
        return data
    }

    // ── Actions — each updates transaction.value directly from response ───────
    // This is the key fix: the backend now returns the full updated transaction
    // after each action. We call setTransaction() with it, which makes
    // transaction.value.logs reactive and triggers useActionVisibility to recompute.

    async function releaseDocument(trxNo: string, payload: {
        remarks?: string | null
        routed_office?: any
    }) {
        try {
            const { data } = await API.post(`/transactions/${trxNo}/release`, payload)
            // Update transaction state from response (includes updated logs)
            if (data.data) setTransaction(data.data)
            await fetchTransactionLogs(trxNo)
            return data
        } catch (e: any) {
            throw e
        }
    }

    async function receiveDocument(trxNo: string, payload: {
        remarks?: string | null
    }) {
        try {
            const { data } = await API.post(`/transactions/${trxNo}/receive`, payload)
            // ✅ Update transaction.value with fresh data including new logs
            if (data.data) setTransaction(data.data)
            await fetchTransactionLogs(trxNo)
            return data
        } catch (e: any) {
            const msg = e.response?.data?.message || e.message || 'Failed to receive document'
            throw new Error(msg)
        }
    }

    async function returnToSender(trxNo: string, payload: {
        remarks: string
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

    async function forwardDocument(trxNo: string, payload: {
        routed_office: { id: string; office_name: string }
        action: { action: string }       // ← ADD this field
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
            if (trxNo) {
                fetchTransactionLogs(trxNo)
                fetchComments(trxNo)
            }
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

        // Logs (formatted for HistoryLogs component)
        trxLogs,
        logsLoading,
        logsError,
        fetchTransactionLogs,

        // Actions
        releaseDocument,
        receiveDocument,
        returnToSender,
        forwardDocument,

        // Comments
        comments,
        commentsLoading,
        commentsError,
        fetchComments,
        postComment,

        // Utility
        refreshTransaction,
    }
}