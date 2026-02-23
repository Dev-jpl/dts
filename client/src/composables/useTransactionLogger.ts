import { ref } from "vue"
import API from "@/api"

export interface TransactionLogPayload {
    document_no: string
    status: "Profiled" | "Received" | "Released" | "Archived" | "Returned To Sender" | "Forwarded"
    action_taken?: string
    activity: string
    remarks?: string
    assigned_personnel_id: string
    assigned_personnel_name: string
    office_id: string
    office_name: string
}

export function useTransactionLogger(trxNo: string) {
    const loading = ref(false)
    const error = ref<string | null>(null)
    const success = ref<string | null>(null)

    async function logTransaction(payload: TransactionLogPayload) {
        loading.value = true
        error.value = null
        success.value = null
        try {
            const { data } = await API.post(`/transactions/${trxNo}/logs`, payload)
            success.value = data.message
            return data.data
        } catch (err: any) {
            error.value = err.message || "Failed to log transaction"
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        logTransaction,
        loading,
        error,
        success,
    }
}
