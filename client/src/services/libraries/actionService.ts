import API from '@/api'

export interface ActionReturnValue {
    id: number
    action: string
    type: 'FA' | 'FI'
    reply_is_terminal: boolean
    requires_proof: boolean
    proof_description: string | null
    default_urgency_level: string | null
}

export interface ActionLibraryItem {
    item_id: number
    item_title: string
    item_desc: string
    return_value: ActionReturnValue
}

export async function fetchActions(): Promise<ActionLibraryItem[]> {
    const { data } = await API.get('/library/actions')
    return data as ActionLibraryItem[]
}