import { ref } from 'vue'
import API from '@/api'

export interface TemplateRecipient {
  id?: number
  template_id?: number
  office_id: string
  office_name: string
  recipient_type: 'default' | 'cc' | 'bcc'
  sequence: number
}

export interface TemplateSignatory {
  id?: number
  template_id?: number
  signatory_id: string | null
  name: string
  position: string | null
  office: string | null
  role: string | null
  sequence: number
}

export interface Template {
  id: number
  name: string
  description: string | null
  scope: 'personal' | 'office' | 'system'
  document_type: string | null
  action_type: string | null
  routing_type: 'Single' | 'Multiple' | 'Sequential' | null
  urgency_level: 'Urgent' | 'High' | 'Normal' | 'Routine'
  origin_type: string | null
  sender: string | null
  sender_position: string | null
  sender_office: string | null
  sender_email: string | null
  remarks_template: string | null
  created_by_id: string
  created_by_name: string
  office_id: string
  isActive: boolean
  use_count: number
  last_used_at: string | null
  recipients: TemplateRecipient[]
  signatories: TemplateSignatory[]
  created_at: string
  updated_at: string
}

export interface TemplateFormData {
  name: string
  description?: string | null
  scope: 'personal' | 'office' | 'system'
  document_type?: string | null
  action_type?: string | null
  routing_type?: 'Single' | 'Multiple' | 'Sequential' | null
  urgency_level?: 'Urgent' | 'High' | 'Normal' | 'Routine'
  origin_type?: string | null
  sender?: string | null
  sender_position?: string | null
  sender_office?: string | null
  sender_email?: string | null
  remarks_template?: string | null
  recipients?: Omit<TemplateRecipient, 'id' | 'template_id'>[]
  signatories?: Omit<TemplateSignatory, 'id' | 'template_id'>[]
}

export function useTemplates() {
  const templates = ref<Template[]>([])
  const currentTemplate = ref<Template | null>(null)
  const isLoading = ref(false)
  const activeScope = ref<'all' | 'personal' | 'office' | 'system'>('all')

  // ── Fetch ────────────────────────────────────────────────────────────────

  async function fetchTemplates(search?: string) {
    isLoading.value = true
    try {
      const params: Record<string, unknown> = { per_page: 50 }
      if (search) params.search = search
      const { data } = await API.get('/templates', { params })
      templates.value = data.data?.data ?? []
    } finally {
      isLoading.value = false
    }
  }

  async function fetchByScope(scope: 'personal' | 'office' | 'system') {
    isLoading.value = true
    try {
      const { data } = await API.get(`/templates/${scope}`, { params: { per_page: 50 } })
      templates.value = data.data?.data ?? []
    } finally {
      isLoading.value = false
    }
  }

  async function fetchTemplate(id: number): Promise<Template> {
    const { data } = await API.get(`/templates/${id}`)
    currentTemplate.value = data.data
    return data.data
  }

  // ── CRUD ─────────────────────────────────────────────────────────────────

  async function createTemplate(formData: TemplateFormData): Promise<Template> {
    const { data } = await API.post('/templates', formData)
    return data.data
  }

  async function updateTemplate(id: number, formData: Partial<TemplateFormData>): Promise<Template> {
    const { data } = await API.put(`/templates/${id}`, formData)
    return data.data
  }

  async function deleteTemplate(id: number): Promise<void> {
    await API.delete(`/templates/${id}`)
    templates.value = templates.value.filter((t) => t.id !== id)
  }

  // ── Actions ───────────────────────────────────────────────────────────────

  async function duplicateTemplate(id: number): Promise<Template> {
    const { data } = await API.post(`/templates/${id}/duplicate`)
    return data.data
  }

  /**
   * Records usage (increments use_count + last_used_at) and returns
   * the full template data for form prefill.
   */
  async function useTemplate(id: number): Promise<Template> {
    const { data } = await API.post(`/templates/${id}/use`)
    return data.data
  }

  async function saveAsTemplate(
    docNo: string,
    payload: { name: string; description?: string | null; scope: 'personal' | 'office' | 'system' },
  ): Promise<Template> {
    const { data } = await API.post(`/documents/${docNo}/save-as-template`, payload)
    return data.data
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  async function refresh() {
    if (activeScope.value === 'all') {
      await fetchTemplates()
    } else {
      await fetchByScope(activeScope.value)
    }
  }

  return {
    templates,
    currentTemplate,
    isLoading,
    activeScope,
    fetchTemplates,
    fetchByScope,
    fetchTemplate,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    duplicateTemplate,
    useTemplate,
    saveAsTemplate,
    refresh,
  }
}
