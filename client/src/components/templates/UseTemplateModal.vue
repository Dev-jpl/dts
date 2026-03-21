<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import type { Template } from '@/composables/useTemplates'
import { useTemplates } from '@/composables/useTemplates'
import BaseButton from '../ui/buttons/BaseButton.vue';

const props = defineProps<{
  template: Template
}>()

const emit = defineEmits<{
  close: []
}>()

const router = useRouter()
const { useTemplate } = useTemplates()
const isLoading = ref(false)
const error = ref('')

const recipientTypeLabels: Record<string, string> = {
  default: 'Default',
  cc: 'CC',
  bcc: 'BCC',
}

const urgencyColors: Record<string, string> = {
  Urgent: 'text-red-600 font-semibold',
  High: 'text-orange-500 font-semibold',
  Normal: 'text-yellow-600',
  Routine: 'text-gray-500',
}

async function confirmUse() {
  isLoading.value = true
  error.value = ''
  try {
    const tpl = await useTemplate(props.template.id)
    // Store template data in session storage for profile-document form to pick up
    sessionStorage.setItem('template_prefill', JSON.stringify(tpl))
    emit('close')
    router.push({ name: 'profile-document' })
  } catch (e: any) {
    error.value = e.response?.data?.message ?? e.message ?? 'Failed to use template.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <h2 class="text-base font-semibold text-gray-800">Use Template</h2>
        <button type="button" @click="emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="flex-1 px-5 py-4 space-y-4 overflow-y-auto">
        <div>
          <h3 class="text-sm font-semibold text-gray-800">{{ template.name }}</h3>
          <p v-if="template.description" class="mt-1 text-xs text-gray-500">{{ template.description }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3 text-sm">
          <div v-if="template.document_type">
            <span class="block text-xs text-gray-400">Document Type</span>
            <span class="text-gray-700">{{ template.document_type }}</span>
          </div>
          <div v-if="template.action_type">
            <span class="block text-xs text-gray-400">Action Type</span>
            <span class="text-gray-700">{{ template.action_type }}</span>
          </div>
          <div v-if="template.routing_type">
            <span class="block text-xs text-gray-400">Routing</span>
            <span class="text-gray-700">{{ template.routing_type }}</span>
          </div>
          <div>
            <span class="block text-xs text-gray-400">Urgency</span>
            <span :class="urgencyColors[template.urgency_level]">{{ template.urgency_level }}</span>
          </div>
        </div>

        <div v-if="template.remarks_template">
          <span class="block mb-1 text-xs text-gray-400">Remarks Template</span>
          <p class="p-2 text-xs text-sm text-gray-700 rounded bg-gray-50">{{ template.remarks_template }}</p>
        </div>

        <div v-if="template.recipients.length">
          <span class="block mb-2 text-xs text-gray-400">Recipients ({{ template.recipients.length }})</span>
          <div class="space-y-1.5">
            <div
              v-for="r in template.recipients"
              :key="r.sequence"
              class="flex items-center gap-2 text-xs"
            >
              <span class="flex items-center justify-center w-5 h-5 text-xs font-medium text-gray-600 bg-gray-200 rounded-full shrink-0">
                {{ r.sequence }}
              </span>
              <span class="flex-1 text-gray-700">{{ r.office_name }}</span>
              <span
                class="text-xs px-1.5 py-0.5 rounded"
                :class="{
                  'bg-teal-100 text-teal-700': r.recipient_type === 'default',
                  'bg-blue-100 text-blue-700': r.recipient_type === 'cc',
                  'bg-gray-100 text-gray-500': r.recipient_type === 'bcc',
                }"
              >
                {{ recipientTypeLabels[r.recipient_type] }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-xs italic text-gray-400">No recipients defined — you can add them when creating the document.</div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- Footer -->
      <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200">
        <button
          type="button"
          @click="emit('close')"
          class="px-4 py-2 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          Cancel
        </button>
        <BaseButton
          type="button"
          :disabled="isLoading"
          @click="confirmUse"
          class="px-4 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 disabled:opacity-50"
        >
          {{ isLoading ? 'Loading…' : 'Use Template' }}
        </BaseButton>
      </div>
    </div>
  </div>
</template>
