<template>
  <div class="flex-1 w-full h-full">
    <div class="p-4">
      <h1 class="text-lg font-semibold text-gray-800">Office Members</h1>
      <p class="mt-1 text-xs text-gray-500">View all users assigned to your office.</p>
    </div>
    <hr class="mb-6 text-gray-200" />

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <div v-else-if="officeMembers.length === 0" class="py-8 text-center text-xs text-gray-500">
      No members found in this office.
    </div>

    <div v-else class="p-4 overflow-hidden bg-white border border-gray-200 rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
              Name
            </th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
              Email
            </th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
              Role
            </th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
              Joined
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <tr v-for="member in officeMembers" :key="member.id">
            <td class="px-4 py-3 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 bg-teal-100 rounded-full">
                  <span class="text-xs font-medium text-teal-700">
                    {{ getInitials(member.name) }}
                  </span>
                </div>
                <div class="ml-3">
                  <p class="text-xs font-medium text-gray-800">{{ member.name }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
              {{ member.email }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                :class="getRoleBadgeClasses(member.role)"
              >
                {{ member.role }}
              </span>
            </td>
            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
              {{ formatDate(member.created_at) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useSettings } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'

const { loading, officeMembers, fetchOfficeMembers } = useSettings()
const toast = useToast()

onMounted(async () => {
  try {
    await fetchOfficeMembers()
  } catch (e) {
    toast.error('Failed to load office members')
  }
})

function getInitials(name: string): string {
  return name
    .split(' ')
    .map(part => part.charAt(0))
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

function getRoleBadgeClasses(role: string): string {
  const lower = role.toLowerCase()
  if (lower === 'admin') {
    return 'bg-red-100 text-red-800'
  }
  if (lower === 'superior' || lower === 'head') {
    return 'bg-purple-100 text-purple-800'
  }
  return 'bg-gray-100 text-gray-800'
}

function formatDate(dateStr: string): string {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}
</script>
