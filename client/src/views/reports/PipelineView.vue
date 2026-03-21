<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useReports } from '@/composables/useReports'
import ScrollableContainer from '@/components/ScrollableContainer.vue'

const router = useRouter()
const { loading, error, pipelineData, fetchPipeline, exportPipeline } = useReports()

const ageLabels: Record<string, string> = {
  under_7:  '< 7 days',
  '7_to_30':  '7-30 days',
  '30_to_90': '30-90 days',
  over_90:  '> 90 days',
}

async function exportReport(format: 'pdf' | 'xlsx') {
  await exportPipeline(format)
}

function statusBorderColor(status: string) {
  const map: Record<string, string> = {
    Draft:     'border-gray-200',
    Active:    'border-teal-200',
    Returned:  'border-amber-200',
    Completed: 'border-emerald-200',
    Closed:    'border-gray-300',
  }
  return map[status] ?? 'border-gray-200'
}

function statusTextColor(status: string) {
  const map: Record<string, string> = {
    Draft:     'text-gray-600',
    Active:    'text-teal-600',
    Returned:  'text-amber-600',
    Completed: 'text-emerald-600',
    Closed:    'text-gray-500',
  }
  return map[status] ?? 'text-gray-600'
}

function urgencyClass(level: string) {
  const map: Record<string, string> = {
    Urgent:  'bg-red-100 text-red-700',
    High:    'bg-orange-100 text-orange-700',
    Normal:  'bg-blue-100 text-blue-700',
    Routine: 'bg-gray-100 text-gray-600',
  }
  return map[level] ?? 'bg-gray-100 text-gray-600'
}

onMounted(fetchPipeline)
</script>

<template>
  <ScrollableContainer padding="0" rem="50px" background="gray-50">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-6">

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
            <h1 class="text-lg font-bold text-gray-800">Document Pipeline</h1>
            <p class="text-xs text-gray-400">Reports / Document Pipeline</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="exportReport('pdf')"
            class="border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors text-xs px-3 py-1.5">
            Export PDF
          </button>
          <button @click="exportReport('xlsx')"
            class="border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors text-xs px-3 py-1.5">
            Export Excel
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-16">
        <div class="size-8 border-2 border-teal-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">{{ error }}</div>

      <template v-else-if="pipelineData?.data">
        <!-- Status Summary -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-5">
          <div
            v-for="(count, status) in pipelineData.data.summary"
            :key="status"
            :class="['bg-white border rounded-xl p-4 text-center', statusBorderColor(status as string)]"
          >
            <div class="text-2xl font-bold" :class="statusTextColor(status as string)">{{ count }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ status }}</div>
          </div>
        </div>

        <!-- Age Distribution -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-5">
          <h2 class="font-semibold text-gray-700 mb-3 text-xs uppercase tracking-wide">Active Document Age Distribution</h2>
          <div class="grid grid-cols-4 gap-3">
            <div v-for="(label, key) in ageLabels" :key="key" class="text-center bg-amber-50 rounded-lg p-3">
              <div class="text-xl font-bold text-amber-700">{{ pipelineData.data.aged[key] ?? 0 }}</div>
              <div class="text-xs text-amber-600 mt-0.5">{{ label }}</div>
            </div>
          </div>
        </div>

        <!-- Overdue List -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h2 class="font-semibold text-gray-700 text-xs uppercase tracking-wide">
              Overdue Documents
              <span class="ml-2 bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full normal-case">
                {{ pipelineData.data.overdue.length }}
              </span>
            </h2>
          </div>
          <div v-if="pipelineData.data.overdue.length === 0" class="py-10 text-center text-gray-400 text-sm">
            No overdue documents.
          </div>
          <table v-else class="w-full text-sm">
            <thead class="bg-red-50 text-red-700 text-xs uppercase">
              <tr>
                <th class="px-4 py-3 text-left">Document</th>
                <th class="px-4 py-3 text-left">Subject</th>
                <th class="px-4 py-3 text-center">Urgency</th>
                <th class="px-4 py-3 text-left">Office</th>
                <th class="px-4 py-3 text-center">Due Date</th>
                <th class="px-4 py-3 text-center">Days Overdue</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in pipelineData.data.overdue"
                :key="item.transaction_no"
                class="border-t border-gray-100 hover:bg-gray-50"
              >
                <td class="px-4 py-3">
                  <RouterLink :to="`/view-document/${item.transaction_no}`" class="text-teal-600 hover:underline text-xs">
                    {{ item.document_no }}
                  </RouterLink>
                </td>
                <td class="px-4 py-3 text-gray-700 truncate max-w-xs text-xs">{{ item.subject }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="urgencyClass(item.urgency_level)" class="text-xs px-2 py-0.5 rounded-full">
                    {{ item.urgency_level }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">{{ item.office_name }}</td>
                <td class="px-4 py-3 text-center text-gray-600 text-xs">{{ item.due_date }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full font-semibold">
                    {{ item.days_overdue }}d
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </ScrollableContainer>
</template>
