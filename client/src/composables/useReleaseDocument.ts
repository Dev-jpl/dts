import { ref } from "vue"
import API from "@/api"

export interface ReleasePayload {
    document_no: string
    office_id: string
    office_name: string
    assigned_personnel_id: string
    assigned_personnel_name: string
    remarks?: string
    routed_to?: Array<{ receivingOffice: string; department: string }>
}

export function useReleaseDocument(trxNo: string) {
    const loading = ref(false)
    const error = ref<string | null>(null)
    const success = ref<string | null>(null)

    async function releaseDocument(payload: ReleasePayload) {
        loading.value = true
        error.value = null
        success.value = null
        try {
            const { data } = await API.post(`/transactions/${trxNo}/release`, payload)
            success.value = data.message
            return data.data
        } catch (err: any) {
            error.value = err.message || "Failed to release document"
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        releaseDocument,
        loading,
        error,
        success,
    }
}
