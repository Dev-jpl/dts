import { computed } from 'vue'
import type { Transaction } from '@/types/transaction'
import { useAuthStore } from '@/stores/auth'

/**
 * Derives which action buttons are visible for the current user
 * based on transaction type, routing, log history, and the user's office.
 */
export function useActionVisibility(transaction: ReturnType<typeof computed<Transaction | null>>) {
    const auth = useAuthStore()

    // ── Derived log state ─────────────────────────────────────────────────────

    /** All logs for this transaction */
    const logs = computed(() => transaction.value?.logs ?? [])

    /** Current user's office_id */
    const myOfficeId = computed(() => auth.user?.office_id ?? null)

    /** The originating office of the transaction */
    const originOfficeId = computed(() => transaction.value?.office_id ?? null)

    /** Is the current user the originator? */
    const isOriginator = computed(() =>
        myOfficeId.value && originOfficeId.value === myOfficeId.value
    )

    /** Has this transaction been Released at least once? */
    const isReleased = computed(() =>
        logs.value.some(log => log.status === 'Released')
    )

    /** Has the current user's office already received this document? */
    const myOfficeHasReceived = computed(() =>
        logs.value.some(
            log => log.status === 'Received' && log.office_id === myOfficeId.value
        )
    )

    /** Has the current user's office already returned this document? */
    const myOfficeHasReturned = computed(() =>
        logs.value.some(
            log => log.status === 'Returned To Sender' && log.office_id === myOfficeId.value
        )
    )

    /** Is the transaction archived/completed? */
    const isCompleted = computed(() =>
        transaction.value?.status === 'Completed'
    )

    /** Is this office a registered default recipient? */
    const isRecipient = computed(() =>
        (transaction.value?.recipients ?? []).some(
            r => r.recipient_type === 'default'
                && r.office_id === myOfficeId.value
                && r.isActive !== false   // ← treat undefined as active (backward compat)
        )
    )

    // ── Sequential: is it currently this office's turn? ───────────────────────
    const isActiveSequentialStep = computed(() => {
        if (transaction.value?.routing !== 'Sequential') return true // non-sequential, not restricted

        const defaultRecipients = (transaction.value?.recipients ?? [])
            .filter(r => r.recipient_type === 'default')
            .sort((a, b) => (a.sequence ?? 0) - (b.sequence ?? 0))

        for (const recipient of defaultRecipients) {
            const stepReceived = logs.value.some(
                l => l.status === 'Received' && l.office_id === recipient.office_id
            )
            if (!stepReceived) {
                // The first un-received step is the active one
                return recipient.office_id === myOfficeId.value
            }
        }
        return false
    })

    // ── All recipients done (for archive eligibility) ────────────────────────
    const allRecipientsDone = computed(() => {
        const routing = transaction.value?.routing
        const defaultRecipients = (transaction.value?.recipients ?? [])
            .filter(r => r.recipient_type === 'default')

        if (!defaultRecipients.length) return false

        return defaultRecipients.every(recipient => {
            const received = logs.value.some(
                l => l.status === 'Received' && l.office_id === recipient.office_id
            )
            const returned = logs.value.some(
                l => l.status === 'Returned To Sender' && l.office_id === recipient.office_id
            )
            return received || returned
        })
    })

    // ── Button visibility ─────────────────────────────────────────────────────

    /**
     * RECEIVE
     * Show when:
     * - Document has been released
     * - Current office is a recipient
     * - Current office has NOT already received
     * - (Sequential) It's this office's turn
     * - Transaction is not completed
     */
    const canReceive = computed(() =>
        isReleased.value &&
        isRecipient.value &&
        !myOfficeHasReceived.value &&
        !myOfficeHasReturned.value &&
        isActiveSequentialStep.value &&
        !isCompleted.value
    )

    /**
     * RELEASE
     * Show when:
     * - Current office is the originator
     * - Transaction has not been Released yet (prevent double-release)
     *   OR you may want to allow re-release after return — adjust if needed
     */
    const canRelease = computed(() =>
        isOriginator.value &&
        !isReleased.value &&
        !isCompleted.value
    )

    /**
     * FORWARD
     * Show when:
     * - Current office has received the document
     * - Transaction is not completed
     */
    const canForward = computed(() =>
        myOfficeHasReceived.value &&
        !isCompleted.value
    )

    /**
     * RETURN TO SENDER
     * Show when:
     * - Current office has received the document
     * - Has not already returned
     * - Transaction is not completed
     */
    const canReturn = computed(() =>
        myOfficeHasReceived.value &&
        !myOfficeHasReturned.value &&
        !isCompleted.value
    )

    /**
     * REPLY
     * Show when:
     * - Current office has received the document
     * - Transaction is not completed
     */
    const canReply = computed(() =>
        myOfficeHasReceived.value &&
        !isCompleted.value
    )

    /**
     * ARCHIVE
     * Show when:
     * - Current office is the originator (only origin can archive)
     * - All recipients have received or returned
     * - Transaction is not already completed
     */
    const canArchive = computed(() =>
        isOriginator.value &&
        allRecipientsDone.value &&
        !isCompleted.value
    )

    return {
        canReceive,
        canRelease,
        canForward,
        canReturn,
        canReply,
        canArchive,
        // expose for debugging / display purposes
        isOriginator,
        isReleased,
        myOfficeHasReceived,
        isCompleted,
    }
}