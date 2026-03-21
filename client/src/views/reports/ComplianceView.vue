<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useReports } from '@/composables/useReports'
import ScrollableContainer from '@/components/ScrollableContainer.vue'
import FrmLabel from '@/components/ui/labels/FrmLabel.vue'

const router = useRouter()
const { filters, loading, error, complianceData,
        fetchCompliance, exportCompliance, setFilters } = useReports()

const localPeriod = ref<'week' | 'month' | 'quarter' | 'year'>(
  (filters.value.period as any) || 'month'
)

async function load() {
  setFilters({ period: localPeriod.value })
  await fetchCompliance()
}

async function exportReport(format: 'pdf' | 'xlsx') {
  setFilters({ period: localPeriod.value })
  await exportCompliance(format)
}

function formatKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

onMounted(load)
</script>

<template>
  <ScrollableContainer padding="0" rem="50px" background="gray-50" class="w-full">
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
            <h1 class="text-lg font-bold text-gray-800">ISO Compliance</h1>
            <p class="text-xs text-gray-400">Reports / ISO Compliance</p>
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
      <div class="flex flex-wrap items-end gap-4 p-4 mb-5 bg-white border border-gray-200 rounded-xl">
        <div>
          <FrmLabel label="Period" class="mb-1" />
          <select v-model="localPeriod"
            class="block px-3 py-2 text-xs text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-teal-500 focus:border-teal-500">
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
          </select>
        </div>
        <button @click="load"
          class="px-4 py-2 text-xs font-medium text-white transition-colors bg-teal-600 rounded-lg hover:bg-teal-700">
          Apply
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-16">
        <div class="border-2 border-teal-600 rounded-full size-8 border-t-transparent animate-spin"></div>
      </div>

      <div v-else-if="error" class="py-16 text-sm text-center text-red-500">{{ error }}</div>

      <template v-else-if="complianceData?.data">
        <!-- ISO 9001 -->
        <div class="mb-6">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-1 rounded bg-emerald-500"></div>
            <h2 class="text-sm font-bold text-gray-700">ISO 9001:2015 - Quality Management</h2>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div
              v-for="(section, clauseNo) in complianceData.data.iso_9001"
              :key="clauseNo"
              class="p-4 bg-white border border-gray-200 rounded-xl"
            >
              <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">{{ clauseNo }}</span>
                <span class="text-xs font-semibold text-gray-700">{{ section.label }}</span>
              </div>
              <p class="mb-3 text-xs text-gray-400">{{ section.description }}</p>
              <div class="space-y-1">
                <template v-for="(val, key) in section.metrics" :key="key">
                  <div v-if="!Array.isArray(val)" class="flex justify-between text-xs">
                    <span class="text-gray-600">{{ formatKey(key as string) }}</span>
                    <span class="font-semibold text-gray-800">{{ val }}</span>
                  </div>
                  <div v-else-if="(val as any[]).length" class="mt-2">
                    <div class="mb-1 text-xs text-gray-400">{{ formatKey(key as string) }}</div>
                    <div class="space-y-0.5">
                      <div v-for="item in (val as any[])" :key="JSON.stringify(item)" class="flex justify-between text-xs">
                        <span class="text-gray-600 truncate">{{ Object.values(item)[0] }}</span>
                        <span class="ml-2 font-semibold text-gray-700">{{ Object.values(item)[1] }}</span>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- ISO 15489 -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-1 bg-teal-500 rounded"></div>
            <h2 class="text-sm font-bold text-gray-700">ISO 15489-1:2016 - Records Management</h2>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div
              v-for="(section, clauseNo) in complianceData.data.iso_15489"
              :key="clauseNo"
              class="p-4 bg-white border border-gray-200 rounded-xl"
            >
              <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-bold bg-teal-100 text-teal-700 px-2 py-0.5 rounded">{{ clauseNo }}</span>
                <span class="text-xs font-semibold text-gray-700">{{ section.label }}</span>
              </div>
              <p class="mb-3 text-xs text-gray-400">{{ section.description }}</p>
              <div class="space-y-1">
                <div v-for="(val, key) in section.metrics" :key="key" class="flex justify-between text-xs">
                  <span class="text-gray-600">{{ formatKey(key as string) }}</span>
                  <span class="font-semibold text-gray-800">{{ val }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </ScrollableContainer>
</template>
