import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ReportPeriod = 'week' | 'month' | 'quarter' | 'year' | 'custom'

export interface ReportFilters {
  period: ReportPeriod
  date_from?: string
  date_to?: string
  office_ids?: string[]
  status?: string
}

export const useReportsStore = defineStore('reports', () => {
  // ── Shared filter state ────────────────────────────────────────────────────
  const filters = ref<ReportFilters>({ period: 'month' })

  // ── Per-report data ────────────────────────────────────────────────────────
  const officePerformanceData = ref<any>(null)
  const pipelineData          = ref<any>(null)
  const complianceData        = ref<any>(null)
  const auditData             = ref<any>(null)
  const turnaroundData        = ref<any>(null)

  // ── Accessible offices (from API response) ─────────────────────────────────
  const offices = ref<{ id: string; office_name: string }[]>([])

  // ── Loading/error state ────────────────────────────────────────────────────
  const loading = ref(false)
  const error   = ref<string | null>(null)

  function setFilters(newFilters: Partial<ReportFilters>) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function resetFilters() {
    filters.value = { period: 'month' }
  }

  return {
    filters,
    offices,
    officePerformanceData,
    pipelineData,
    complianceData,
    auditData,
    turnaroundData,
    loading,
    error,
    setFilters,
    resetFilters,
  }
})
