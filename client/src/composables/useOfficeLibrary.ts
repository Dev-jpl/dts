import API from '@/api'
import { ref } from 'vue'

export interface OfficeItem {
    item_id: string
    item_title: string
    item_desc: string
    return_value: {
        region_code?: string
        region_name?: string
        province_code?: string
        province_name?: string
        municipality_code?: string
        municipality_name?: string
        office: string
        office_code: string
        office_type: string
        superior_office_id?: string
        superior_office_name?: string
        hierarchy: { id: string; name: string; type: string }[]
    }
}

export function useOfficeLibrary() {
    const office = ref<OfficeItem[]>([])
    const isLoading = ref(false)
    const error = ref<string | null>(null)

    const fetchOffices = async () => {
        isLoading.value = true
        error.value = null

        try {
            const response = await API.get('/library/offices')
            const data = await response.data

            // Map API response into OfficeItem[]
            office.value = data.map((item: any) => ({
                item_id: item.item_id,
                item_title: item.item_title,
                item_desc: item.item_desc,
                return_value: {
                    ...item.return_value,
                    hierarchy: item.return_value.hierarchy || [],
                },
            }))
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch offices'
        } finally {
            isLoading.value = false
        }
    }

    return {
        office,
        isLoading,
        error,
        fetchOffices,
    }
}
