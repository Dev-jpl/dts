<template>
  <div class="flex-1 w-full h-full">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Notification Preferences</h1>
        <p class="mt-1 text-xs text-gray-500">Choose how you receive notifications for different events.</p>
      </div>
      <button
        @click="handleSave"
        :disabled="saving || !isDirty || loading"
        class="inline-flex items-center px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Save Preferences
      </button>
    </div>
    <hr class="mb-6 text-gray-200" />

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <div v-else class="max-w-2xl p-4 space-y-3">
      <div
        v-for="pref in localPreferences"
        :key="pref.event_type"
        class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg"
      >
        <div>
          <p class="text-xs font-medium text-gray-800">{{ formatEventType(pref.event_type) }}</p>
          <p class="text-xs text-gray-500">{{ getEventDescription(pref.event_type) }}</p>
        </div>
        <div class="flex items-center gap-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="pref.in_app"
              @change="markDirty"
              class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
            />
            <span class="text-xs text-gray-700">In-App</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="pref.email"
              @change="markDirty"
              class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
            />
            <span class="text-xs text-gray-700">Email</span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettings, type NotificationPreference } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'

const { loading, fetchNotificationPreferences, updateNotificationPreferences } = useSettings()
const toast = useToast()

const localPreferences = ref<NotificationPreference[]>([])
const isDirty = ref(false)
const saving = ref(false)

const eventTypes = [
  'document_released',
  'document_received',
  'document_returned',
  'document_done',
  'document_forwarded',
  'routing_halted',
  'overdue',
  'official_note_added',
]

const eventDescriptions: Record<string, string> = {
  document_released: 'When a document is released to your office',
  document_received: 'When your document is received by a recipient',
  document_returned: 'When a document is returned to sender',
  document_done: 'When a recipient marks a document as done',
  document_forwarded: 'When a document is forwarded to another office',
  routing_halted: 'When document routing is halted',
  overdue: 'When a document becomes overdue',
  official_note_added: 'When an official note is added to a document',
}

onMounted(async () => {
  try {
    const data = await fetchNotificationPreferences()
    // Initialize with all event types, filling in defaults if missing
    localPreferences.value = eventTypes.map(eventType => {
      const existing = data.find(p => p.event_type === eventType)
      return existing || { event_type: eventType, in_app: true, email: false }
    })
  } catch (e) {
    toast.error('Failed to load notification preferences')
  }
})

function formatEventType(eventType: string): string {
  return eventType
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

function getEventDescription(eventType: string): string {
  return eventDescriptions[eventType] || ''
}

function markDirty() {
  isDirty.value = true
}

async function handleSave() {
  saving.value = true
  try {
    await updateNotificationPreferences(localPreferences.value)
    isDirty.value = false
    toast.success('Notification preferences saved')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Failed to save preferences')
  } finally {
    saving.value = false
  }
}
</script>
