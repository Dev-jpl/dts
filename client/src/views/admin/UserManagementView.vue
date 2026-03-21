<template>
  <div class="flex-1 w-full h-full bg-white">
    <div class="flex items-center justify-between p-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-800">User Management</h1>
        <p class="mt-1 text-xs text-gray-500">Manage system users, roles, and access permissions.</p>
      </div>
      <button class="px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800">
        Add User
      </button>
    </div>
    <hr class="text-gray-200" />

    <!-- Search & Filters -->
    <div class="flex items-center gap-3 p-4 border-b border-gray-200">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search users..."
        class="w-64 px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
      />
      <select
        v-model="filterRole"
        class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
      >
        <option value="">All Roles</option>
        <option value="admin">Admin</option>
        <option value="superior">Superior</option>
        <option value="regular">Regular User</option>
      </select>
      <select
        v-model="filterOffice"
        class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
      >
        <option value="">All Offices</option>
      </select>
      <select
        v-model="filterStatus"
        class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
      >
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <!-- Content Area -->
    <div class="p-4">
      <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">User</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Email</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Office</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Role</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr class="text-center">
              <td colspan="6" class="px-4 py-8 text-xs text-gray-500">
                Users will be loaded from the API
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
const filterRole = ref('')
const filterOffice = ref('')
const filterStatus = ref('')
</script>
