<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import LargeModal from '../ui/modals/LargeModal.vue'
import Textarea from '../ui/textareas/Textarea.vue'
import BaseButton from '../ui/buttons/BaseButton.vue'
import FrmLabel from '../ui/labels/FrmLabel.vue'
import SearchSelect from '../ui/select/SearchSelect.vue'
import { useLibraryStore } from '@/stores/libraries'
import { useTransaction } from '@/composables/useTransaction'

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleModal: { type: Function, required: true },
    trxNo: { type: String, required: true },
})

const emit = defineEmits<{
    (e: 'replied'): void
}>()

const subject = ref('')
const selectedAction = ref<any>(null)
const selectedDocType = ref<any>(null)
const remarks = ref('')
const loading = ref(false)
const errorMsg = ref<string | null>(null)

const { replyToDocument } = useTransaction()
const { actionLibrary, documentTypeLibrary, loadActionLibrary, loadDocumentTypeLibrary } = useLibraryStore()

onMounted(() => {
    loadActionLibrary()
    loadDocumentTypeLibrary()
})

const isValid = computed(() =>
    subject.value.trim() && selectedAction.value && selectedDocType.value
)

async function handleReply() {
    if (!isValid.value) {
        errorMsg.value = 'Please fill in all required fields.'
        return
    }

    errorMsg.value = null
    loading.value = true
    try {
        await replyToDocument(props.trxNo, {
            subject: subject.value.trim(),
            action_type: selectedAction.value?.action,
            document_type: selectedDocType.value?.type,
            remarks: remarks.value || null,
            recipients: [],  // reply goes back to origin — backend handles this
        })
        emit('replied')
        handleClose()
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message || e.message || 'Failed to send reply.'
    } finally {
        loading.value = false
    }
}

function handleClose() {
    subject.value = ''
    selectedAction.value = null
    selectedDocType.value = null
    remarks.value = ''
    errorMsg.value = null
    props.toggleModal()
}
</script>

<template>
    <LargeModal title="Reply to Document" :isOpen="props.isOpen" @close="handleClose">

        <div class="px-4 py-2 space-y-4 max-h-[calc(100svh-15rem)] overflow-auto">

            <!-- Info -->
            <div class="flex items-start gap-3 p-3 text-sm text-blue-700 border border-blue-200 rounded-lg bg-blue-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7.49 12 3.74 8.248m0 0 3.75-3.75m-3.75 3.75h16.5V19.5" />
                </svg>
                <span>
                    A reply creates a new document addressed to the originating office.
                    Fill in the details of your response below.
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

            <!-- Subject -->
            <div>
                <FrmLabel label="Subject" :isRequired="true" class="mb-1.5" />
                <input v-model="subject" type="text" placeholder="Subject of your reply..."
                    class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500" />
            </div>

            <!-- Document Type -->
            <div>
                <FrmLabel label="Document Type" :isRequired="true" class="mb-1.5" />
                <SearchSelect v-model="selectedDocType" :items="documentTypeLibrary?.data ?? []"
                    :isLoading="documentTypeLibrary?.isLoading" placeholder="Select document type" />
            </div>

            <!-- Action Type -->
            <div>
                <FrmLabel label="Action" :isRequired="true" class="mb-1.5" />
                <SearchSelect v-model="selectedAction" :items="actionLibrary.data ?? []"
                    :isLoading="actionLibrary.isLoading" placeholder="What should the origin office do?" />
            </div>

            <!-- Remarks -->
            <div>
                <div class="flex items-center gap-1 mb-1.5">
                    <FrmLabel label="Remarks" />
                    <span class="text-[10px] italic text-gray-400">(Optional)</span>
                </div>
                <Textarea v-model="remarks" placeholder="Add context or notes..." class="w-full" />
            </div>

        </div>

        <template #footer>
            <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200">
                <BaseButton @click="handleClose" backgroundClass="bg-gray-100 hover:bg-gray-200"
                    textColorClass="text-gray-700" :disabled="loading">
                    Cancel
                </BaseButton>
                <BaseButton @click="handleReply" backgroundClass="bg-indigo-600 hover:bg-indigo-700"
                    textColorClass="text-white" :disabled="loading || !isValid" class="min-w-[130px]">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="animate-spin size-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Sending...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.49 12 3.74 8.248m0 0 3.75-3.75m-3.75 3.75h16.5V19.5" />
                        </svg>
                        Send Reply
                    </span>
                </BaseButton>
            </div>
        </template>

    </LargeModal>
</template>
