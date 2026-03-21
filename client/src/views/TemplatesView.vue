<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTemplates, type Template } from '@/composables/useTemplates'
import { useAuthStore } from '@/stores/auth'
import TemplateCard from '@/components/templates/TemplateCard.vue'
import UseTemplateModal from '@/components/templates/UseTemplateModal.vue'
import BaseButton from '@/components/ui/buttons/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const {
  templates,
  isLoading,
  activeScope,
  fetchTemplates,
  fetchByScope,
  deleteTemplate,
  duplicateTemplate,
} = useTemplates()

const userRole = computed(() => authStore.user?.role ?? 'regular')
const userId = computed(() => authStore.user?.id ?? '')

// Search state
const searchQuery = ref('')

// Modal state
const confirmDeleteTemplate = ref<Template | null>(null)
const confirmDuplicateTemplate = ref<Template | null>(null)
const useModalTemplate = ref<Template | null>(null)
const isDeleteLoading = ref(false)
const isDuplicateLoading = ref(false)
const actionError = ref('')

const scopeTabs = [
  { key: 'all', label: 'All' },
  { key: 'personal', label: 'Personal' },
  { key: 'office', label: 'Office' },
  { key: 'system', label: 'System' },
] as const

const hasFilters = computed(() => !!searchQuery.value)

// Filtered templates based on search
const filteredTemplates = computed(() => {
  let result = templates.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(t =>
      t.name.toLowerCase().includes(query) ||
      t.description?.toLowerCase().includes(query)
    )
  }

  return result
})

async function selectScope(scope: typeof activeScope.value) {
  activeScope.value = scope
  if (scope === 'all') {
    await fetchTemplates()
  } else {
    await fetchByScope(scope)
  }
}

function onSearch() {
  // Local filter - no API call needed
}

function canEdit(template: Template): boolean {
  if (template.scope === 'personal') return template.created_by_id === userId.value
  if (template.scope === 'office') return ['superior', 'admin'].includes(userRole.value)
  if (template.scope === 'system') return userRole.value === 'admin'
  return false
}

function canDelete(template: Template): boolean {
  return canEdit(template)
}

async function handleDelete() {
  if (!confirmDeleteTemplate.value) return
  isDeleteLoading.value = true
  actionError.value = ''
  try {
    await deleteTemplate(confirmDeleteTemplate.value.id)
    confirmDeleteTemplate.value = null
  } catch (e: any) {
    actionError.value = e.response?.data?.message ?? e.message ?? 'Delete failed.'
  } finally {
    isDeleteLoading.value = false
  }
}

async function handleDuplicate() {
  if (!confirmDuplicateTemplate.value) return
  isDuplicateLoading.value = true
  actionError.value = ''
  try {
    await duplicateTemplate(confirmDuplicateTemplate.value.id)
    confirmDuplicateTemplate.value = null
    // Refresh list to show the new copy
    await selectScope(activeScope.value)
  } catch (e: any) {
    actionError.value = e.response?.data?.message ?? e.message ?? 'Duplicate failed.'
  } finally {
    isDuplicateLoading.value = false
  }
}

onMounted(async () => {
  await fetchTemplates()
})
</script>

