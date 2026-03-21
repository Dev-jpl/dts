<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import LargeModal from '../ui/modals/LargeModal.vue'
import Textarea from '../ui/textareas/Textarea.vue'
import BaseButton from '../ui/buttons/BaseButton.vue'
import FrmLabel from '../ui/labels/FrmLabel.vue'
import FileTaskCard from '../uploader/FileTaskCard.vue'
import FileTaskHeader from '../uploader/FileTaskHeader.vue'
import { useTransaction } from '@/composables/useTransaction'
import { useUploader } from '@/composables/useUploader'
import { useLibraryStore } from '@/stores/libraries'
import { LuUpload } from 'vue-icons-plus/lu'

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleModal: { type: Function, required: true },
    trxNo: { type: String, required: true },
    actionType: { type: String, required: true },
})

const emit = defineEmits<{
    (e: 'done'): void
}>()

const libraryStore = useLibraryStore()
const proofUploader = useUploader()

const remarks = ref('')
const loading = ref(false)
const errorMsg = ref<string | null>(null)
const proofFileId = ref<string | null>(null)

const { markAsDone } = useTransaction()

// Find action info from library store
const actionInfo = computed(() => {
    const actions = libraryStore.actionLibrary.data
    const found = actions.find(a => a.item_title === props.actionType)
    return found?.return_value ?? null
})

const requiresProof = computed(() => actionInfo.value?.requires_proof ?? false)
const proofDescription = computed(() => actionInfo.value?.proof_description ?? 'Proof of completion')

const canSubmit = computed(() => {
    if (requiresProof.value && !proofFileId.value) return false
    return true
})

// Load action library if not already loaded
onMounted(async () => {
    if (libraryStore.actionLibrary.data.length === 0) {
        await libraryStore.loadActionLibrary()
    }
})

async function onProofFileChange(event: Event) {
    const responses = await proofUploader.handleFileInput(event)
    if (responses.length > 0) {
        proofFileId.value = responses[0].temp_path
    }
}

async function onProofFileDrop(event: DragEvent) {
    const responses = await proofUploader.handleDrop(event)
    if (responses.length > 0) {
        proofFileId.value = responses[0].temp_path
    }
}

function removeProofFile() {
    proofFileId.value = null
    proofUploader.tasks.value = []
}

async function handleDone() {
    errorMsg.value = null

    if (requiresProof.value && !proofFileId.value) {
        errorMsg.value = `This action type (${props.actionType}) requires a proof attachment.`
        return
    }

    loading.value = true
    try {
        await markAsDone(props.trxNo, {
            remarks: remarks.value || null,
            proof_file_id: proofFileId.value
        })
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
    proofFileId.value = null
    proofUploader.tasks.value = []
    props.toggleModal()
}
</script>

<template>
    <LargeModal title="Mark as Done" :isOpen="props.isOpen" @close="handleClose">

        <div class="px-4 py-2 space-y-4">

            <!-- Info banner -->
            <div
                class="flex items-start gap-3 p-3 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50">
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

            <!-- Proof required banner -->
            <div v-if="requiresProof"
                class="flex items-start gap-3 p-3 text-sm border rounded-lg text-amber-700 border-amber-200 bg-amber-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span>
                    <strong>{{ actionType }}</strong> requires proof of completion.
                    Please upload supporting documentation before marking as done.
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

            <!-- Proof Upload Section -->
            <div v-if="requiresProof" class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                <div class="flex items-center gap-1 mb-1.5">
                    <FrmLabel :label="proofDescription || 'Proof of Completion'" />
                    <span class="text-[10px] italic text-red-500">(Required)</span>
                </div>
                <div class="mb-3 text-xs text-gray-500">
                    Upload documentation that proves the required action has been completed.
                </div>

                <!-- Uploaded files list -->
                <div v-if="proofUploader.tasks.value.length > 0"
                    class="mt-2 bg-white border border-gray-200 rounded-lg overflow-clip">
                    <FileTaskHeader :completedCount="proofUploader.completedCount"
                        :totalCount="proofUploader.totalCount" />
                    <div class="max-h-[150px] divide-y divide-gray-200 overflow-y-auto">
                        <div v-for="(task, i) in proofUploader.orderedTasks.value" :key="i" class="relative">
                            <FileTaskCard :task="task" />
                            <button v-if="task.finished" @click="removeProofFile"
                                class="absolute p-1 text-gray-400 rounded top-2 right-2 hover:text-red-500 hover:bg-red-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Drop zone -->
                <input type="file" class="hidden" id="dropzone-proof-file" @change="onProofFileChange"
                    accept=".png,.jpg,.jpeg,.pdf,.doc,.docx" />
                <label v-if="!proofFileId" for="dropzone-proof-file" @dragover.prevent @dragenter.prevent
                    @drop.prevent="onProofFileDrop"
                    class="flex flex-col items-center justify-center w-full h-24 mt-2 bg-white border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:bg-gray-100">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-500">
                        <LuUpload class="mb-2 size-6" />
                        <p class="text-sm">
                            <span class="font-semibold">Click to upload</span> or drag and drop
                        </p>
                        <p class="text-xs text-gray-400">PDF, DOC, PNG, JPG</p>
                    </div>
                </label>
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
            <div class="flex justify-end gap-2 border-gray-200">
                <BaseButton @click="handleClose" backgroundClass="bg-gray-100 hover:bg-gray-200"
                    textColorClass="text-gray-700" :disabled="loading">
                    Cancel
                </BaseButton>
                <BaseButton @click="handleDone"
                    :backgroundClass="canSubmit ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed'"
                    textColorClass="text-white" :disabled="loading || !canSubmit" class="min-w-[140px]">
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
