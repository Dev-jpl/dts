import { ref } from 'vue'
import API from '@/api'

export interface ReceivePayload {
    remarks?: string | null
}

export function useReceiveDocument(trxNo: string) {
    const loading = ref(false)
    const error = ref<string | null>(null)
    const success = ref<string | null>(null)

    async function receiveDocument(payload: ReceivePayload = {}) {
        loading.value = true
        error.value = null
        success.value = null

        try {
            const { data } = await API.post(`/transactions/${trxNo}/receive`, payload)
            success.value = data.message
            return data.data
        } catch (err: any) {
            // Surface the backend validation/guard message if present
            const serverMessage =
                err.response?.data?.message || err.message || 'Failed to receive document'
            error.value = serverMessage
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        receiveDocument,
        loading,
        error,
        success,
    }
}