<template>
  <div class="w-full bg-white">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
      <div>
        <h1 class="font-bold text-gray-600">My Templates</h1>
        <p class="text-xs text-gray-400 mt-0.5">Saved routing configurations to speed up document creation.</p>
      </div>
      <BaseButton type="button" @click="router.push({ name: 'template-create' })"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-teal-600 rounded-lg hover:bg-teal-700">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
          <path
            d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
        </svg>
        Create Template
      </BaseButton>
    </div>

    <!-- Main Content -->
    <div class="flex flex-col" style="height: calc(100vh - 130px)">
      <div class="flex items-end justify-between px-4 pt-2 bg-white border-b border-gray-200">
        <!-- Search -->
        <div class="relative pb-2">
          <input v-model="searchQuery" @input="onSearch" type="text" placeholder="Search templates..."
            class="w-60 py-1.5 pl-8 pr-3 text-xs border border-gray-200 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500" />
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
            class="absolute left-2.5 top-2 size-3.5 text-gray-400">
            <path fill-rule="evenodd"
              d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
              clip-rule="evenodd" />
          </svg>
        </div>

        <!-- Tabs Row -->
        <div class="grid grid-cols-4 gap-1">
          <button v-for="tab in scopeTabs" :key="tab.key" type="button" @click="selectScope(tab.key)" :class="[
            'px-4 py-2 text-xs font-medium rounded-t-lg transition-colors relative',
            activeScope === tab.key
              ? 'bg-teal-700 text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]">
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Scrollable Content -->
      <div class="flex-1 p-4 overflow-y-auto bg-gray-50">
        <!-- Loading -->
        <div v-if="isLoading" class="flex items-center justify-center py-16">
          <div class="border-2 border-teal-600 rounded-full size-8 border-t-transparent animate-spin"></div>
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredTemplates.length === 0" class="py-16 text-center">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="mx-auto mb-3 text-gray-300 size-12">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 16.5H5.625c-.621 0-1.125-.504-1.125-1.125V4.875c0-.621.504-1.125 1.125-1.125h9.75c.621 0 1.125.504 1.125 1.125v6.75" />
          </svg>
          <p class="text-sm text-gray-400">{{ hasFilters ? 'No templates match your filters.' : 'No templates found.' }}
          </p>
          <button v-if="!hasFilters" type="button" @click="router.push({ name: 'template-create' })"
            class="mt-3 text-sm text-teal-700 hover:underline">
            Create your first template
          </button>
        </div>

        <!-- Template grid -->
        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <TemplateCard v-for="tpl in filteredTemplates" :key="tpl.id" :template="tpl" :can-edit="canEdit(tpl)"
            :can-delete="canDelete(tpl)" @use="useModalTemplate = $event"
            @edit="router.push({ name: 'template-edit', params: { id: $event.id } })"
            @duplicate="confirmDuplicateTemplate = $event" @delete="confirmDeleteTemplate = $event" />
        </div>
      </div>
    </div>

    <!-- Use Template Modal -->
    <UseTemplateModal v-if="useModalTemplate" :template="useModalTemplate" @close="useModalTemplate = null" />

    <!-- Confirm Duplicate Modal -->
    <div v-if="confirmDuplicateTemplate" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
      <div class="w-full max-w-sm p-5 space-y-4 bg-white shadow-xl rounded-xl">
        <h3 class="text-base font-semibold text-gray-800">Duplicate Template</h3>
        <p class="text-sm text-gray-600">
          This will create a personal copy named
          <strong>"Copy of {{ confirmDuplicateTemplate.name }}"</strong>.
        </p>
        <p v-if="actionError" class="text-sm text-red-600">{{ actionError }}</p>
        <div class="flex justify-end gap-3">
          <button type="button" @click="confirmDuplicateTemplate = null"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
          </button>
          <BaseButton type="button" :disabled="isDuplicateLoading" @click="handleDuplicate">
            {{ isDuplicateLoading ? 'Duplicating…' : 'Duplicate' }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div v-if="confirmDeleteTemplate" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
      <div class="w-full max-w-sm p-5 space-y-4 bg-white shadow-xl rounded-xl">
        <h3 class="text-base font-semibold text-gray-800">Delete Template</h3>
        <p class="text-sm text-gray-600">
          Are you sure you want to delete
          <strong>"{{ confirmDeleteTemplate.name }}"</strong>?
          This action cannot be undone.
        </p>
        <p v-if="actionError" class="text-sm text-red-600">{{ actionError }}</p>
        <div class="flex justify-end gap-3">
          <button type="button" @click="confirmDeleteTemplate = null"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
          </button>
          <button type="button" :disabled="isDeleteLoading" @click="handleDelete"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50">
            {{ isDeleteLoading ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
