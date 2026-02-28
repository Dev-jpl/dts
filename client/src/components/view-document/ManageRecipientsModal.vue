<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import LargeModal from '../ui/modals/LargeModal.vue'
import BaseButton from '../ui/buttons/BaseButton.vue'
import FrmLabel from '../ui/labels/FrmLabel.vue'
import SearchMultiSelectOffice from '../ui/select/SearchMultiSelectOffice.vue'
import type { Transaction } from '@/types/transaction'
import { useLibraryStore } from '@/stores/libraries'
import { useTransaction } from '@/composables/useTransaction'

const props = defineProps<{
    isOpen: boolean
    toggleModal: () => void
    trxNo: string
    transaction: Transaction | null
}>()

const emit = defineEmits<{
    (e: 'updated'): void
}>()

const selectedOffices = ref<any[]>([])
const loading = ref(false)
const errorMsg = ref<string | null>(null)

const { manageRecipients } = useTransaction()
const { officeLibrary, loadOfficeLibrary } = useLibraryStore()

onMounted(() => loadOfficeLibrary())

// Active default recipients (can potentially be removed)
const activeRecipients = computed(() =>
    (props.transaction?.recipients ?? []).filter(
        r => r.recipient_type === 'default' && r.isActive
    )
)

// Recipients who have already received (cannot be removed)
const receivedOfficeIds = computed(() => {
    const logs = props.transaction?.logs ?? []
    return new Set(logs.filter(l => l.status === 'Received').map(l => l.office_id))
})

const toRemove = ref<Set<number>>(new Set())

function toggleRemove(id: number) {
    if (toRemove.value.has(id)) {
        toRemove.value.delete(id)
    } else {
        toRemove.value.add(id)
    }
    toRemove.value = new Set(toRemove.value) // trigger reactivity
}

const hasChanges = computed(() =>
    selectedOffices.value.length > 0 || toRemove.value.size > 0
)

async function handleUpdate() {
    if (!hasChanges.value) {
        errorMsg.value = 'No changes to save.'
        return
    }

    errorMsg.value = null
    loading.value = true
    try {
        const payload: any = {}

        if (selectedOffices.value.length) {
            payload.add = selectedOffices.value.map(o => ({
                office_id: o.office_code,
                office_name: o.office,
                recipient_type: 'default',
            }))
        }

        if (toRemove.value.size) {
            payload.remove = Array.from(toRemove.value)
        }

        await manageRecipients(props.trxNo, payload)
        emit('updated')
        handleClose()
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message || e.message || 'Failed to update recipients.'
    } finally {
        loading.value = false
    }
}

function handleClose() {
    selectedOffices.value = []
    toRemove.value = new Set()
    errorMsg.value = null
    props.toggleModal()
}
</script>

<template>
    <LargeModal title="Manage Recipients" :isOpen="props.isOpen" @close="handleClose">

        <div class="px-4 py-2 space-y-5 max-h-[calc(100svh-15rem)] overflow-auto">

            <!-- Info -->
            <div class="flex items-start gap-3 p-3 text-sm text-blue-700 border border-blue-200 rounded-lg bg-blue-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <span>
                    Recipients who have already received the document cannot be removed.
                    New recipients will be added immediately.
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

            <!-- Current recipients -->
            <div>
                <FrmLabel label="Current Recipients" class="mb-2" />
                <div v-if="!activeRecipients.length" class="text-xs text-gray-400 italic">
                    No active recipients.
                </div>
                <div v-else class="space-y-1.5">
                    <div v-for="r in activeRecipients" :key="r.id"
                        class="flex items-center justify-between px-3 py-2 text-xs border rounded-lg"
                        :class="toRemove.has(r.id) ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white'">
                        <div>
                            <span class="font-medium text-gray-700">{{ r.office_name }}</span>
                            <span v-if="receivedOfficeIds.has(r.office_id)"
                                class="ml-2 px-1.5 py-0.5 text-[9px] bg-green-100 text-green-700 rounded">
                                Received
                            </span>
                        </div>
                        <button v-if="!receivedOfficeIds.has(r.office_id)" @click="toggleRemove(r.id)"
                            class="text-[10px] font-medium transition px-2 py-0.5 rounded"
                            :class="toRemove.has(r.id)
                                ? 'text-red-700 bg-red-100 hover:bg-red-200'
                                : 'text-gray-400 hover:text-red-500 hover:bg-red-50'">
                            {{ toRemove.has(r.id) ? 'Undo' : 'Remove' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Add new recipients -->
            <div>
                <FrmLabel label="Add Recipients" class="mb-1.5" />
                <SearchMultiSelectOffice v-model="selectedOffices" :items="officeLibrary.data ?? []"
                    :isLoading="officeLibrary.isLoading" :hasDesc="true" :isMultiple="true"
                    placeholder="Search and select offices to add..." />
            </div>

        </div>

        <template #footer>
            <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200">
                <BaseButton @click="handleClose" backgroundClass="bg-gray-100 hover:bg-gray-200"
                    textColorClass="text-gray-700" :disabled="loading">
                    Cancel
                </BaseButton>
                <BaseButton @click="handleUpdate" backgroundClass="bg-teal-700 hover:bg-teal-800"
                    textColorClass="text-white" :disabled="loading || !hasChanges" class="min-w-[140px]">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="animate-spin size-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Saving...
                    </span>
                    <span v-else>Save Changes</span>
                </BaseButton>
            </div>
        </template>

    </LargeModal>
</template>
