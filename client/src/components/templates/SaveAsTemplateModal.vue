<script setup lang="ts">
import { reactive, ref, computed } from 'vue'
import { useTemplates } from '@/composables/useTemplates'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  docNo: string
}>()

const emit = defineEmits<{
  saved: []
  close: []
}>()

const authStore = useAuthStore()
const { saveAsTemplate } = useTemplates()

const isLoading = ref(false)
const error = ref('')

const form = reactive({
  name: '',
  description: '',
  scope: 'personal' as 'personal' | 'office' | 'system',
})

const userRole = computed(() => authStore.user?.role ?? 'regular')

const scopeOptions = computed(() => {
  const opts: { value: string; label: string }[] = [
    { value: 'personal', label: 'Personal — only me' },
  ]
  if (['superior', 'admin'].includes(userRole.value)) {
    opts.push({ value: 'office', label: 'Office — all office members' })
  }
  if (userRole.value === 'admin') {
    opts.push({ value: 'system', label: 'System — all users' })
  }
  return opts
})

async function submit() {
  if (!form.name.trim()) {
    error.value = 'Template name is required.'
    return
  }
  isLoading.value = true
  error.value = ''
  try {
    await saveAsTemplate(props.docNo, {
      name: form.name.trim(),
      description: form.description.trim() || null,
      scope: form.scope,
    })
    emit('saved')
  } catch (e: any) {
    error.value = e.response?.data?.message ?? e.message ?? 'Failed to save template.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <h2 class="text-base font-semibold text-gray-800">Save as Template</h2>
        <button type="button" @click="emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <form @submit.prevent="submit" class="px-5 py-4 space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Template Name <span class="text-red-500">*</span></label>
          <input
            v-model="form.name"
            type="text"
            maxlength="150"
            placeholder="e.g. Standard Budget Request"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
          <textarea
            v-model="form.description"
            rows="2"
            placeholder="Optional — short note about when to use this template"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Scope</label>
          <select
            v-model="form.scope"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
          >
            <option v-for="opt in scopeOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <div class="flex justify-end gap-3 pt-1">
          <button
            type="button"
            @click="emit('close')"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isLoading"
            class="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-medium"
          >
            {{ isLoading ? 'Saving…' : 'Save Template' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
