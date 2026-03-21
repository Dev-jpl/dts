<script setup lang="ts">
import ScrollableContainer from '@/components/ScrollableContainer.vue'
import ActivityFeedWidget from '@/components/dashboard/ActivityFeedWidget.vue'
import ForActionWidget from '@/components/dashboard/ForActionWidget.vue'
import MyOutgoingWidget from '@/components/dashboard/MyOutgoingWidget.vue'
import OverdueWidget from '@/components/dashboard/OverdueWidget.vue'
import PendingReleaseWidget from '@/components/dashboard/PendingReleaseWidget.vue'
import QuickStatsWidget from '@/components/dashboard/QuickStatsWidget.vue'
import SystemHealthWidget from '@/components/dashboard/SystemHealthWidget.vue'
import TeamPerformanceWidget from '@/components/dashboard/TeamPerformanceWidget.vue'
import CloseDocumentModal from '@/components/view-document/CloseDocumentModal.vue'
import ReceiveModal from '@/components/view-document/ReceiveModal.vue'
import { useDashboard } from '@/composables/useDashboard'
import { useAuthStore } from '@/stores/auth'
import { computed, ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'

// Debug logging
console.log('🚀 MainDashboardView script executing')
onMounted(() => console.log('✅ MainDashboardView mounted'))

const authStore = useAuthStore()
const router = useRouter()

const {
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
    refresh,
    refreshStats,
} = useDashboard()

// ── Role checks ────────────────────────────────────────────────────────────────

const userRole = computed(() => authStore.user?.role ?? 'user')
const isTeamVisible = computed(() => ['superior', 'admin'].includes(userRole.value))
const isAdmin = computed(() => userRole.value === 'admin')

// ── Header stats (derived from stats endpoint) ─────────────────────────────────

const headerStats = computed(() => ({
    overdue: stats.value?.current.overdue ?? 0,
    forAction: stats.value?.current.for_action ?? 0,
    sent: stats.value?.current.sent ?? 0,
    completed: stats.value?.current.completed ?? 0,
}))

// ── Receive modal state ────────────────────────────────────────────────────────

const receiveOpen = ref(false)
const receiveTrxNo = ref('')

function openReceive(trxNo: string) {
    receiveTrxNo.value = trxNo
    receiveOpen.value = true
}

function toggleReceiveModal() {
    receiveOpen.value = !receiveOpen.value
    if (!receiveOpen.value) receiveTrxNo.value = ''
}

function onReceived() {
    receiveOpen.value = false
    receiveTrxNo.value = ''
    refresh()
}

// ── Close modal state ──────────────────────────────────────────────────────────

const closeOpen = ref(false)
const closeDocNo = ref('')

function openClose(docNo: string) {
    closeDocNo.value = docNo
    closeOpen.value = true
}

function toggleCloseModal() {
    closeOpen.value = !closeOpen.value
    if (!closeOpen.value) closeDocNo.value = ''
}

function onClosed() {
    closeOpen.value = false
    closeDocNo.value = ''
    refresh()
}

// ── Release: navigate to ViewDocument (full form required for initial release) ─

function handleRelease(trxNo: string) {
    router.push({ name: 'view-document', params: { trxNo } })
}

// ── Refresh stats when period changes ─────────────────────────────────────────

watch(selectedPeriod, () => refreshStats())
</script>

<template>

    <ScrollableContainer>
        <!-- ── Header ─────────────────────────────────────────────────────────── -->
        <div class="w-full">
            <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">Saved routing configurations to speed up document creation.</p>
        </div>
        <div hidden
            class="grid grid-cols-1 sm:grid-cols-12 gap-5 p-4 sm:h-[150px] w-full rounded-xl bg-gradient-to-r from-teal-900 to-teal-700">
            <!-- Welcome block -->
            <div class="flex flex-col justify-between col-span-1 sm:col-span-4">
                <h1 class="flex text-2xl font-bold text-lime-500">
                    WELCOME!
                    <span class="ml-2 text-white">{{ authStore.user?.username || authStore.user?.name }}</span>
                </h1>
                <p class="hidden text-xs text-teal-200 sm:block">
                    {{ authStore.user?.office_name }}
                </p>
            </div>

            <!-- Quick stat cards (top line overview) -->
            <div class="grid grid-cols-2 col-span-1 gap-4 sm:col-span-8 sm:grid-cols-4">
                <div class="flex flex-col justify-between p-4 shadow-2xl bg-white/20 rounded-xl">
                    <div class="text-sm font-light text-gray-100">Overdue</div>
                    <div class="text-2xl font-bold text-red-300">{{ isLoading ? '—' : headerStats.overdue }}</div>
                </div>
                <div class="flex flex-col justify-between p-4 shadow-2xl bg-white/20 rounded-xl">
                    <div class="text-sm font-light text-gray-100">For Action</div>
                    <div class="text-2xl font-bold text-amber-300">{{ isLoading ? '—' : headerStats.forAction }}</div>
                </div>
                <div class="flex flex-col justify-between p-4 shadow-2xl bg-white/20 rounded-xl">
                    <div class="text-sm font-light text-gray-100">Sent</div>
                    <div class="text-2xl font-bold text-white">{{ isLoading ? '—' : headerStats.sent }}</div>
                </div>
                <div class="flex flex-col justify-between p-4 shadow-2xl bg-white/20 rounded-xl">
                    <div class="text-sm font-light text-gray-100">Completed</div>
                    <div class="text-2xl font-bold text-lime-300">{{ isLoading ? '—' : headerStats.completed }}</div>
                </div>
            </div>
        </div>

        <!-- ── Widget stack ───────────────────────────────────────────────────── -->
        <div class="grid w-full grid-cols-12 gap-5 mt-5">

            <!-- 5. QUICK STATS -->
            <QuickStatsWidget class="col-span-12" :stats="stats" :loading="isLoading" v-model:period="selectedPeriod" />

            <!-- 2. FOR ACTION -->
            <ForActionWidget class="col-span-6" :data="forAction" :loading="isLoading" @receive="openReceive" />

            <div class="grid col-span-6 gap-5 gird-cols-12">
                <!-- 1. OVERDUE (only shown if there are overdue items or loading) -->
                <OverdueWidget class="col-span-6" :data="overdue" :loading="isLoading" />

                <!-- 3. PENDING RELEASE -->
                <PendingReleaseWidget class="col-span-6" :data="drafts" :loading="isLoading" @release="handleRelease" />
            </div>

            <!-- 6. ACTIVITY FEED -->
            <ActivityFeedWidget class="col-span-4" :data="activity" :loading="isLoading" />

            <!-- 4. MY OUTGOING -->
            <MyOutgoingWidget class="col-span-8" :data="outgoing" :loading="isLoading" @close="openClose" />

            <!-- 7. TEAM PERFORMANCE (Superior + Admin only) -->
            <TeamPerformanceWidget class="col-span-12" v-if="isTeamVisible" :data="team" :loading="isLoading" />

            <!-- 8. SYSTEM HEALTH (Admin only) -->
            <SystemHealthWidget class="col-span-12" v-if="isAdmin" :data="system" :loading="isLoading" />

        </div>

        <!-- ── Quick action modals ─────────────────────────────────────────────── -->

        <!-- Receive modal -->
        <ReceiveModal v-if="receiveOpen && receiveTrxNo" :isOpen="receiveOpen" :toggleReceiveModal="toggleReceiveModal"
            :trxNo="receiveTrxNo" @received="onReceived" />

        <!-- Close document modal -->
        <CloseDocumentModal v-if="closeOpen && closeDocNo" :isOpen="closeOpen" :toggleModal="toggleCloseModal"
            :docNo="closeDocNo" @closed="onClosed" />

    </ScrollableContainer>
</template>
