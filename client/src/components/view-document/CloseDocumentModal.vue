<script setup lang="ts">
import { ref } from 'vue'
import LargeModal from '../ui/modals/LargeModal.vue'
import Textarea from '../ui/textareas/Textarea.vue'
import BaseButton from '../ui/buttons/BaseButton.vue'
import FrmLabel from '../ui/labels/FrmLabel.vue'
import { useTransaction } from '@/composables/useTransaction'

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleModal: { type: Function, required: true },
    docNo: { type: String, required: true },
})

const emit = defineEmits<{
    (e: 'closed'): void
}>()

const remarks = ref('')
const loading = ref(false)
const errorMsg = ref<string | null>(null)

const { closeDocument } = useTransaction()

async function handleClose_() {
    if (!remarks.value.trim()) {
        errorMsg.value = 'Remarks are required when closing a document.'
        return
    }

    errorMsg.value = null
    loading.value = true
    try {
        await closeDocument(props.docNo, { remarks: remarks.value.trim() })
        emit('closed')
        handleClose()
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message || e.message || 'Failed to close document.'
    } finally {
        loading.value = false
    }
}

function handleClose() {
    remarks.value = ''
    errorMsg.value = null
    props.toggleModal()
}
</script>

<template>
    <LargeModal title="Close Document" :isOpen="props.isOpen" @close="handleClose">

        <div class="px-4 py-2 space-y-4">

            <!-- Warning -->
            <div class="flex items-start gap-3 p-3 text-sm text-amber-700 border border-amber-200 rounded-lg bg-amber-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" />
                </svg>
                <span>
                    Closing a document will archive it and halt any pending routing.
                    Only the originating office can close a document.
                </span>
            </div>

            <!-- Error -->
            <div v-if="errorMsg"
                class="flex items-start gap-3 p-3 text-sm text-red-700 border border-red-200 rounded-lg bg-red-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span>{{ errorMsg }}</span>
            </div>

            <!-- Remarks (required) -->
            <div>
                <FrmLabel label="Remarks" :isRequired="true" class="mb-1.5" />
                <Textarea v-model="remarks" placeholder="State the reason for closing this document..."
                    class="w-full" />
            </div>

        </div>

        <template #footer>
            <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200">
                <BaseButton @click="handleClose" backgroundClass="bg-gray-100 hover:bg-gray-200"
                    textColorClass="text-gray-700" :disabled="loading">
                    Cancel
                </BaseButton>
                <BaseButton @click="handleClose_" backgroundClass="bg-gray-700 hover:bg-gray-800"
                    textColorClass="text-white" :disabled="loading || !remarks.trim()" class="min-w-[140px]">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="animate-spin size-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Closing...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                        Close Document
                    </span>
                </BaseButton>
            </div>
        </template>

    </LargeModal>
</template>
