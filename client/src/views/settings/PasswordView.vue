<template>
  <div class="flex-1 w-full h-full">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Update Password</h1>
        <p class="mt-1 text-xs text-gray-500">Ensure your account is using a long, random password to stay secure.</p>
      </div>
      <button
        @click="handleSubmit"
        :disabled="saving"
        class="inline-flex items-center px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Update Password
      </button>
    </div>
    <hr class="mb-6 text-gray-200" />

    <form @submit.prevent="handleSubmit" class="max-w-xl p-4 space-y-4">
      <div>
        <label for="current_password" class="block mb-1.5 text-xs font-medium text-gray-600">Current Password</label>
        <input
          type="password"
          id="current_password"
          v-model="form.current_password"
          class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          :class="{ 'border-red-400 ring-1 ring-red-400': errors.current_password }"
        />
        <p v-if="errors.current_password" class="mt-1 text-xs text-red-500">{{ errors.current_password }}</p>
      </div>

      <div>
        <label for="password" class="block mb-1.5 text-xs font-medium text-gray-600">New Password</label>
        <input
          type="password"
          id="password"
          v-model="form.password"
          class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          :class="{ 'border-red-400 ring-1 ring-red-400': errors.password }"
        />
        <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password }}</p>
      </div>

      <div>
        <label for="password_confirmation" class="block mb-1.5 text-xs font-medium text-gray-600">Confirm Password</label>
        <input
          type="password"
          id="password_confirmation"
          v-model="form.password_confirmation"
          class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          :class="{ 'border-red-400 ring-1 ring-red-400': errors.password_confirmation }"
        />
        <p v-if="errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ errors.password_confirmation }}</p>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useSettings } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'

const { updatePassword } = useSettings()
const toast = useToast()

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const errors = reactive<Record<string, string>>({})
const saving = ref(false)

async function handleSubmit() {
  // Clear errors
  Object.keys(errors).forEach(key => delete errors[key])

  // Basic validation
  if (!form.current_password) {
    errors.current_password = 'Current password is required'
    return
  }
  if (!form.password) {
    errors.password = 'New password is required'
    return
  }
  if (form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters'
    return
  }
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwords do not match'
    return
  }

  saving.value = true
  try {
    await updatePassword({
      current_password: form.current_password,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    toast.success('Password updated successfully')
    // Clear form
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''
  } catch (e: any) {
    if (e.response?.data?.errors) {
      Object.assign(errors, e.response.data.errors)
    } else {
      toast.error(e.response?.data?.message || 'Failed to update password')
    }
  } finally {
    saving.value = false
  }
}
</script>
