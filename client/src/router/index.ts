
import ForReleasingView from '@/views/ForReleasingView.vue'
import ReportsHomeView from '@/views/reports/ReportsHomeView.vue'
import IncomingDocumentsView from '@/views/IncomingDocumentsView.vue'
import MyDocumentsView from '@/views/MyDocumentsView.vue'
import { createRouter, createWebHistory } from 'vue-router'
import DocumentsReceivedView from '@/views/DocumentsReceivedView.vue'
import DocumentsReleasedView from '@/views/DocumentsReleasedView.vue'
import DocumentsOutgoingView from '@/views/DocumentsOutgoingView.vue'
import DocumentsArchivedView from '@/views/DocumentsArchivedView.vue'
import AdvanceSearchView from '@/views/AdvanceSearchView.vue'
import AdminView from '@/views/AdminView.vue'
import TemplatesView from '@/views/TemplatesView.vue'
import ProfileDocumentView from '@/views/ProfileDocumentView.vue'
import ReceiveDocumentView from '@/views/ReceiveDocumentView.vue'
import ReleaseDocumentView from '@/views/ReleaseDocumentView.vue'
import DocumentsDraftsView from '@/views/DocumentsDraftsView.vue'
import NewDocumentView from '@/views/NewDocumentView.vue'
import CreateDocumentView from '@/views/CreateDocumentView.vue'
import FastTransactionView from '@/views/FastTransactionView.vue'
import ScheduledTaskView from '@/views/ScheduledTaskView.vue'
import MainDashboardView from '@/views/MainDashboardView.vue'
import LoginView from '@/views/LoginView.vue'
import UserProfileView from '@/views/UserProfileView.vue'
import UserSettingsView from '@/views/UserSettingsView.vue'
import LandingPageView from '@/views/LandingPageView.vue'
import TrackDocumentView from '@/views/TrackDocumentView.vue'
import AboutView from '@/views/AboutView.vue'
import RecoverPasswordView from '@/views/RecoverPasswordView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: LandingPageView,
      meta: {
        label: 'Home',
      },
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('../views/LoginView.vue'),
          meta: {
            label: 'Login',
          },
        },
        {
          path: 'recover',
          name: 'recover',
          component: () => import('../views/RecoverPasswordView.vue'),
          meta: {
            label: 'Recover Password',
          },
        },
        {
          path: 'track',
          name: 'track',
          component: () => import('../views/TrackDocumentView.vue'),
          meta: {
            label: 'Track Document',
          },
        },
        {
          path: 'about',
          name: 'about',
          component: () => import('../views/AboutView.vue'),
          meta: {
            label: 'About',
          },
        },
      ]
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: MainDashboardView,
      meta: {
        label: 'Dashboard',
        requiresAuth: true
      },
    },
    {
      path: '/user',
      children: [
        {
          path: 'user-profile',
          name: 'user-profile',
          component: UserProfileView,
          meta: {
            label: 'User Profile',
            requiresAuth: true,
            breadcrumb: {
              isGroupRoot: true,
              parent: 'user-profile',
              group: 'user-profile',
            }
          },
        },
        {
          path: 'user-settings',
          name: 'user-settings',
          component: UserSettingsView,
          meta: {
            label: 'User Settings',
            requiresAuth: true,
            breadcrumb: {
              parent: 'user-profile',
              group: 'user-profile',
            }
          },
        },
      ]
    },
    {
      path: '/transactions',
      children: [
        {
          path: 'new-document',
          name: 'new-document',
          component: NewDocumentView,
          meta: {
            label: 'New Document',
            requiresAuth: true,
            breadcrumb: {
              isGroupRoot: true,
              group: 'new-document',
            }
          },
        },
        {
          path: 'new-document/profile-document',
          name: 'profile-document',
          component: ProfileDocumentView,
          meta: {
            label: 'Profile Document',
            requiresAuth: true,
            breadcrumb: {
              parent: 'new-document',
              group: 'new-document',
            }
          }
        },
        {
          path: 'new-document/profile-document/reply/:trxNo',
          name: 'profile-document-reply',
          component: ProfileDocumentView, // or a dedicated Reply view if needed
          meta: {
            label: 'Reply to Profile Document',
            requiresAuth: true,
            breadcrumb: {
              parent: 'new-document',
              group: 'new-document',
            }
          }
        },
        {
          path: 'new-document/create-document',
          name: 'create-document',
          component: CreateDocumentView,
          meta: {
            label: 'Create Document',
            requiresAuth: true,
            breadcrumb: {
              parent: 'new-document',
              group: 'new-document',
            }
          }
        },
        {
          path: 'new-document/fast-transaction',
          name: 'fast-transaction',
          component: FastTransactionView,
          meta: {
            label: 'Fast Transaction',
            requiresAuth: true,
            breadcrumb: {
              parent: 'new-document',
              group: 'new-document',
            }
          }
        },
        {
          path: 'new-document/scheduled-task',
          name: 'scheduled-task',
          component: ScheduledTaskView,
          meta: {
            label: 'Scheduled Task',
            requiresAuth: true,
            breadcrumb: {
              parent: 'new-document',
              group: 'new-document',
            }
          }
        },
        {
          path: 'received-document',
          name: 'received-document',
          meta: {
            label: 'Received Document',
            requiresAuth: true,
          },
          component: ReceiveDocumentView,
        },
        {
          path: 'release-document',
          name: 'release-document',
          meta: {
            label: 'Release Document',
            requiresAuth: true,
          },
          component: ReleaseDocumentView,
        },
      ]
    },
    {
      path: '/incoming-documents',
      name: 'incoming-documents',
      meta: {
        label: 'Incoming Documents',
        requiresAuth: true,
      },
      component: IncomingDocumentsView,
    },
    {
      path: '/for-releasing',
      name: 'for-releasing',
      meta: {
        label: 'For Releasing',
        requiresAuth: true,
      },
      component: ForReleasingView,
    },
    {
      path: '/documents',
      name: 'documents',
      children: [
        {
          path: '',
          name: 'my-documents',
          component: MyDocumentsView,
          meta: {
            label: 'My Documents',
            requiresAuth: true,
            breadcrumb: {
              // isGroupRoot: true,
              parent: 'document',
              group: 'documents',
            }
          },
        },
        {
          path: 'received',
          name: 'documents-received',
          component: DocumentsReceivedView,
          meta: {
            label: 'Received Documents',
            requiresAuth: true,
            breadcrumb: {
              parent: 'document',
              group: 'documents',
            }
          },
        },
        {
          path: 'released',
          name: 'documents-released',
          component: DocumentsReleasedView,
          meta: {
            label: 'Released Documents',
            requiresAuth: true,
            breadcrumb: {
              parent: 'document',
              group: 'documents',
            }
          },
        },
        {
          path: 'outgoing',
          name: 'documents-outgoing',
          component: DocumentsOutgoingView,
          meta: {
            label: 'Outgoing Documents',
            requiresAuth: true,
            breadcrumb: {
              parent: 'document',
              group: 'documents',
            }
          },
        },
        {
          path: 'archived',
          name: 'documents-archived',
          component: DocumentsArchivedView,
          meta: {
            label: 'Archived Documents',
            requiresAuth: true,
            breadcrumb: {
              parent: 'document',
              group: 'documents',
            }
          },
        },
        {
          path: 'drafts',
          name: 'documents-drafts',
          component: DocumentsDraftsView,
          meta: {
            label: 'Drafts Documents',
            requiresAuth: true,
            breadcrumb: {
              parent: 'document',
              group: 'documents',
            }
          },
        },
      ],
    },
    {
      path: '/advance-search',
      name: 'advance-search',
      meta: {
        label: 'Advance Search'
      },
      component: AdvanceSearchView,
    },
    {
      path: '/admin',
      name: 'admin',
      meta: {
        label: 'Admin',
        requiresAuth: true,
      },
      redirect: { name: 'admin-library' },
    },
    {
      path: '/admin/library',
      name: 'admin-library',
      meta: {
        label: 'Library Management',
        requiresAuth: true,
      },
      component: () => import('../views/admin/LibraryManagementView.vue'),
    },
    {
      path: '/admin/signatories',
      name: 'admin-signatories',
      meta: {
        label: 'Signatory Management',
        requiresAuth: true,
      },
      component: () => import('../views/admin/SignatoryManagementView.vue'),
    },
    {
      path: '/admin/users',
      name: 'admin-users',
      meta: {
        label: 'User Management',
        requiresAuth: true,
      },
      component: () => import('../views/admin/UserManagementView.vue'),
    },
    {
      path: '/admin/settings',
      name: 'admin-settings',
      meta: {
        label: 'System Settings',
        requiresAuth: true,
      },
      component: () => import('../views/admin/SystemSettingsView.vue'),
    },
    {
      path: '/templates',
      name: 'my-templates',
      meta: {
        label: 'My Templates',
        requiresAuth: true,
      },
      component: TemplatesView,
    },
    {
      path: '/templates/create',
      name: 'template-create',
      meta: {
        label: 'Create Template',
        requiresAuth: true,
      },
      component: () => import('../views/TemplateCreateView.vue'),
    },
    {
      path: '/templates/:id/edit',
      name: 'template-edit',
      meta: {
        label: 'Edit Template',
        requiresAuth: true,
      },
      component: () => import('../views/TemplateEditView.vue'),
    },
    {
      path: '/view-document/:trxNo',
      name: 'view-document',
      meta: {
        label: 'View Document'
      },
      component: () => import('../views/ViewDocument.vue'),
    },

    // ── Files management module ─────────────────────────────────────────────────
    {
      path: '/files',
      name: 'files-management',
      component: () => import('../views/FilesManagementView.vue'),
      meta: { label: 'Files', requiresAuth: true },
    },

    // ── Reports module ────────────────────────────────────────────────────────
    {
      path: '/reports',
      name: 'reports',
      component: ReportsHomeView,
      meta: { label: 'Reports', requiresAuth: true },
    },
    {
      path: '/reports/office-performance',
      name: 'reports-office-performance',
      component: () => import('../views/reports/OfficePerformanceView.vue'),
      meta: { label: 'Office Performance', requiresAuth: true },
    },
    {
      path: '/reports/pipeline',
      name: 'reports-pipeline',
      component: () => import('../views/reports/PipelineView.vue'),
      meta: { label: 'Document Pipeline', requiresAuth: true },
    },
    {
      path: '/reports/compliance',
      name: 'reports-compliance',
      component: () => import('../views/reports/ComplianceView.vue'),
      meta: { label: 'ISO Compliance', requiresAuth: true },
    },
    {
      path: '/reports/audit',
      name: 'reports-audit',
      component: () => import('../views/reports/AuditView.vue'),
      meta: { label: 'Transaction Audit', requiresAuth: true },
    },
    {
      path: '/reports/turnaround',
      name: 'reports-turnaround',
      component: () => import('../views/reports/TurnaroundView.vue'),
      meta: { label: 'Turnaround', requiresAuth: true },
    },

    // ── Settings module ────────────────────────────────────────────────────────
    {
      path: '/settings',
      name: 'settings',
      component: () => import('../views/settings/SettingsLayout.vue'),
      meta: { label: 'Settings', requiresAuth: true },
      redirect: '/settings/profile',
      children: [
        {
          path: 'profile',
          name: 'settings-profile',
          component: () => import('../views/settings/ProfileView.vue'),
          meta: { label: 'Profile', requiresAuth: true },
        },
        {
          path: 'password',
          name: 'settings-password',
          component: () => import('../views/settings/PasswordView.vue'),
          meta: { label: 'Password', requiresAuth: true },
        },
        {
          path: 'preferences',
          name: 'settings-preferences',
          component: () => import('../views/settings/PreferencesView.vue'),
          meta: { label: 'Preferences', requiresAuth: true },
        },
        {
          path: 'notifications',
          name: 'settings-notifications',
          component: () => import('../views/settings/NotificationPrefsView.vue'),
          meta: { label: 'Notifications', requiresAuth: true },
        },
        {
          path: 'sessions',
          name: 'settings-sessions',
          component: () => import('../views/settings/SessionsView.vue'),
          meta: { label: 'Sessions', requiresAuth: true },
        },
        {
          path: 'office',
          name: 'settings-office',
          component: () => import('../views/settings/OfficeProfileView.vue'),
          meta: { label: 'Office Profile', requiresAuth: true },
        },
        {
          path: 'office/members',
          name: 'settings-office-members',
          component: () => import('../views/settings/OfficeMembersView.vue'),
          meta: { label: 'Office Members', requiresAuth: true },
        },
        {
          path: 'office/defaults',
          name: 'settings-office-defaults',
          component: () => import('../views/settings/OfficeDefaultsView.vue'),
          meta: { label: 'Office Defaults', requiresAuth: true },
        },
      ],
    },


  ],
})

export default router
