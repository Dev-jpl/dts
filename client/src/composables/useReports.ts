import API from '@/api'
import { useReportsStore, type ReportFilters } from '@/stores/reports'
import { storeToRefs } from 'pinia'

// ── Helpers ────────────────────────────────────────────────────────────────────

function buildParams(filters: ReportFilters, extra: Record<string, any> = {}) {
  const params: Record<string, any> = { period: filters.period }

  if (filters.period === 'custom') {
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to)   params.date_to   = filters.date_to
  }

  if (filters.office_ids?.length) {
    params.office_ids = filters.office_ids.join(',')
  }

  if (filters.status) params.status = filters.status

  return { ...params, ...extra }
}

// ── Composable ─────────────────────────────────────────────────────────────────

export function useReports() {
  const store = useReportsStore()
  const { filters, offices, loading, error,
          officePerformanceData, pipelineData,
          complianceData, auditData, turnaroundData } = storeToRefs(store)

  // ── Office Performance ───────────────────────────────────────────────────────
  async function fetchOfficePerformance() {
    loading.value = true
    error.value   = null
    try {
      const res = await API.get('/reports/office-performance', {
        params: buildParams(filters.value),
      })
      officePerformanceData.value = res.data
      if (res.data.offices) offices.value = res.data.offices
    } catch (e: any) {
      error.value = e.response?.data?.message ?? e.message ?? 'Failed to load report'
    } finally {
      loading.value = false
    }
  }

  async function exportOfficePerformance(format: 'pdf' | 'xlsx') {
    const params = buildParams(filters.value, { format })
    const res = await API.get('/reports/office-performance/export', {
      params,
      responseType: 'blob',
    })
    triggerDownload(res, `office-performance.${format}`)
  }

  // ── Pipeline ─────────────────────────────────────────────────────────────────
  async function fetchPipeline() {
    loading.value = true
    error.value   = null
    try {
      const res = await API.get('/reports/pipeline', {
        params: buildParams(filters.value),
      })
      pipelineData.value = res.data
      if (res.data.offices) offices.value = res.data.offices
    } catch (e: any) {
      error.value = e.response?.data?.message ?? e.message ?? 'Failed to load report'
    } finally {
      loading.value = false
    }
  }

  async function exportPipeline(format: 'pdf' | 'xlsx') {
    const params = buildParams(filters.value, { format })
    const res = await API.get('/reports/pipeline/export', {
      params,
      responseType: 'blob',
    })
    triggerDownload(res, `pipeline.${format}`)
  }

  // ── Compliance ────────────────────────────────────────────────────────────────
  async function fetchCompliance() {
    loading.value = true
    error.value   = null
    try {
      const res = await API.get('/reports/compliance', {
        params: buildParams(filters.value),
      })
      complianceData.value = res.data
    } catch (e: any) {
      error.value = e.response?.data?.message ?? e.message ?? 'Failed to load report'
    } finally {
      loading.value = false
    }
  }

  async function exportCompliance(format: 'pdf' | 'xlsx') {
    const params = buildParams(filters.value, { format })
    const res = await API.get('/reports/compliance/export', {
      params,
      responseType: 'blob',
    })
    triggerDownload(res, `compliance.${format}`)
  }

  // ── Audit ────────────────────────────────────────────────────────────────────
  async function fetchAudit(docNo: string) {
    loading.value = true
    error.value   = null
    try {
      const res = await API.get(`/reports/audit/${docNo}`)
      auditData.value = res.data
    } catch (e: any) {
      error.value = e.response?.data?.message ?? e.message ?? 'Failed to load audit'
    } finally {
      loading.value = false
    }
  }

  async function exportAudit(docNo: string) {
    // PDF ONLY — rule #29
    const res = await API.get(`/reports/audit/${docNo}/export`, {
      responseType: 'blob',
    })
    triggerDownload(res, `audit-${docNo}.pdf`)
  }

  // ── Turnaround ────────────────────────────────────────────────────────────────
  async function fetchTurnaround() {
    loading.value = true
    error.value   = null
    try {
      const res = await API.get('/reports/turnaround', {
        params: buildParams(filters.value),
      })
      turnaroundData.value = res.data
      if (res.data.offices) offices.value = res.data.offices
    } catch (e: any) {
      error.value = e.response?.data?.message ?? e.message ?? 'Failed to load report'
    } finally {
      loading.value = false
    }
  }

  async function exportTurnaround(format: 'pdf' | 'xlsx') {
    const params = buildParams(filters.value, { format })
    const res = await API.get('/reports/turnaround/export', {
      params,
      responseType: 'blob',
    })
    triggerDownload(res, `turnaround.${format}`)
  }

  return {
    filters,
    offices,
    loading,
    error,
    officePerformanceData,
    pipelineData,
    complianceData,
    auditData,
    turnaroundData,
    // Office Performance
    fetchOfficePerformance,
    exportOfficePerformance,
    // Pipeline
    fetchPipeline,
    exportPipeline,
    // Compliance
    fetchCompliance,
    exportCompliance,
    // Audit
    fetchAudit,
    exportAudit,
    // Turnaround
    fetchTurnaround,
    exportTurnaround,
    // Store helpers
    setFilters:   store.setFilters,
    resetFilters: store.resetFilters,
  }
}

// ── Download helper ────────────────────────────────────────────────────────────
function triggerDownload(res: any, filename: string) {
  const url  = URL.createObjectURL(new Blob([res.data]))
  const link = document.createElement('a')
  link.href  = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
