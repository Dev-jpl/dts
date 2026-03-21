import { ref, reactive, computed } from 'vue'
import API from '@/api'

// ─────────────────────────────────────────────────────────────────────────────
// Types
// ─────────────────────────────────────────────────────────────────────────────

export interface UserProfile {
  id: string
  name: string
  email: string
  office_id: string
  office_name: string
  role: string
  created_at: string
}

export interface UserPreferences {
  date_format: string
  timezone: string
  default_period: 'week' | 'month' | 'quarter' | 'year'
  dashboard_realtime: boolean
}

export interface NotificationPreference {
  event_type: string
  in_app: boolean
  email: boolean
}

export interface Session {
  id: string
  name: string | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
  last_used_at: string | null
  is_current: boolean
}

export interface OfficeMember {
  id: string
  name: string
  email: string
  role: string
  created_at: string
}

export interface OfficeDefaults {
  default_action_type: string | null
  default_document_type: string | null
  default_routing_type: string | null
  default_urgency_level: string | null
}

export interface OfficeProfile {
  id: string
  name: string
  code: string | null
  description: string | null
  parent_office_id: string | null
  parent_office_name: string | null
}

// ─────────────────────────────────────────────────────────────────────────────
// State
// ─────────────────────────────────────────────────────────────────────────────

const loading = ref(false)
const error = ref<string | null>(null)

const profile = ref<UserProfile | null>(null)
const preferences = ref<UserPreferences | null>(null)
const notificationPreferences = ref<NotificationPreference[]>([])
const sessions = ref<Session[]>([])
const office = ref<OfficeProfile | null>(null)
const officeMembers = ref<OfficeMember[]>([])
const officeDefaults = ref<OfficeDefaults | null>(null)

// ─────────────────────────────────────────────────────────────────────────────
// Profile
// ─────────────────────────────────────────────────────────────────────────────

async function fetchProfile(): Promise<UserProfile> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.get('/settings/profile')
    profile.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to fetch profile'
    throw e
  } finally {
    loading.value = false
  }
}

async function updateProfile(payload: { name: string; email: string }): Promise<UserProfile> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.put('/settings/profile', payload)
    profile.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to update profile'
    throw e
  } finally {
    loading.value = false
  }
}

async function updatePassword(payload: {
  current_password: string
  password: string
  password_confirmation: string
}): Promise<void> {
  loading.value = true
  error.value = null
  try {
    await API.put('/settings/password', payload)
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to update password'
    throw e
  } finally {
    loading.value = false
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Preferences
// ─────────────────────────────────────────────────────────────────────────────

async function fetchPreferences(): Promise<UserPreferences> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.get('/settings/preferences')
    preferences.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to fetch preferences'
    throw e
  } finally {
    loading.value = false
  }
}

async function updatePreferences(payload: Partial<UserPreferences>): Promise<UserPreferences> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.put('/settings/preferences', payload)
    preferences.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to update preferences'
    throw e
  } finally {
    loading.value = false
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Notification Preferences
// ─────────────────────────────────────────────────────────────────────────────

async function fetchNotificationPreferences(): Promise<NotificationPreference[]> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.get('/notifications/preferences')
    notificationPreferences.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to fetch notification preferences'
    throw e
  } finally {
    loading.value = false
  }
}

async function updateNotificationPreferences(
  updatedPrefs: NotificationPreference[]
): Promise<NotificationPreference[]> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.put('/notifications/preferences', { preferences: updatedPrefs })
    notificationPreferences.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to update notification preferences'
    throw e
  } finally {
    loading.value = false
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Sessions
// ─────────────────────────────────────────────────────────────────────────────

async function fetchSessions(): Promise<Session[]> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.get('/settings/sessions')
    sessions.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to fetch sessions'
    throw e
  } finally {
    loading.value = false
  }
}

async function revokeSession(sessionId: string): Promise<void> {
  loading.value = true
  error.value = null
  try {
    await API.delete(`/settings/sessions/${sessionId}`)
    sessions.value = sessions.value.filter(s => s.id !== sessionId)
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to revoke session'
    throw e
  } finally {
    loading.value = false
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Office
// ─────────────────────────────────────────────────────────────────────────────

async function fetchOffice(): Promise<OfficeProfile> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.get('/settings/office')
    office.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to fetch office'
    throw e
  } finally {
    loading.value = false
  }
}

async function updateOffice(payload: Partial<OfficeProfile>): Promise<OfficeProfile> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.put('/settings/office', payload)
    office.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to update office'
    throw e
  } finally {
    loading.value = false
  }
}

async function fetchOfficeMembers(): Promise<OfficeMember[]> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.get('/settings/office/members')
    officeMembers.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to fetch office members'
    throw e
  } finally {
    loading.value = false
  }
}

async function fetchOfficeDefaults(): Promise<OfficeDefaults> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.get('/settings/office/defaults')
    officeDefaults.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to fetch office defaults'
    throw e
  } finally {
    loading.value = false
  }
}

async function updateOfficeDefaults(payload: Partial<OfficeDefaults>): Promise<OfficeDefaults> {
  loading.value = true
  error.value = null
  try {
    const { data } = await API.put('/settings/office/defaults', payload)
    officeDefaults.value = data.data
    return data.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to update office defaults'
    throw e
  } finally {
    loading.value = false
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Export
// ─────────────────────────────────────────────────────────────────────────────

export function useSettings() {
  return {
    // State
    loading,
    error,
    profile,
    preferences,
    notificationPreferences,
    sessions,
    office,
    officeMembers,
    officeDefaults,

    // Profile
    fetchProfile,
    updateProfile,
    updatePassword,

    // Preferences
    fetchPreferences,
    updatePreferences,

    // Notification Preferences
    fetchNotificationPreferences,
    updateNotificationPreferences,

    // Sessions
    fetchSessions,
    revokeSession,

    // Office
    fetchOffice,
    updateOffice,
    fetchOfficeMembers,
    fetchOfficeDefaults,
    updateOfficeDefaults,
  }
}
