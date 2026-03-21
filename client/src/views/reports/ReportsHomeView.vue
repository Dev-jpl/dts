<script setup lang="ts">
import { useRouter } from 'vue-router'
import ScrollableContainer from '@/components/ScrollableContainer.vue'

const router = useRouter()

const reports = [
  {
    name: 'Office Performance',
    path: '/reports/office-performance',
    icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
    color: 'bg-teal-600',
    description: 'Received counts, on-time rates, avg turnaround, and return rates per office.',
    exports: ['PDF', 'Excel'],
  },
  {
    name: 'Document Pipeline',
    path: '/reports/pipeline',
    icon: 'M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3',
    color: 'bg-teal-700',
    description: 'Document status distribution, overdue list, and age distribution.',
    exports: ['PDF', 'Excel'],
  },
  {
    name: 'ISO Compliance',
    path: '/reports/compliance',
    icon: 'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z',
    color: 'bg-emerald-600',
    description: 'ISO 9001:2015 and ISO 15489-1:2016 compliance metrics mapped from DTS data.',
    exports: ['PDF', 'Excel'],
  },
  {
    name: 'Transaction Audit',
    path: '/reports/audit',
    icon: 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
    color: 'bg-gray-700',
    description: 'Full integrity log for a specific document: all logs, versions, parties, notes.',
    exports: ['PDF only'],
  },
  {
    name: 'Individual Turnaround',
    path: '/reports/turnaround',
    icon: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    color: 'bg-amber-600',
    description: 'Turnaround time breakdown by action type, document type, urgency, and trend.',
    exports: ['PDF', 'Excel'],
  },
]
</script>

<template>
  <ScrollableContainer padding="0" rem="50px" background="gray-50">
    <div class="w-full max-w-5xl px-4 py-8 mx-auto sm:px-6">

      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-lg font-bold text-gray-800">Reports</h1>
        <p class="mt-1 text-xs text-gray-500">Generate, view, and export document tracking reports.</p>
      </div>

      <!-- Report cards -->
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <button
          v-for="report in reports"
          :key="report.path"
          type="button"
          @click="router.push(report.path)"
          class="p-5 text-left transition-all bg-white border border-gray-200 group rounded-xl hover:shadow-md hover:border-teal-300"
        >
          <div class="flex items-start gap-4">
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center text-white flex-shrink-0', report.color]">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" :d="report.icon" />
              </svg>
            </div>
            <div>
              <h2 class="text-sm font-semibold text-gray-800 group-hover:text-teal-700">{{ report.name }}</h2>
              <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ report.description }}</p>
              <div class="flex flex-wrap gap-1 mt-2">
                <span
                  v-for="tag in report.exports"
                  :key="tag"
                  class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded"
                >{{ tag }}</span>
              </div>
            </div>
          </div>
        </button>
      </div>
    </div>
  </ScrollableContainer>
</template>
