<script setup lang="ts">
import type { Template } from '@/composables/useTemplates'

const props = defineProps<{
  template: Template
  canEdit: boolean
  canDelete: boolean
}>()

const emit = defineEmits<{
  use: [template: Template]
  edit: [template: Template]
  duplicate: [template: Template]
  delete: [template: Template]
}>()

const scopeColors: Record<string, string> = {
  personal: 'bg-blue-100 text-blue-700',
  office: 'bg-green-100 text-green-700',
  system: 'bg-purple-100 text-purple-700',
}

const urgencyColors: Record<string, string> = {
  Urgent: 'text-red-600',
  High: 'text-orange-500',
  Normal: 'text-yellow-600',
  Routine: 'text-gray-500',
}

function formatDate(date: string | null): string {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <div class="flex flex-col gap-3 p-4 transition-shadow bg-white border border-gray-200 rounded-lg hover:shadow-md">
    <!-- Header -->
    <div class="flex items-start justify-between gap-2">
      <div class="flex-1 min-w-0">
        <h3 class="text-sm font-semibold text-gray-800 truncate">{{ template.name }}</h3>
        <p v-if="template.description" class="text-xs text-gray-500 mt-0.5 line-clamp-2">
          {{ template.description }}
        </p>
      </div>
      <span
        class="shrink-0 text-[10px] text-gray-700 bg-gray-100 font-medium px-2 py-0.5 rounded-full capitalize"
        :class="scopeColors[template.scope]"
      >
        {{ template.scope }}
      </span>
    </div>

    <!-- Metadata grid -->
    <div class="grid grid-cols-2 text-xs text-gray-600 gap-x-4 gap-y-1">
      <div v-if="template.document_type">
        <span class="text-gray-400">Type:</span> {{ template.document_type }}
      </div>
      <div v-if="template.action_type">
        <span class="text-gray-400">Action:</span> {{ template.action_type }}
      </div>
      <div v-if="template.routing_type">
        <span class="text-gray-400">Routing:</span> {{ template.routing_type }}
      </div>
      <div>
        <span class="text-gray-400">Urgency:</span>
        <span :class="urgencyColors[template.urgency_level]" class="ml-1 font-medium">
          {{ template.urgency_level }}
        </span>
      </div>
      <div>
        <span class="text-gray-400">Recipients:</span> {{ template.recipients.length }}
      </div>
      <div>
        <span class="text-gray-400">Used:</span> {{ template.use_count }}×
      </div>
    </div>

    <div class="text-xs text-gray-400">
      Last used: {{ formatDate(template.last_used_at) }}
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-2 pt-1 border-gray-100">
      <button
        type="button"
        @click="emit('use', template)"
        class="flex-1 text-xs bg-teal-700 text-white px-3 py-1.5 rounded hover:bg-teal-700 transition-colors font-medium"
      >
        Use
      </button>
      <button
        v-if="canEdit"
        type="button"
        @click="emit('edit', template)"
        class="text-xs border border-gray-300 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50 transition-colors"
      >
        Edit
      </button>
      <button
        type="button"
        @click="emit('duplicate', template)"
        class="text-xs border border-gray-300 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-50 transition-colors"
        title="Duplicate as personal copy"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
          <path d="M7 3.5A1.5 1.5 0 0 1 8.5 2h3.879a1.5 1.5 0 0 1 1.06.44l3.122 3.12A1.5 1.5 0 0 1 17 6.622V12.5a1.5 1.5 0 0 1-1.5 1.5h-1v-3.379a3 3 0 0 0-.879-2.121L10.5 5.379A3 3 0 0 0 8.379 4.5H7v-1Z" />
          <path d="M4.5 6A1.5 1.5 0 0 0 3 7.5v9A1.5 1.5 0 0 0 4.5 18h7a1.5 1.5 0 0 0 1.5-1.5v-5.879a1.5 1.5 0 0 0-.44-1.06L9.44 6.439A1.5 1.5 0 0 0 8.378 6H4.5Z" />
        </svg>
      </button>
      <button
        v-if="canDelete"
        type="button"
        @click="emit('delete', template)"
        class="text-xs border border-red-200 text-red-500 px-3 py-1.5 rounded hover:bg-red-50 transition-colors"
        title="Delete template"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5">
          <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
  </div>
</template>
