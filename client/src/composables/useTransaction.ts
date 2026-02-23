import API from '@/api'
import type { Transaction } from '@/types/transaction'
import { ref, computed, watch } from 'vue'


export function useTransaction(initial?: Transaction) {
    const transaction = ref<Transaction | null>(null)
    const loading = ref(false)
    const error = ref<string | null>(null)

    // Logs state
    const trxLogs = ref<any[]>([])
    const logsLoading = ref(false)
    const logsError = ref<string | null>(null)

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

    async function releaseDocument(trxNo: string, payload: {
        remarks?: string | null
        routed_office?: any
    }) {
        try {
            const { data } = await API.post(`/transactions/${trxNo}/release`, payload)
            // Refresh logs after release
            await fetchTransactionLogs(trxNo)
            return data
        } catch (e: any) {
            throw e
        }
    }

    // Auto-fetch logs when transaction changes
    watch(
        () => transaction.value?.transaction_no,
        (trxNo) => {
            if (trxNo) {
                fetchTransactionLogs(trxNo)
            }
        },
        { immediate: true }
    )

    return {
        transaction,
        setTransaction,
        fetchTransaction,
        loading,
        error,
        trxLogs,
        logsLoading,
        logsError,
        fetchTransactionLogs,
        releaseDocument,
    }
}
