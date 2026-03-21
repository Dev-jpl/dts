<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useReports } from '@/composables/useReports'
import ScrollableContainer from '@/components/ScrollableContainer.vue'
import FrmLabel from '@/components/ui/labels/FrmLabel.vue'

const router = useRouter()
const { filters, loading, error, officePerformanceData,
        fetchOfficePerformance, exportOfficePerformance, setFilters } = useReports()

const localPeriod = ref<'week' | 'month' | 'quarter' | 'year'>(
  (filters.value.period as any) || 'month'
)

async function load() {
  setFilters({ period: localPeriod.value })
  await fetchOfficePerformance()
}

async function exportReport(format: 'pdf' | 'xlsx') {
  setFilters({ period: localPeriod.value })
  await exportOfficePerformance(format)
}

function onTimeClass(rate: number) {
  if (rate >= 90) return 'text-emerald-600 font-semibold'
  if (rate >= 70) return 'text-amber-600'
  return 'text-red-600'
}

function returnRateClass(rate: number) {
  if (rate <= 5)  return 'text-emerald-600'
  if (rate <= 15) return 'text-amber-600'
  return 'text-red-600'
}

onMounted(load)
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
            <h1 class="text-lg font-bold text-gray-800">Office Performance</h1>
            <p class="text-xs text-gray-400">Reports / Office Performance</p>
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

      <!-- Error -->
      <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">{{ error }}</div>

      <!-- Table -->
      <div v-else-if="officePerformanceData?.data?.length" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-teal-50 text-teal-700 text-xs uppercase">
            <tr>
              <th class="px-4 py-3 text-left">Office</th>
              <th class="px-4 py-3 text-center">Received</th>
              <th class="px-4 py-3 text-center">On-Time %</th>
              <th class="px-4 py-3 text-center">Avg Receive (hrs)</th>
              <th class="px-4 py-3 text-center">Avg Complete (hrs)</th>
              <th class="px-4 py-3 text-center">Return Rate %</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="row in officePerformanceData.data" :key="row.office_id">
              <tr class="border-t border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800 text-xs">{{ row.office_name }}</td>
                <td class="px-4 py-3 text-center text-xs">{{ row.received_count }}</td>
                <td class="px-4 py-3 text-center text-xs">
                  <span v-if="row.on_time_rate !== null" :class="onTimeClass(row.on_time_rate)">
                    {{ row.on_time_rate }}%
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-4 py-3 text-center text-xs">{{ row.avg_time_to_receive ?? '-' }}</td>
                <td class="px-4 py-3 text-center text-xs">{{ row.avg_time_to_complete ?? '-' }}</td>
                <td class="px-4 py-3 text-center text-xs">
                  <span :class="returnRateClass(row.return_rate)">{{ row.return_rate }}%</span>
                </td>
              </tr>
              <!-- Return reasons sub-row -->
              <tr v-if="row.return_reasons?.length" class="bg-gray-50 border-t border-gray-100">
                <td colspan="6" class="px-4 py-1.5 text-xs text-gray-500">
                  <strong>Return reasons:</strong>
                  <span v-for="(rr, i) in row.return_reasons" :key="i">
                    {{ rr.reason }} ({{ rr.count }}){{ i < row.return_reasons.length - 1 ? ' · ' : '' }}
                  </span>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Empty -->
      <div v-else-if="officePerformanceData" class="text-center py-16 text-gray-400 text-sm">
        No data for the selected period.
      </div>
    </div>
  </ScrollableContainer>
</template>
