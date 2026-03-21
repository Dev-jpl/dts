<template>
  <div class="flex-1 w-full h-full bg-white">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">Signatory Management</h1>
        <p class="mt-1 text-xs text-gray-500">Manage authorized signatories for document routing and approvals.</p>
      </div>
      <button class="px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800">
        Add Signatory
      </button>
    </div>
    <hr class="text-gray-200" />

    <!-- Search & Filters -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search signatories..."
        class="w-64 px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
      />
      <select
        v-model="filterOffice"
        class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
      >
        <option value="">All Offices</option>
      </select>
    </div>

    <!-- Content Area -->
    <div class="p-4">
      <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Position</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Office</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr class="text-center">
              <td colspan="5" class="px-4 py-8 text-xs text-gray-500">
                Signatories will be loaded from the API
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const searchQuery = ref('')
const filterOffice = ref('')
</script>
