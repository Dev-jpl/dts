<template>
  <div class="flex-1 w-full h-full bg-white">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">System Settings</h1>
        <p class="mt-1 text-xs text-gray-500">Configure global system settings and defaults.</p>
      </div>
      <button 
        @click="handleSave"
        :disabled="saving"
        class="inline-flex items-center px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 disabled:opacity-50"
      >
        <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Save Settings
      </button>
    </div>
    <hr class="text-gray-200" />

    <!-- Settings Sections -->
    <div class="max-w-3xl p-4 space-y-6">
      <!-- Document Settings -->
      <div class="p-4 border border-gray-200 rounded-lg">
        <h2 class="mb-4 text-sm font-semibold text-gray-700">Document Settings</h2>
        <div class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-600">Default Urgency Level</label>
              <select
                v-model="settings.default_urgency_level"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
              >
                <option value="Urgent">Urgent (1 day)</option>
                <option value="High">High (3 days)</option>
                <option value="Normal">Normal (5 days)</option>
                <option value="Routine">Routine (7 days)</option>
              </select>
            </div>
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-600">Default Routing Type</label>
              <select
                v-model="settings.default_routing_type"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
              >
                <option value="Single">Single</option>
                <option value="Multiple">Multiple</option>
                <option value="Sequential">Sequential</option>
              </select>
            </div>
          </div>
          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="settings.allow_document_copy"
                class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
              />
              <span class="text-xs text-gray-700">Allow document copying by default</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Notification Settings -->
      <div class="p-4 border border-gray-200 rounded-lg">
        <h2 class="mb-4 text-sm font-semibold text-gray-700">Notification Settings</h2>
        <div class="space-y-3">
          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="settings.enable_email_notifications"
                class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
              />
              <span class="text-xs text-gray-700">Enable email notifications system-wide</span>
            </label>
          </div>
          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="settings.enable_overdue_reminders"
                class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
              />
              <span class="text-xs text-gray-700">Send automatic overdue reminders</span>
            </label>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-600">Reminder Frequency</label>
              <select
                v-model="settings.reminder_frequency"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
              >
                <option value="daily">Daily</option>
                <option value="twice_daily">Twice Daily</option>
                <option value="weekly">Weekly</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Security Settings -->
      <div class="p-4 border border-gray-200 rounded-lg">
        <h2 class="mb-4 text-sm font-semibold text-gray-700">Security Settings</h2>
        <div class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-600">Session Timeout (minutes)</label>
              <input
                type="number"
                v-model="settings.session_timeout"
                min="5"
                max="480"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
              />
            </div>
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-600">Max Login Attempts</label>
              <input
                type="number"
                v-model="settings.max_login_attempts"
                min="3"
                max="10"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
              />
            </div>
          </div>
          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="settings.require_strong_password"
                class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
              />
              <span class="text-xs text-gray-700">Require strong passwords (8+ chars, mixed case, numbers, symbols)</span>
            </label>
          </div>
        </div>
      </div>

      <!-- System Maintenance -->
      <div class="p-4 border border-gray-200 rounded-lg">
        <h2 class="mb-4 text-sm font-semibold text-gray-700">System Maintenance</h2>
        <div class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-600">Auto-archive Documents After (days)</label>
              <input
                type="number"
                v-model="settings.auto_archive_days"
                min="30"
                max="365"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
              />
              <p class="mt-1 text-xs text-gray-400">Completed documents will be auto-archived after this period</p>
            </div>
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-600">Audit Log Retention (days)</label>
              <input
                type="number"
                v-model="settings.audit_log_retention"
                min="90"
                max="730"
                class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

const saving = ref(false)

const settings = reactive({
  // Document Settings
  default_urgency_level: 'High',
  default_routing_type: 'Single',
  allow_document_copy: false,
  
  // Notification Settings
  enable_email_notifications: true,
  enable_overdue_reminders: true,
  reminder_frequency: 'daily',
  
  // Security Settings
  session_timeout: 60,
  max_login_attempts: 5,
  require_strong_password: true,
  
  // Maintenance
  auto_archive_days: 90,
  audit_log_retention: 365,
})

async function handleSave() {
  saving.value = true
  try {
    // TODO: Call API to save settings
    await new Promise(resolve => setTimeout(resolve, 1000))
    // toast.success('Settings saved successfully')
  } catch (e) {
    // toast.error('Failed to save settings')
  } finally {
    saving.value = false
  }
}
</script>
