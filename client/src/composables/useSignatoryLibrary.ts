import { ref } from 'vue'
import API from '@/api'

export interface Signatory {
    name: string
    position: string
    office: string
    office_id: string
}

export function useSignatoriesLibrary() {
    const data = ref<any[]>([])
    const isLoading = ref(false)
    const error = ref<string | null>(null)

    async function fetchSignatories() {
        isLoading.value = true
        error.value = null
        try {
            const response = await API.get('/library/signatories')
            // console.log(data.va);

            data.value = response.data
        } catch (err) {
            error.value = "Failed to fetch signatories"
        } finally {
            isLoading.value = false
        }
    }

    return { data, fetchSignatories, isLoading, error }
}
