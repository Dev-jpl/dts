import { ref } from 'vue'

/**
 * useQuickAction
 *
 * Thin composable that manages which quick-action modal is open from the dashboard.
 * Dashboard supports ONLY: Receive, Release, Close (Rule 22).
 * All other actions must navigate to ViewDocument (Rule 23).
 */
export function useQuickAction() {
    // Receive
    const receiveOpen    = ref(false)
    const receiveTrxNo   = ref<string>('')

    // Release confirm (simple confirm, not full modal)
    const releaseOpen    = ref(false)
    const releaseTrxNo   = ref<string>('')

    // Close
    const closeOpen      = ref(false)
    const closeDocNo     = ref<string>('')

    function openReceive(trxNo: string) {
        receiveTrxNo.value = trxNo
        receiveOpen.value  = true
    }

    function closeReceive() {
        receiveOpen.value  = false
        receiveTrxNo.value = ''
    }

    function openRelease(trxNo: string) {
        releaseTrxNo.value = trxNo
        releaseOpen.value  = true
    }

    function closeRelease() {
        releaseOpen.value  = false
        releaseTrxNo.value = ''
    }

    function openClose(docNo: string) {
        closeDocNo.value = docNo
        closeOpen.value  = true
    }

    function closeClose() {
        closeOpen.value  = false
        closeDocNo.value = ''
    }

    return {
        // Receive
        receiveOpen,
        receiveTrxNo,
        openReceive,
        closeReceive,

        // Release
        releaseOpen,
        releaseTrxNo,
        openRelease,
        closeRelease,

        // Close
        closeOpen,
        closeDocNo,
        openClose,
        closeClose,
    }
}
