<template>
  <div class="flex-1 w-full h-full">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Office Defaults</h1>
        <p class="mt-1 text-xs text-gray-500">Set default values for new documents created by this office.</p>
      </div>
      <button
        @click="handleSubmit"
        :disabled="saving || loading"
        class="inline-flex items-center px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Save Defaults
      </button>
    </div>
    <hr class="mb-6 text-gray-200" />

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="max-w-2xl p-4 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="default_document_type" class="block mb-1.5 text-xs font-medium text-gray-600">
            Default Document Type
          </label>
          <select
            id="default_document_type"
            v-model="form.default_document_type"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          >
            <option :value="null">None (let user choose)</option>
            <option v-for="dt in documentTypes" :key="dt" :value="dt">{{ dt }}</option>
          </select>
        </div>

        <div>
          <label for="default_action_type" class="block mb-1.5 text-xs font-medium text-gray-600">
            Default Action Type
          </label>
          <select
            id="default_action_type"
            v-model="form.default_action_type"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          >
            <option :value="null">None (let user choose)</option>
            <option v-for="at in actionTypes" :key="at" :value="at">{{ at }}</option>
          </select>
        </div>

        <div>
          <label for="default_routing_type" class="block mb-1.5 text-xs font-medium text-gray-600">
            Default Routing Type
          </label>
          <select
            id="default_routing_type"
            v-model="form.default_routing_type"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          >
            <option :value="null">None (let user choose)</option>
            <option value="Single">Single</option>
            <option value="Multiple">Multiple</option>
            <option value="Sequential">Sequential</option>
          </select>
        </div>

        <div>
          <label for="default_urgency_level" class="block mb-1.5 text-xs font-medium text-gray-600">
            Default Urgency Level
          </label>
          <select
            id="default_urgency_level"
            v-model="form.default_urgency_level"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          >
            <option :value="null">None (use system default)</option>
            <option value="Urgent">Urgent (1 day)</option>
            <option value="High">High (3 days)</option>
            <option value="Normal">Normal (5 days)</option>
            <option value="Routine">Routine (7 days)</option>
          </select>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useSettings } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'

const { loading, fetchOfficeDefaults, updateOfficeDefaults } = useSettings()
const toast = useToast()

const form = reactive({
  default_document_type: null as string | null,
  default_action_type: null as string | null,
  default_routing_type: null as string | null,
  default_urgency_level: null as string | null,
})

const saving = ref(false)

// These could be fetched from library endpoints, but for now we'll use static lists
const documentTypes = [
  'Letter',
  'Memorandum',
  'Report',
  'Resolution',
  'Ordinance',
  'Executive Order',
  'Administrative Order',
  'Circular',
  'Directive',
  'Notice',
  'Request',
  'Endorsement',
  'Certificate',
  'Contract',
  'Agreement',
]

const actionTypes = [
  'Appropriate Action',
  'Urgent Action',
  'Dissemination of Information',
  'Comment/Reaction/Response',
  'Compliance/Implementation',
  'Endorsement/Recommendation',
  'Coding/Deposit/Preparation',
  'Follow Up',
  'Investigation/Verification',
  'Your Information',
  'Draft of Reply',
  'Approval',
]

onMounted(async () => {
  try {
    const data = await fetchOfficeDefaults()
    form.default_document_type = data.default_document_type
    form.default_action_type = data.default_action_type
    form.default_routing_type = data.default_routing_type
    form.default_urgency_level = data.default_urgency_level
  } catch (e) {
    toast.error('Failed to load office defaults')
  }
})

async function handleSubmit() {
  saving.value = true
  try {
    await updateOfficeDefaults({
      default_document_type: form.default_document_type,
      default_action_type: form.default_action_type,
      default_routing_type: form.default_routing_type,
      default_urgency_level: form.default_urgency_level,
    })
    toast.success('Office defaults saved')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Failed to save defaults')
  } finally {
    saving.value = false
  }
}
</script>
