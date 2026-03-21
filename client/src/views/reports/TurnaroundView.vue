<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useReports } from '@/composables/useReports'
import ScrollableContainer from '@/components/ScrollableContainer.vue'
import FrmLabel from '@/components/ui/labels/FrmLabel.vue'

const router = useRouter()
const { filters, loading, error, turnaroundData,
        fetchTurnaround, exportTurnaround, setFilters } = useReports()

const localPeriod = ref<'week' | 'month' | 'quarter' | 'year'>(
  (filters.value.period as any) || 'month'
)

async function load() {
  setFilters({ period: localPeriod.value })
  await fetchTurnaround()
}

async function exportReport(format: 'pdf' | 'xlsx') {
  setFilters({ period: localPeriod.value })
  await exportTurnaround(format)
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

onMounted(load)
</script>

<template>
  <ScrollableContainer padding="0" rem="50px" background="gray-50">
    <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 py-6">

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
            <h1 class="text-lg font-bold text-gray-800">Individual Turnaround</h1>
            <p class="text-xs text-gray-400">Reports / Individual Turnaround</p>
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

      <!-- Filters -->
      <div class="bg-white border border-gray-200 rounded-xl p-4 mb-5 flex flex-wrap gap-4 items-end">
        <div>
          <FrmLabel label="Period" class="mb-1" />
          <select v-model="localPeriod"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-teal-500 focus:border-teal-500 block px-3 py-2">
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
          </select>
        </div>
        <button @click="load"
          class="bg-teal-600 text-white rounded-lg font-medium hover:bg-teal-700 transition-colors text-xs px-4 py-2">
          Apply
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-16">
        <div class="size-8 border-2 border-teal-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">{{ error }}</div>

      <template v-else-if="turnaroundData?.data">
        <!-- Overall stats -->
        <div class="grid grid-cols-2 gap-4 mb-5">
          <div class="bg-teal-50 border border-teal-200 rounded-xl p-5 text-center">
            <div class="text-3xl font-bold text-teal-700">
              {{ turnaroundData.data.overall.avg_hours ?? '-' }}
              <small class="text-sm font-normal">hrs</small>
            </div>
            <div class="text-xs text-teal-600 mt-1">Average Turnaround</div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl p-5 text-center">
            <div class="text-3xl font-bold text-gray-700">{{ turnaroundData.data.overall.completed_count }}</div>
            <div class="text-xs text-gray-500 mt-1">Completed Actions</div>
          </div>
        </div>

        <!-- Tables (2x2 grid) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- By Action Type -->
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-xs uppercase tracking-wide">By Action Type</div>
            <table class="w-full text-xs">
              <thead class="bg-teal-50 text-teal-700">
                <tr>
                  <th class="px-3 py-2 text-left">Action Type</th>
                  <th class="px-3 py-2 text-center">Avg (hrs)</th>
                  <th class="px-3 py-2 text-center">Count</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!turnaroundData.data.by_action_type.length">
                  <td colspan="3" class="text-center py-6 text-gray-400">No data</td>
                </tr>
                <tr v-for="row in turnaroundData.data.by_action_type" :key="row.action_type" class="border-t border-gray-100">
                  <td class="px-3 py-2 text-gray-700">{{ row.action_type }}</td>
                  <td class="px-3 py-2 text-center font-semibold">{{ row.avg_hours ?? '-' }}</td>
                  <td class="px-3 py-2 text-center text-gray-500">{{ row.completed_count }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- By Document Type -->
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-xs uppercase tracking-wide">By Document Type</div>
            <table class="w-full text-xs">
              <thead class="bg-teal-50 text-teal-700">
                <tr>
                  <th class="px-3 py-2 text-left">Document Type</th>
                  <th class="px-3 py-2 text-center">Avg (hrs)</th>
                  <th class="px-3 py-2 text-center">Count</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!turnaroundData.data.by_document_type.length">
                  <td colspan="3" class="text-center py-6 text-gray-400">No data</td>
                </tr>
                <tr v-for="row in turnaroundData.data.by_document_type" :key="row.document_type" class="border-t border-gray-100">
                  <td class="px-3 py-2 text-gray-700">{{ row.document_type }}</td>
                  <td class="px-3 py-2 text-center font-semibold">{{ row.avg_hours ?? '-' }}</td>
                  <td class="px-3 py-2 text-center text-gray-500">{{ row.completed_count }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- By Urgency -->
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-xs uppercase tracking-wide">By Urgency Level</div>
            <table class="w-full text-xs">
              <thead class="bg-teal-50 text-teal-700">
                <tr>
                  <th class="px-3 py-2 text-left">Urgency</th>
                  <th class="px-3 py-2 text-center">Avg (hrs)</th>
                  <th class="px-3 py-2 text-center">Count</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in turnaroundData.data.by_urgency" :key="row.urgency_level" class="border-t border-gray-100">
                  <td class="px-3 py-2">
                    <span :class="urgencyClass(row.urgency_level)" class="px-2 py-0.5 rounded-full">
                      {{ row.urgency_level }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-center font-semibold">{{ row.avg_hours ?? '-' }}</td>
                  <td class="px-3 py-2 text-center text-gray-500">{{ row.completed_count }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Trend -->
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-xs uppercase tracking-wide">Monthly Trend</div>
            <table class="w-full text-xs">
              <thead class="bg-teal-50 text-teal-700">
                <tr>
                  <th class="px-3 py-2 text-left">Period</th>
                  <th class="px-3 py-2 text-center">Avg (hrs)</th>
                  <th class="px-3 py-2 text-center">Count</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!turnaroundData.data.trend.length">
                  <td colspan="3" class="text-center py-6 text-gray-400">No data</td>
                </tr>
                <tr v-for="row in turnaroundData.data.trend" :key="row.period" class="border-t border-gray-100">
                  <td class="px-3 py-2 font-mono">{{ row.period }}</td>
                  <td class="px-3 py-2 text-center font-semibold">{{ row.avg_hours ?? '-' }}</td>
                  <td class="px-3 py-2 text-center text-gray-500">{{ row.completed_count }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </ScrollableContainer>
</template>
