<template>
  <div class="flex-1 w-full h-full">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Office Profile</h1>
        <p class="mt-1 text-xs text-gray-500">View and edit your office information.</p>
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
        Save Changes
      </button>
    </div>
    <hr class="mb-6 text-gray-200" />

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="max-w-2xl p-4 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="code" class="block mb-1.5 text-xs font-medium text-gray-600">Office Code</label>
          <input
            type="text"
            id="code"
            :value="office?.code || office?.id"
            disabled
            class="w-full px-3 py-2 text-xs text-gray-500 bg-gray-100 border border-gray-200 rounded-lg"
          />
        </div>

        <div>
          <label for="name" class="block mb-1.5 text-xs font-medium text-gray-600">Office Name</label>
          <input
            type="text"
            id="name"
            v-model="form.name"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
            :class="{ 'border-red-400 ring-1 ring-red-400': errors.name }"
          />
          <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
        </div>

        <div class="md:col-span-2">
          <label for="description" class="block mb-1.5 text-xs font-medium text-gray-600">Description</label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          ></textarea>
        </div>

        <div>
          <label class="block mb-1.5 text-xs font-medium text-gray-600">Parent Office</label>
          <p class="text-xs text-gray-600">
            {{ office?.parent_office_name || 'None (Top-level office)' }}
          </p>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useSettings } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'

const { loading, office, fetchOffice, updateOffice } = useSettings()
const toast = useToast()

const form = reactive({
  name: '',
  description: '',
})

const errors = reactive<Record<string, string>>({})
const saving = ref(false)

onMounted(async () => {
  try {
    const data = await fetchOffice()
    form.name = data.name || ''
    form.description = data.description || ''
  } catch (e) {
    toast.error('Failed to load office profile')
  }
})

async function handleSubmit() {
  // Clear errors
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.name.trim()) {
    errors.name = 'Office name is required'
    return
  }

  saving.value = true
  try {
    await updateOffice({
      name: form.name,
      description: form.description,
    })
    toast.success('Office profile updated')
  } catch (e: any) {
    if (e.response?.data?.errors) {
      Object.assign(errors, e.response.data.errors)
    } else {
      toast.error(e.response?.data?.message || 'Failed to update office profile')
    }
  } finally {
    saving.value = false
  }
}
</script>
