import API from '@/api'
import { useAuthStore } from '@/stores/auth'
import { onMounted, onUnmounted, ref } from 'vue'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface DashboardForActionItem {
    id: number
    document_no: string
    transaction_no: string
    office_name: string
    routed_office_id: string
    status: string
    urgency_level: string | null
    created_at: string
    days_overdue: number | null
    is_overdue: boolean
    transaction: {
        subject: string
        document_type: string
        action_type: string
        routing: string
        office_name: string
    } | null
}

export interface DashboardOverdueItem {
    id: number
    document_no: string
    transaction_no: string
    office_name: string
    status: string
    days_overdue: number
    due_date: string | null
    created_at: string
    transaction: {
        subject: string
        document_type: string
        action_type: string
        urgency_level: string | null
    } | null
}

export interface DashboardDraftItem {
    transaction_no: string
    document_no: string
    subject: string
    document_type: string
    action_type: string
    routing: string
    updated_at: string
    document: { document_no: string } | null
}

export interface DashboardOutgoingItem {
    document_no: string
    subject: string
    document_type: string
    action_type: string
    status: string
    updated_at: string
    transactions: Array<{
        transaction_no: string
        transaction_type: string
        status: string
        routing: string
        urgency_level: string
        recipient_names: string[]
        progress: {
            total_recipients: number
            received: number
            completed: number
            pending: number
        }
        latest_activity: {
            status: string
            office_name: string
            created_at: string
        } | null
        recipients: Array<{
            office_id: string
            office_name: string
            recipient_type: string
            sequence: number
            isActive: boolean
            logs: Array<{
                status: string
                created_at: string
            }>
        }>
    }>
}

export interface DashboardStats {
    period: string
    current: {
        sent: number
        received: number
        for_action: number
        completed: number
        returned: number
        overdue: number
    }
    previous: {
        sent: number
        received: number
        for_action: number
        completed: number
        returned: number
        overdue: number
    }
    delta: {
        sent: number
        received: number
        for_action: number
        completed: number
        returned: number
        overdue: number
    }
}

export interface ActivityItem {
    id: number
    status: string
    office_name: string
    document_no: string
    transaction_no: string
    subject: string | null
    document_type: string | null
    created_at: string
}

export interface TeamRow {
    office_id: string
    office_name: string
    total_received: number
    overdue_count: number
    avg_turnaround: number | null
    on_time_rate: number | null
}

export interface SystemStats {
    total_processing: number
    total_overdue: number
    total_documents_today: number
    completion_rate_30d: number
    top_overdue_offices: Array<{
        office_id: string
        office_name: string
        overdue_count: number
    }>
}

// ── Composable ────────────────────────────────────────────────────────────────

export function useDashboard() {
    const authStore = useAuthStore()

    // State
    const forAction   = ref<DashboardForActionItem[]>([])
    const overdue     = ref<DashboardOverdueItem[]>([])
    const drafts      = ref<DashboardDraftItem[]>([])
    const outgoing    = ref<{ active: DashboardOutgoingItem[]; returned: DashboardOutgoingItem[]; completed: DashboardOutgoingItem[] }>({
        active: [],
        returned: [],
        completed: [],
    })
    const stats         = ref<DashboardStats | null>(null)
    const activity      = ref<ActivityItem[]>([])
    const team          = ref<TeamRow[]>([])
    const system        = ref<SystemStats | null>(null)
    const isLoading     = ref(false)
    const selectedPeriod = ref<'week' | 'month' | 'quarter' | 'year'>('month')

    let pollingTimer: ReturnType<typeof setInterval> | null = null
    let echoChannel: any = null

    // ── Fetch helpers ──────────────────────────────────────────────────────────

    async function fetchForAction() {
        const { data } = await API.get('/dashboard/for-action', { params: { per_page: 10 } })
        forAction.value = data.data?.data ?? []
    }

    async function fetchOverdue() {
        const { data } = await API.get('/dashboard/overdue', { params: { per_page: 10 } })
        overdue.value = data.data?.data ?? []
    }

    async function fetchDrafts() {
        const { data } = await API.get('/dashboard/drafts', { params: { per_page: 10 } })
        drafts.value = data.data?.data ?? []
    }

    async function fetchOutgoing() {
        const [activeRes, returnedRes, completedRes] = await Promise.all([
            API.get('/dashboard/outgoing', { params: { status: 'Active',    per_page: 10 } }),
            API.get('/dashboard/outgoing', { params: { status: 'Returned',  per_page: 10 } }),
            API.get('/dashboard/outgoing', { params: { status: 'Completed', per_page: 10 } }),
        ])
        outgoing.value = {
            active:    activeRes.data.data?.data    ?? [],
            returned:  returnedRes.data.data?.data  ?? [],
            completed: completedRes.data.data?.data ?? [],
        }
    }

    async function fetchStats() {
        const { data } = await API.get('/dashboard/stats', { params: { period: selectedPeriod.value } })
        stats.value = data
    }

    async function fetchActivity() {
        const { data } = await API.get('/dashboard/activity')
        activity.value = data.data ?? []
    }

    async function fetchTeam() {
        try {
            const { data } = await API.get('/dashboard/team')
            team.value = data.data ?? []
        } catch {
            // 403 for non-superior/admin — silently ignore
            team.value = []
        }
    }

    async function fetchSystem() {
        try {
            const { data } = await API.get('/dashboard/system')
            system.value = data.data ?? null
        } catch {
            // 403 for non-admin — silently ignore
            system.value = null
        }
    }

    // ── Main refresh ───────────────────────────────────────────────────────────

    async function refresh() {
        isLoading.value = true
        try {
            await Promise.all([
                fetchForAction(),
                fetchOverdue(),
                fetchDrafts(),
                fetchOutgoing(),
                fetchStats(),
                fetchActivity(),
                fetchTeam(),
                fetchSystem(),
            ])
        } catch (e) {
            console.error('[useDashboard] refresh error:', e)
        } finally {
            isLoading.value = false
        }
    }

    // Refresh stats when period changes (without full reload)
    async function refreshStats() {
        await fetchStats()
    }

    // ── WebSocket subscription ─────────────────────────────────────────────────

    function subscribeWebSocket() {
        const officeId = authStore.user?.office_id
        if (!officeId) return

        try {
            const echo = (window as any).Echo
            if (!echo) return

            echoChannel = echo.private(`office.${officeId}`)
            echoChannel.listen('DocumentActivityLogged', () => {
                refresh()
            })
        } catch (e) {
            // Echo not configured — polling fallback handles refresh
        }
    }

    function unsubscribeWebSocket() {
        try {
            const officeId = authStore.user?.office_id
            if (officeId && (window as any).Echo) {
                ;(window as any).Echo.leave(`office.${officeId}`)
            }
        } catch {
            // ignore
        }
        echoChannel = null
    }

    // ── Lifecycle ──────────────────────────────────────────────────────────────

    onMounted(() => {
        refresh()
        subscribeWebSocket()
        // 60s polling fallback — always runs
        pollingTimer = setInterval(() => refresh(), 60_000)
    })

    onUnmounted(() => {
        unsubscribeWebSocket()
        if (pollingTimer) clearInterval(pollingTimer)
    })

    return {
        // State
        forAction,
        overdue,
        drafts,
        outgoing,
        stats,
        activity,
        team,
        system,
        isLoading,
        selectedPeriod,

        // Actions
        refresh,
        refreshStats,
        fetchForAction,
        fetchOverdue,
        fetchDrafts,
        fetchOutgoing,
        fetchStats,
        fetchActivity,
    }
}
