<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import LargeModal from '../ui/modals/LargeModal.vue'
import Textarea from '../ui/textareas/Textarea.vue'
import BaseButton from '../ui/buttons/BaseButton.vue'
import FrmLabel from '../ui/labels/FrmLabel.vue'
import type { Transaction } from '@/types/transaction'
import { useTransaction } from '@/composables/useTransaction'

const props = defineProps<{
    isOpen: boolean
    toggleModal: () => void
    trxNo: string
    transaction: Transaction | null
}>()

const emit = defineEmits<{
    (e: 'released'): void
}>()

const selectedRecipientId = ref<string>('')
const remarks = ref('')
const loading = ref(false)
const errorMsg = ref<string | null>(null)

const { subsequentRelease } = useTransaction()

// Only show active default recipients who haven't been released to yet
const eligibleRecipients = computed(() => {
    if (!props.transaction) return []
    return props.transaction.recipients.filter(
        r => r.recipient_type === 'default' && r.isActive
    )
})

const selectedRecipient = computed(() =>
    eligibleRecipients.value.find(r => r.office_id === selectedRecipientId.value) ?? null
)

const isValid = computed(() => !!selectedRecipientId.value)

async function handleRelease() {
    if (!isValid.value || !selectedRecipient.value) return

    errorMsg.value = null
    loading.value = true
    try {
        await subsequentRelease(props.trxNo, {
            target_office_id: selectedRecipient.value.office_id,
            target_office_name: selectedRecipient.value.office_name,
            remarks: remarks.value || null,
        })
        emit('released')
        handleClose()
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message || e.message || 'Failed to re-release document.'
    } finally {
        loading.value = false
    }
}

function handleClose() {
    selectedRecipientId.value = ''
    remarks.value = ''
    errorMsg.value = null
    props.toggleModal()
}
</script>

<template>
    <LargeModal title="Subsequent Release" :isOpen="props.isOpen" @close="handleClose">

        <div class="px-4 py-2 space-y-4">

            <!-- Info -->
            <div class="flex items-start gap-3 p-3 text-sm text-blue-700 border border-blue-200 rounded-lg bg-blue-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 mt-0.5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <span>
                    Re-release this document to a registered recipient. Your office will step out of the routing
                    and the selected office will be reactivated.
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

            <!-- Target recipient -->
            <div>
                <FrmLabel label="Release To" :isRequired="true" class="mb-1.5" />
                <div v-if="!eligibleRecipients.length" class="text-xs text-gray-400 italic">
                    No eligible recipients found.
                </div>
                <div v-else class="grid gap-1.5">
                    <label v-for="r in eligibleRecipients" :key="r.office_id"
                        class="flex items-center gap-2.5 px-3 py-2.5 text-xs border rounded-lg cursor-pointer transition"
                        :class="selectedRecipientId === r.office_id
                            ? 'border-teal-500 bg-teal-50 text-teal-800 font-medium'
                            : 'border-gray-200 hover:border-gray-300 text-gray-600'">
                        <input type="radio" v-model="selectedRecipientId" :value="r.office_id"
                            class="accent-teal-600" />
                        {{ r.office_name }}
                    </label>
                </div>
            </div>

            <!-- Remarks -->
            <div>
                <div class="flex items-center gap-1 mb-1.5">
                    <FrmLabel label="Remarks" />
                    <span class="text-[10px] italic text-gray-400">(Optional)</span>
                </div>
                <Textarea v-model="remarks" placeholder="Add any notes for the recipient..." class="w-full" />
            </div>

        </div>

        <template #footer>
            <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-200">
                <BaseButton @click="handleClose" backgroundClass="bg-gray-100 hover:bg-gray-200"
                    textColorClass="text-gray-700" :disabled="loading">
                    Cancel
                </BaseButton>
                <BaseButton @click="handleRelease" backgroundClass="bg-teal-700 hover:bg-teal-800"
                    textColorClass="text-white" :disabled="loading || !isValid" class="min-w-[150px]">
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="animate-spin size-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Releasing...
                    </span>
                    <span v-else>Subsequent Release</span>
                </BaseButton>
            </div>
        </template>

    </LargeModal>
</template>
