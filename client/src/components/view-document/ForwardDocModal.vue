<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import LargeModal from '../ui/modals/LargeModal.vue'
import FrmLabel from '../ui/labels/FrmLabel.vue'
import Textarea from '../ui/textareas/Textarea.vue'
import BaseButton from '../ui/buttons/BaseButton.vue'
import SearchSelect from '../ui/select/SearchSelect.vue'
import SearchMultiSelectOffice from '../ui/select/SearchMultiSelectOffice.vue'
import { useLibraryStore } from '@/stores/libraries'
import { useTransaction } from '@/composables/useTransaction'

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleForwardModal: { type: Function, required: true },
    trxNo: { type: String, required: true },
    // Pass the current transaction so we can show context
    transaction: { type: Object, default: null },
})

const emit = defineEmits<{
    (e: 'forwarded'): void
}>()

// ── Libraries ─────────────────────────────────────────────────────────────────
const { actionLibrary, officeLibrary, loadActionLibrary, loadOfficeLibrary } = useLibraryStore()

onMounted(() => {
    loadActionLibrary()
    loadOfficeLibrary()
})

// ── Form state ────────────────────────────────────────────────────────────────
const selectedOffice = ref<any[]>([])   // single office (array with 1 item from SearchMultiSelectOffice)
const selectedAction = ref<any>(null)   // action instruction for the next office
const remarks = ref<string>('')
const loading = ref(false)
const errorMsg = ref<string | null>(null)

// ── Composable ────────────────────────────────────────────────────────────────
const { forwardDocument } = useTransaction()

// ── Computed helpers ──────────────────────────────────────────────────────────
const resolvedOffice = computed(() => selectedOffice.value?.[0] ?? null)

const isValid = computed(() =>
    resolvedOffice.value !== null && selectedAction.value !== null
)

// ── Handlers ──────────────────────────────────────────────────────────────────
async function handleForward() {
    if (!isValid.value) {
        errorMsg.value = 'Please select an office and an action before forwarding.'
        return
    }

    errorMsg.value = null
    loading.value = true

    try {
        await forwardDocument(props.trxNo, {
            routed_office: {
                id: resolvedOffice.value.office_code,
                office_name: resolvedOffice.value.office,
            },
            action: selectedAction.value,
            remarks: remarks.value || null,
        })

        emit('forwarded')
        handleClose()
    } catch (e: any) {
        errorMsg.value = e.message || 'Failed to forward document.'
    } finally {
        loading.value = false
    }
}

function handleClose() {
    selectedOffice.value = []
    selectedAction.value = null
    remarks.value = ''
    errorMsg.value = null
    props.toggleForwardModal()
}
</script>

<template>
    <LargeModal title="Forward Document" :isOpen="props.isOpen" @close="handleClose">

        <div class="px-4 py-2 space-y-4 max-h-[calc(100svh-15rem)] overflow-auto">

            <!-- Context: what this document is currently for -->
            <div v-if="transaction"
                class="flex items-start gap-3 p-3 text-xs border rounded-lg bg-amber-50 border-amber-200 text-amber-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-4 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <div>
                    <span class="font-medium">Original instruction: </span>
                    <span>{{ transaction.action_type }}</span>
                    <p class="mt-0.5 text-amber-600">
                        You are handing this off completely. Set a new instruction for the next office below.
                    </p>
                </div>
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

            <!-- Office to forward to -->
            <div>
                <div class="flex items-center gap-1 mb-1.5">
                    <FrmLabel label="Forward To" :isRequired="true" />
                </div>
                <SearchMultiSelectOffice v-model="selectedOffice" :items="officeLibrary.data ?? []"
                    :isLoading="officeLibrary.isLoading" :hasDesc="true" :isMultiple="false"
                    placeholder="Select office to forward to" />
                <!-- Selected office preview -->
                <div v-if="resolvedOffice"
                    class="mt-2 flex items-center justify-between p-2.5 bg-gray-50 border border-gray-200 rounded-md text-xs">
                    <div>
                        <p class="font-medium text-gray-800">{{ resolvedOffice.office }}</p>
                        <p class="text-gray-400 text-[10px]">{{ resolvedOffice.region }} — {{ resolvedOffice.service }}
                        </p>
                    </div>
                    <button @click="selectedOffice = []"
                        class="p-1 text-gray-400 transition-colors rounded-full hover:text-red-500 hover:bg-red-50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Action / instruction for the next office -->
            <div>
                <div class="flex items-center gap-1 mb-1.5">
                    <FrmLabel label="Action for Next Office" :isRequired="true" />
                </div>
                <SearchSelect v-model="selectedAction" :items="actionLibrary.data ?? []"
                    :isLoading="actionLibrary.isLoading" placeholder="What should this office do with the document?" />
                <p class="mt-1 text-[10px] text-gray-400">
                    This replaces the original action instruction for the next office.
                </p>
            </div>

            <!-- Remarks -->
            <div>
                <div class="flex items-center gap-1 mb-1.5">
                    <FrmLabel label="Remarks" />
                    <span class="text-[10px] italic text-gray-400">(Optional)</span>
                </div>
                <Textarea v-model="remarks" placeholder="Add context or notes for the next office..." class="w-full" />
            </div>

            <!-- Handoff notice -->
            <div class="flex items-start gap-2 p-3 text-xs text-gray-500 border border-gray-200 rounded-lg bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-4 mt-0.5 shrink-0 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <span>
                    Forwarding is a <strong>complete handoff</strong>. Your office will no longer be responsible
                    for this document after forwarding.
                </span>
            </div>

        </div>

        <!-- Footer -->
        <template #footer>
            <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200">
                <BaseButton @click="handleClose" backgroundClass="bg-gray-100 hover:bg-gray-200"
                    textColorClass="text-gray-700" :disabled="loading">
                    Cancel
                </BaseButton>
                <BaseButton @click="handleForward" backgroundClass="bg-indigo-600 hover:bg-indigo-700"
                    textColorClass="text-white" :disabled="loading || !isValid" class="min-w-[130px]">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="animate-spin size-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Forwarding...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
                        </svg>
                        Forward Document
                    </span>
                </BaseButton>
            </div>
        </template>

    </LargeModal>
</template>