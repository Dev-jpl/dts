<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useReports } from '@/composables/useReports'
import ScrollableContainer from '@/components/ScrollableContainer.vue'
import FrmLabel from '@/components/ui/labels/FrmLabel.vue'

const route = useRoute()
const router = useRouter()
const { loading, error, auditData, fetchAudit, exportAudit } = useReports()

const docNo     = ref((route.query.doc as string) || '')
const activeTab = ref('logs')

const doc = computed(() => auditData.value?.data?.document ?? {})

const tabs = computed(() => [
  { key: 'logs',        label: 'Transaction Logs', count: auditData.value?.data?.logs?.length },
  { key: 'recipients',  label: 'Recipients',       count: auditData.value?.data?.recipients?.length },
  { key: 'versions',    label: 'Versions',          count: auditData.value?.data?.versions?.length },
  { key: 'notes',       label: 'Notes',             count: auditData.value?.data?.notes?.length },
  { key: 'attachments', label: 'Attachments',       count: auditData.value?.data?.attachments?.length },
])

async function load() {
  if (!docNo.value.trim()) return
  await fetchAudit(docNo.value.trim())
  activeTab.value = 'logs'
}

async function downloadPdf() {
  await exportAudit(docNo.value.trim())
}

function formatDate(ts: string) {
  return new Date(ts).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })
}

onMounted(() => {
  if (docNo.value) load()
})
</script>

