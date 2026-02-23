
import ForReleasingView from '@/views/ForReleasingView.vue'
import IncomingDocumentsView from '@/views/IncomingDocumentsView.vue'
import MyDocumentsView from '@/views/MyDocumentsView.vue'
import { createRouter, createWebHistory } from 'vue-router'
import DocumentsReceivedView from '@/views/DocumentsReceivedView.vue'
import DocumentsReleasedView from '@/views/DocumentsReleasedView.vue'
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
      path: '/transactions',
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
        label: 'Admin'
      },
      component: AdminView,
    },
    {
      path: '/templates',
      name: 'templates',
      meta: {
        label: 'Templates'
      },
      component: TemplatesView,
    },
    {
      path: '/view-document/:trxNo',
      name: 'view-document',
      meta: {
        label: 'View Document'
      },
      component: () => import('../views/ViewDocument.vue'),
    },

  ],
})

export default router
