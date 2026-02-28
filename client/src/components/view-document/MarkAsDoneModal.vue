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
    trxNo: { type: String, required: true },
})

const emit = defineEmits<{
    (e: 'done'): void
}>()

const remarks = ref('')
const loading = ref(false)
const errorMsg = ref<string | null>(null)

const { markAsDone } = useTransaction()

async function handleDone() {
    errorMsg.value = null
    loading.value = true
    try {
        await markAsDone(props.trxNo, { remarks: remarks.value || null })
        emit('done')
        handleClose()
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message || e.message || 'Failed to mark as done.'
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
    <LargeModal title="Mark as Done" :isOpen="props.isOpen" @close="handleClose">

        <div class="px-4 py-2 space-y-4">

            <!-- Info banner -->
            <div class="flex items-start gap-3 p-3 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>
                    Marking as done confirms that your office has completed the required action.
                    This will be logged in the transaction history.
                </span>
            </div>

            <!-- Error banner -->
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
                <div class="flex items-center gap-1 mb-1.5">
                    <FrmLabel label="Remarks" />
                    <span class="text-[10px] italic text-gray-400">(Optional)</span>
                </div>
                <Textarea v-model="remarks" placeholder="Describe what was accomplished..." class="w-full" />
            </div>

        </div>

        <template #footer>
            <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200">
                <BaseButton @click="handleClose" backgroundClass="bg-gray-100 hover:bg-gray-200"
                    textColorClass="text-gray-700" :disabled="loading">
                    Cancel
                </BaseButton>
                <BaseButton @click="handleDone" backgroundClass="bg-green-600 hover:bg-green-700"
                    textColorClass="text-white" :disabled="loading" class="min-w-[140px]">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="animate-spin size-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Saving...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Mark as Done
                    </span>
                </BaseButton>
            </div>
        </template>

    </LargeModal>
</template>