<template>
  <ScrollableContainer padding="0" rem="50px" background="gray-50">
    <div class="w-full px-4 py-6 mx-auto sm:px-6">

      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-2">
          <button type="button" @click="router.push('/reports')"
            class="p-1 text-gray-400 rounded hover:text-gray-600 hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
              <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
            </svg>
          </button>
          <div>
            <h1 class="text-lg font-bold text-gray-800">Transaction Audit</h1>
            <p class="text-xs text-gray-400">Reports / Transaction Audit</p>
          </div>
        </div>
        <button
          v-if="auditData?.data"
          @click="downloadPdf"
          class="px-4 py-2 text-xs font-medium text-white transition-colors bg-teal-600 rounded-lg hover:bg-teal-700"
        >
          Export PDF
        </button>
      </div>

      <!-- Document lookup -->
      <div class="p-4 mb-5 bg-white border border-gray-200 rounded-xl">
        <FrmLabel label="Document Number" class="mb-1" />
        <div class="flex gap-3">
          <input
            v-model="docNo"
            type="text"
            placeholder="e.g. DOC-2026-00001"
            class="flex-1 px-3 py-2 text-xs border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
            @keyup.enter="load"
          />
          <button @click="load" :disabled="!docNo.trim()"
            class="px-4 py-2 text-xs font-medium text-white transition-colors bg-teal-600 rounded-lg hover:bg-teal-700 disabled:opacity-50">
            Load Audit
          </button>
        </div>
        <p class="text-[10px] text-amber-600 mt-2">
          Audit reports are exported as PDF only - integrity protected.
        </p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-16">
        <div class="border-2 border-teal-600 rounded-full size-8 border-t-transparent animate-spin"></div>
      </div>

      <div v-else-if="error" class="py-16 text-sm text-center text-red-500">{{ error }}</div>

      <template v-else-if="auditData?.data">
        <!-- Document info -->
        <div class="p-4 mb-5 border border-teal-200 bg-teal-50 rounded-xl">
          <div class="grid grid-cols-2 gap-3 text-xs md:grid-cols-4">
            <div><span class="block font-semibold text-teal-600">Document No</span>{{ doc.document_no }}</div>
            <div><span class="block font-semibold text-teal-600">Status</span>{{ doc.status }}</div>
            <div><span class="block font-semibold text-teal-600">Type</span>{{ doc.document_type ?? '-' }}</div>
            <div><span class="block font-semibold text-teal-600">Origin</span>{{ doc.office_name ?? '-' }}</div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 mb-4">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="['text-xs px-3 py-1.5 rounded-lg border transition-colors',
              activeTab === tab.key
                ? 'bg-teal-600 text-white border-teal-600'
                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50']"
          >
            {{ tab.label }}
            <span v-if="tab.count !== undefined" class="ml-1 opacity-70">({{ tab.count }})</span>
          </button>
        </div>

        <!-- Transaction Logs -->
        <div v-if="activeTab === 'logs'" class="overflow-hidden bg-white border border-gray-200 rounded-xl">
          <table class="w-full text-xs">
            <thead class="text-teal-700 uppercase bg-teal-50">
              <tr>
                <th class="px-4 py-2 text-left">Timestamp</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-left">Office</th>
                <th class="px-4 py-2 text-left">Transaction</th>
                <th class="px-4 py-2 text-left">Reason</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in auditData.data.logs" :key="log.id" class="border-t border-gray-100">
                <td class="px-4 py-2 text-gray-500">{{ formatDate(log.created_at) }}</td>
                <td class="px-4 py-2">
                  <span class="bg-teal-100 text-teal-700 px-2 py-0.5 rounded">{{ log.status }}</span>
                </td>
                <td class="px-4 py-2 text-gray-700">{{ log.office_name ?? '-' }}</td>
                <td class="px-4 py-2 text-gray-400">{{ log.transaction_no }}</td>
                <td class="px-4 py-2 text-gray-600">{{ log.reason ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Recipients -->
        <div v-if="activeTab === 'recipients'" class="overflow-hidden bg-white border border-gray-200 rounded-xl">
          <table class="w-full text-xs">
            <thead class="text-teal-700 uppercase bg-teal-50">
              <tr>
                <th class="px-4 py-2 text-left">Office</th>
                <th class="px-4 py-2 text-left">Transaction</th>
                <th class="px-4 py-2 text-center">Type</th>
                <th class="px-4 py-2 text-center">Seq</th>
                <th class="px-4 py-2 text-center">Active</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in auditData.data.recipients" :key="r.id" class="border-t border-gray-100">
                <td class="px-4 py-2">{{ r.office_name }}</td>
                <td class="px-4 py-2 text-gray-400">{{ r.transaction_no }}</td>
                <td class="px-4 py-2 text-center">{{ r.recipient_type }}</td>
                <td class="px-4 py-2 text-center">{{ r.sequence }}</td>
                <td class="px-4 py-2 text-center">
                  <span :class="r.isActive ? 'text-emerald-600' : 'text-gray-400'">
                    {{ r.isActive ? 'Yes' : 'No' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Versions -->
        <div v-if="activeTab === 'versions'" class="overflow-hidden bg-white border border-gray-200 rounded-xl">
          <div v-if="!auditData.data.versions.length" class="py-10 text-sm text-center text-gray-400">No versions recorded.</div>
          <table v-else class="w-full text-xs">
            <thead class="text-teal-700 uppercase bg-teal-50">
              <tr>
                <th class="px-4 py-2 text-left">Version</th>
                <th class="px-4 py-2 text-left">Action Type</th>
                <th class="px-4 py-2 text-left">Changed By</th>
                <th class="px-4 py-2 text-left">Changed At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in auditData.data.versions" :key="v.id" class="border-t border-gray-100">
                <td class="px-4 py-2 font-mono">v{{ v.version_number }}</td>
                <td class="px-4 py-2">{{ v.action_type }}</td>
                <td class="px-4 py-2">{{ v.changed_by_name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ formatDate(v.changed_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Notes -->
        <div v-if="activeTab === 'notes'" class="space-y-3">
          <div v-if="!auditData.data.notes.length" class="py-10 text-sm text-center text-gray-400">No notes.</div>
          <div v-for="note in auditData.data.notes" :key="note.id" class="p-4 bg-white border border-gray-200 rounded-xl">
            <div class="flex items-center justify-between mb-1 text-xs text-gray-500">
              <span class="font-semibold text-gray-700">{{ note.office_name }}</span>
              <span>{{ formatDate(note.created_at) }}</span>
            </div>
            <p class="text-xs text-gray-700">{{ note.note }}</p>
          </div>
        </div>

        <!-- Attachments -->
        <div v-if="activeTab === 'attachments'" class="overflow-hidden bg-white border border-gray-200 rounded-xl">
          <div v-if="!auditData.data.attachments.length" class="py-10 text-sm text-center text-gray-400">No attachments.</div>
          <table v-else class="w-full text-xs">
            <thead class="text-teal-700 uppercase bg-teal-50">
              <tr>
                <th class="px-4 py-2 text-left">Filename</th>
                <th class="px-4 py-2 text-left">Transaction</th>
                <th class="px-4 py-2 text-left">Uploaded At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="att in auditData.data.attachments" :key="att.id" class="border-t border-gray-100">
                <td class="px-4 py-2">{{ att.original_name ?? att.file_name ?? '-' }}</td>
                <td class="px-4 py-2 text-gray-400">{{ att.transaction_no }}</td>
                <td class="px-4 py-2 text-gray-500">{{ formatDate(att.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </ScrollableContainer>
</template>
