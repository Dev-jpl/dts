// useTransactionLogs.ts
import { ref, watch } from "vue"
import axios from "axios"

export function useTransactionLogs(trxNoRef: any) {
    const historyLogs = ref<any[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)

    async function fetchLogs(trxNo: string) {
        loading.value = true
        error.value = null
        try {
            const response = await axios.get(`/api/transactions/${trxNo}/history`)
            historyLogs.value = response.data
        } catch (err: any) {
            error.value = err.message || "Failed to fetch transaction logs"
        } finally {
            loading.value = false
        }
    }

    watch(trxNoRef, (newTrxNo) => {
        if (newTrxNo) {
            fetchLogs(newTrxNo)
        }
    }, { immediate: true })

    return { historyLogs, loading, error, refresh: () => fetchLogs(trxNoRef.value) }
}
