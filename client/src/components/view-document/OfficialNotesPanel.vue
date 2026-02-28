<script setup lang="ts">
import { ref } from 'vue'
import type { DocumentNote } from '@/types/transaction'
import BaseButton from '../ui/buttons/BaseButton.vue'

const props = defineProps<{
    notes: DocumentNote[]
    loading: boolean
    docNo: string
    trxNo: string
    onPost: (docNo: string, payload: { transaction_no: string; note: string }) => Promise<any>
}>()

const newNote = ref('')
const posting = ref(false)
const errorMsg = ref<string | null>(null)

function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleDateString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

async function handlePost() {
    if (!newNote.value.trim()) return
    errorMsg.value = null
    posting.value = true
    try {
        await props.onPost(props.docNo, {
            transaction_no: props.trxNo,
            note: newNote.value.trim(),
        })
        newNote.value = ''
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message || e.message || 'Failed to post note.'
    } finally {
        posting.value = false
    }
}
</script>

<template>
    <div class="flex flex-col h-full">

        <!-- Notes list -->
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3">

            <div v-if="loading" class="text-xs text-center text-gray-400 py-8">
                Loading notes...
            </div>

            <div v-else-if="!notes.length" class="text-xs text-center text-gray-400 py-8">
                No official notes yet.
            </div>

            <div v-else v-for="note in notes" :key="note.id"
                class="p-3 border border-gray-200 rounded-lg bg-gray-50 space-y-1.5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-xs font-semibold text-gray-700">{{ note.office_name }}</span>
                        <span class="text-[10px] text-gray-400 ml-1.5">by {{ note.created_by_name }}</span>
                    </div>
                    <span class="text-[10px] text-gray-400 shrink-0">{{ formatDate(note.created_at) }}</span>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed whitespace-pre-wrap">{{ note.note }}</p>
            </div>

        </div>

        <!-- Compose area -->
        <div class="border-t border-gray-200 bg-white px-4 py-3 space-y-2">

            <div v-if="errorMsg" class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded">
                {{ errorMsg }}
            </div>

            <textarea v-model="newNote" rows="3" placeholder="Add an official note visible to all participants..."
                class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg resize-none focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500" />

            <div class="flex justify-end">
                <BaseButton @click="handlePost" backgroundClass="bg-teal-700 hover:bg-teal-800"
                    textColorClass="text-white" :disabled="posting || !newNote.trim()" class="min-w-[100px]">
                    <span v-if="posting" class="flex items-center gap-1.5">
                        <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Posting...
                    </span>
                    <span v-else>Post Note</span>
                </BaseButton>
            </div>

        </div>
    </div>
</template>
