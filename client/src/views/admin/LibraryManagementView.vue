<template>
  <div class="flex-1 w-full h-full bg-white">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Library Management</h1>
        <p class="mt-1 text-xs text-gray-500">Manage document types, action types, offices, and other system libraries.</p>
      </div>
    </div>
    <hr class="text-gray-200" />

    <!-- Tabs -->
    <div class="flex items-center gap-1 px-4 pt-4 border-b border-gray-200">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="[
          'px-4 py-2 text-xs font-medium rounded-t-lg transition-colors',
          activeTab === tab.key
            ? 'bg-teal-700 text-white'
            : 'text-gray-600 hover:bg-gray-100'
        ]"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Content Area -->
    <div class="p-4">
      <!-- Document Types -->
      <div v-if="activeTab === 'document-types'" class="space-y-4">
        <div class="flex items-center justify-between">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search document types..."
            class="w-64 px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          />
          <button class="px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800">
            Add Document Type
          </button>
        </div>
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Code</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <tr class="text-center">
                <td colspan="4" class="px-4 py-8 text-xs text-gray-500">
                  Document types will be loaded from the API
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Action Types -->
      <div v-else-if="activeTab === 'action-types'" class="space-y-4">
        <div class="flex items-center justify-between">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search action types..."
            class="w-64 px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          />
          <button class="px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800">
            Add Action Type
          </button>
        </div>
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <tr class="text-center">
                <td colspan="4" class="px-4 py-8 text-xs text-gray-500">
                  Action types will be loaded from the API
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Offices -->
      <div v-else-if="activeTab === 'offices'" class="space-y-4">
        <div class="flex items-center justify-between">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search offices..."
            class="w-64 px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          />
          <button class="px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800">
            Add Office
          </button>
        </div>
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Office Name</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Code</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Parent Office</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <tr class="text-center">
                <td colspan="5" class="px-4 py-8 text-xs text-gray-500">
                  Offices will be loaded from the API
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Origin Types -->
      <div v-else-if="activeTab === 'origin-types'" class="space-y-4">
        <div class="flex items-center justify-between">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search origin types..."
            class="w-64 px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
          />
          <button class="px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800">
            Add Origin Type
          </button>
        </div>
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <tr class="text-center">
                <td colspan="3" class="px-4 py-8 text-xs text-gray-500">
                  Origin types will be loaded from the API
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const activeTab = ref('document-types')
const searchQuery = ref('')

const tabs = [
  { key: 'document-types', label: 'Document Types' },
  { key: 'action-types', label: 'Action Types' },
  { key: 'offices', label: 'Offices' },
  { key: 'origin-types', label: 'Origin Types' },
]
</script>
