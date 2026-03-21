<template>
  <div class="flex-1 w-full h-full">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Display Preferences</h1>
        <p class="mt-1 text-xs text-gray-500">Customize how data is displayed across the application.</p>
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
        Save Preferences
      </button>
    </div>
    <hr class="mb-6 text-gray-200" />

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="max-w-2xl p-4 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="date_format" class="block mb-1.5 text-xs font-medium text-gray-600">Date Format</label>
          <select
            id="date_format"
            v-model="form.date_format"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          >
            <option value="Y-m-d">2024-03-15 (ISO)</option>
            <option value="d/m/Y">15/03/2024 (EU)</option>
            <option value="m/d/Y">03/15/2024 (US)</option>
            <option value="d-m-Y">15-03-2024</option>
            <option value="F j, Y">March 15, 2024</option>
          </select>
        </div>

        <div>
          <label for="timezone" class="block mb-1.5 text-xs font-medium text-gray-600">Timezone</label>
          <select
            id="timezone"
            v-model="form.timezone"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          >
            <option value="Asia/Manila">Asia/Manila (PHT)</option>
            <option value="UTC">UTC</option>
            <option value="America/New_York">America/New York (EST)</option>
            <option value="America/Los_Angeles">America/Los Angeles (PST)</option>
            <option value="Europe/London">Europe/London (GMT)</option>
            <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
            <option value="Asia/Singapore">Asia/Singapore (SGT)</option>
          </select>
        </div>

        <div>
          <label for="default_period" class="block mb-1.5 text-xs font-medium text-gray-600">Default Report Period</label>
          <select
            id="default_period"
            v-model="form.default_period"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          >
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
          </select>
        </div>

        <div class="flex items-center pt-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              id="dashboard_realtime"
              type="checkbox"
              v-model="form.dashboard_realtime"
              class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
            />
            <span class="text-xs text-gray-700">Enable real-time dashboard updates</span>
          </label>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useSettings } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'

const { loading, fetchPreferences, updatePreferences } = useSettings()
const toast = useToast()

const form = reactive({
  date_format: 'Y-m-d',
  timezone: 'Asia/Manila',
  default_period: 'month' as 'week' | 'month' | 'quarter' | 'year',
  dashboard_realtime: true,
})

const saving = ref(false)

onMounted(async () => {
  try {
    const data = await fetchPreferences()
    form.date_format = data.date_format
    form.timezone = data.timezone
    form.default_period = data.default_period
    form.dashboard_realtime = data.dashboard_realtime
  } catch (e) {
    toast.error('Failed to load preferences')
  }
})

async function handleSubmit() {
  saving.value = true
  try {
    await updatePreferences({
      date_format: form.date_format,
      timezone: form.timezone,
      default_period: form.default_period,
      dashboard_realtime: form.dashboard_realtime,
    })
    toast.success('Preferences saved successfully')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Failed to save preferences')
  } finally {
    saving.value = false
  }
}
</script>
