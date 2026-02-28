import { computed, type Ref } from 'vue'
import type { Transaction } from '@/types/transaction'
import { useAuthStore } from '@/stores/auth'

// FI action types — all others are FA
const FI_TYPES = ['Dissemination of Information', 'Your Information']

/**
 * Derives which action buttons are visible for the current user
 * based on transaction type, routing, log history, and the user's office.
 */
export function useActionVisibility(transaction: Ref<Transaction | null>) {
    const auth = useAuthStore()

    // ── Base derivations ──────────────────────────────────────────────────────

    const logs = computed(() => transaction.value?.logs ?? [])
    const recipients = computed(() => transaction.value?.recipients ?? [])
    const myOfficeId = computed(() => auth.user?.office_id ?? null)
    const originOfficeId = computed(() => transaction.value?.office_id ?? null)

    const isOriginator = computed(() =>
        !!myOfficeId.value && originOfficeId.value === myOfficeId.value
    )

    const isFA = computed(() =>
        !FI_TYPES.includes(transaction.value?.action_type ?? '')
    )

    const isReleased = computed(() =>
        logs.value.some(l => l.status === 'Released')
    )

    const myOfficeHasReceived = computed(() =>
        logs.value.some(l => l.status === 'Received' && l.office_id === myOfficeId.value)
    )

    const myOfficeHasReturned = computed(() =>
        logs.value.some(l => l.status === 'Returned To Sender' && l.office_id === myOfficeId.value)
    )

    const myOfficeHasDone = computed(() =>
        logs.value.some(l => l.status === 'Done' && l.office_id === myOfficeId.value)
    )

    const myOfficeHasForwarded = computed(() =>
        logs.value.some(l => l.status === 'Forwarded' && l.office_id === myOfficeId.value)
    )

    const myOfficeHasTerminalAction = computed(() =>
        myOfficeHasDone.value || myOfficeHasForwarded.value || myOfficeHasReturned.value
    )

    const isProcessing = computed(() => transaction.value?.status === 'Processing')
    const isDraft = computed(() => transaction.value?.status === 'Draft')

    const docStatus = computed(() => transaction.value?.document?.status)

    // ── Recipient role checks ─────────────────────────────────────────────────

    /** Is my office an active recipient of ANY type (default, cc, bcc)? */
    const isActiveAnyRecipient = computed(() =>
        recipients.value.some(
            r => r.office_id === myOfficeId.value && r.isActive !== false
        )
    )

    /** Is my office an active DEFAULT recipient? */
    const isActiveDefaultRecipient = computed(() =>
        recipients.value.some(
            r => r.office_id === myOfficeId.value
                && r.recipient_type === 'default'
                && r.isActive !== false
        )
    )

    // ── Sequential turn guard ─────────────────────────────────────────────────

    /**
     * For sequential routing: only the lowest-sequence office that has no
     * terminal action can act. For non-sequential, always true.
     */
    const isActiveSequentialStep = computed(() => {
        if (transaction.value?.routing !== 'Sequential') return true

        const defaultRecipients = recipients.value
            .filter(r => r.recipient_type === 'default')
            .sort((a, b) => (a.sequence ?? 0) - (b.sequence ?? 0))

        for (const r of defaultRecipients) {
            const hasReceived = logs.value.some(
                l => l.status === 'Received' && l.office_id === r.office_id
            )
            if (!hasReceived) {
                return r.office_id === myOfficeId.value
            }
        }
        return false
    })

    // ── Button visibility ─────────────────────────────────────────────────────

    /**
     * RECEIVE — any active recipient type (default, cc, bcc)
     */
    const canReceive = computed(() =>
        isReleased.value &&
        isActiveAnyRecipient.value &&
        !myOfficeHasReceived.value &&
        isActiveSequentialStep.value &&
        isProcessing.value
    )

    /**
     * RELEASE — originator, transaction still Draft
     */
    const canRelease = computed(() =>
        isOriginator.value &&
        isDraft.value
    )

    /**
     * SUBSEQUENT RELEASE — active default recipient who has already received,
     * and the transaction is still Processing (not Returned/Completed).
     */
    const canSubsequentRelease = computed(() =>
        isActiveDefaultRecipient.value &&
        myOfficeHasReceived.value &&
        !myOfficeHasTerminalAction.value &&
        isProcessing.value
    )

    /**
     * MARK AS DONE — FA only, active default recipient who has received,
     * has not yet taken a terminal action.
     */
    const canMarkAsDone = computed(() =>
        isFA.value &&
        isActiveDefaultRecipient.value &&
        myOfficeHasReceived.value &&
        !myOfficeHasTerminalAction.value &&
        isProcessing.value
    )

    /**
     * FORWARD — active default recipient who has received, no terminal action yet.
     */
    const canForward = computed(() =>
        isActiveDefaultRecipient.value &&
        myOfficeHasReceived.value &&
        !myOfficeHasTerminalAction.value &&
        isProcessing.value
    )

    /**
     * RETURN TO SENDER — active default recipient who has received,
     * has not already returned.
     */
    const canReturn = computed(() =>
        isActiveDefaultRecipient.value &&
        myOfficeHasReceived.value &&
        !myOfficeHasReturned.value &&
        !myOfficeHasTerminalAction.value &&
        isProcessing.value
    )

    /**
     * REPLY — any active recipient type (default, cc, bcc) who has received.
     */
    const canReply = computed(() =>
        isActiveAnyRecipient.value &&
        myOfficeHasReceived.value &&
        isProcessing.value
    )

    /**
     * CLOSE — originator only, document not Draft and not already Closed.
     */
    const canClose = computed(() =>
        isOriginator.value &&
        docStatus.value !== 'Draft' &&
        docStatus.value !== 'Closed'
    )

    /**
     * MANAGE RECIPIENTS — originator, transaction Processing.
     */
    const canManageRecipients = computed(() =>
        isOriginator.value &&
        isProcessing.value
    )

    return {
        canReceive,
        canRelease,
        canSubsequentRelease,
        canMarkAsDone,
        canForward,
        canReturn,
        canReply,
        canClose,
        canManageRecipients,
        // expose for debug / display
        isOriginator,
        isReleased,
        isFA,
        myOfficeHasReceived,
        isProcessing,
        isDraft,
    }
}
