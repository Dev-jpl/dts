<script setup lang="ts">
import { ref } from 'vue'
import LargeModal from '../ui/modals/LargeModal.vue'
import FrmLabel from '../ui/labels/FrmLabel.vue'
import Textarea from '../ui/textareas/Textarea.vue'
import BaseButton from '../ui/buttons/BaseButton.vue'
import { useTransaction } from '@/composables/useTransaction'

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleReceiveModal: { type: Function, required: true },
    trxNo: { type: String, required: true },
})

const emit = defineEmits<{
    (e: 'received'): void
}>()

// ── State ─────────────────────────────────────────────────────────────────────
const remarks = ref<string>('')
const loading = ref(false)
const errorMsg = ref<string | null>(null)

// Use the shared composable — receiveDocument updates transaction.value directly
const { receiveDocument } = useTransaction()

// ── Handlers ──────────────────────────────────────────────────────────────────
async function handleReceive() {
    errorMsg.value = null
    loading.value = true

    try {
        await receiveDocument(props.trxNo, { remarks: remarks.value || null })
        // transaction.value.logs is now updated inside receiveDocument()
        // useActionVisibility recomputes immediately — no extra fetch needed
        emit('received')
        handleClose()
    } catch (e: any) {
        errorMsg.value = e.message || 'Failed to receive document.'
    } finally {
        loading.value = false
    }
}

function handleClose() {
    remarks.value = ''
    errorMsg.value = null
    props.toggleReceiveModal()
}
</script>

<template>
    <LargeModal title="Receive Document" :isOpen="props.isOpen" @close="handleClose">

        <div class="px-4 py-2 space-y-4">

            <!-- Info Banner -->
            <div class="flex items-start gap-3 p-3 text-sm text-blue-700 border border-blue-200 rounded-lg bg-blue-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <span>
                    Acknowledging receipt confirms your office has taken possession of this document
                    and will be logged in the transaction history.
                </span>
            </div>

            <!-- Error Banner -->
            <div v-if="errorMsg"
                class="flex items-start gap-3 p-3 text-sm text-red-700 border border-red-200 rounded-lg bg-red-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span>{{ errorMsg }}</span>
            </div>

            <!-- Remarks -->
            <div>
                <FrmLabel label="Remarks" class="mb-1.5" />
                <Textarea v-model="remarks" placeholder="Optional — add any notes about this receipt" class="w-full" />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 border-gray-200">
                <BaseButton @click="handleClose" backgroundClass="bg-gray-100 hover:bg-gray-200"
                    textColorClass="text-gray-700" :disabled="loading">
                    Cancel
                </BaseButton>
                <BaseButton @click="handleReceive"
                    textColorClass="text-white" :disabled="loading" class="min-w-[130px]">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="animate-spin size-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Receiving...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        Confirm Receipt
                    </span>
                </BaseButton>
            </div>
        </template>

    </LargeModal>
</template>