<template>
  <div class="flex-1 w-full h-full">
    <div class="p-4">
      <h1 class="text-lg font-semibold text-gray-800">Active Sessions</h1>
      <p class="mt-1 text-xs text-gray-500">Manage your active sessions across devices. You can revoke any session to log out that device.</p>
    </div>
    <hr class="mb-6 text-gray-200" />

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
    </div>

    <div v-else-if="sessions.length === 0" class="py-8 text-center text-xs text-gray-500">
      No active sessions found.
    </div>

    <div v-else class="max-w-3xl p-4 space-y-3">
      <div
        v-for="session in sessions"
        :key="session.id"
        class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg"
      >
        <div class="flex items-center gap-4">
          <div class="flex-shrink-0">
            <div class="flex items-center justify-center w-10 h-10 bg-gray-100 rounded-full">
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <p class="text-xs font-medium text-gray-800">
                {{ session.name || 'Personal Access Token' }}
              </p>
              <span
                v-if="session.is_current"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700"
              >
                Current
              </span>
            </div>
            <div class="text-xs text-gray-500 space-x-2">
              <span v-if="session.ip_address">IP: {{ session.ip_address }}</span>
              <span v-if="session.last_used_at">· Last used: {{ formatDate(session.last_used_at) }}</span>
              <span v-else>· Created: {{ formatDate(session.created_at) }}</span>
            </div>
            <p v-if="session.user_agent" class="mt-1 text-xs text-gray-400 truncate max-w-md">
              {{ session.user_agent }}
            </p>
          </div>
        </div>
        <button
          v-if="!session.is_current"
          @click="handleRevoke(session.id)"
          :disabled="revokingId === session.id"
          class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50 disabled:opacity-50"
        >
          <svg v-if="revokingId === session.id" class="animate-spin -ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Revoke
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettings, type Session } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'

const { loading, sessions, fetchSessions, revokeSession } = useSettings()
const toast = useToast()

const revokingId = ref<string | null>(null)

onMounted(async () => {
  try {
    await fetchSessions()
  } catch (e) {
    toast.error('Failed to load sessions')
  }
})

function formatDate(dateStr: string): string {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function handleRevoke(sessionId: string) {
  revokingId.value = sessionId
  try {
    await revokeSession(sessionId)
    toast.success('Session revoked successfully')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Failed to revoke session')
  } finally {
    revokingId.value = null
  }
}
</script>
