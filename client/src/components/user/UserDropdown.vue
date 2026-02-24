<script setup lang="ts">
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";

const open = ref(false);
const router = useRouter();
const auth = useAuthStore();

// ── FIX: use computed so it stays reactive to store changes after refresh ──
// Plain object assignment at setup time captures null if fetchUser hasn't resolved yet.
const user = computed(() => ({
  name: auth.user?.first_name || "—",
  email: auth.user?.email || "—",
  office: auth.user?.office_name || "—",
}))

function toggleMenu() {
  open.value = !open.value;
}

function closeDropdown() {
  open.value = false;
}

function logout() {
  auth.logout();
  router.push("/login");
}
</script>

<template>
  <div class="relative inline-block text-left" v-outside-click="closeDropdown">
    <!-- Trigger -->
    <button @click="toggleMenu"
      class="flex items-start px-3 py-2 text-xs font-light text-gray-700 transition-all duration-200 border border-gray-100 rounded-full focus:outline-white focus:outline-2 focus:bg-white hover:bg-white hover:border hover:border-gray-200">
      <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
          class="sm:mr-2 size-5">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <div class="flex flex-col items-start ml-1">
          <div class="hidden text-xs sm:block">{{ user.name }}</div>
        </div>
      </div>
    </button>

    <!-- Dropdown Menu -->
    <transition name="fade">
      <div v-if="open" class="absolute right-0 z-50 w-48 mt-2 bg-white border border-gray-200 rounded-md shadow-xl">
        <div>
          <div class="px-4 py-3 border-b border-gray-200">
            <p class="text-xs text-gray-500">Assigned Office</p>
            <p class="text-sm font-medium text-gray-800">{{ user.office }}</p>
          </div>
        </div>

        <ul class="py-1 text-sm text-gray-700">
          <li>
            <RouterLink :to="{ name: 'user-profile' }" class="block px-4 py-2 hover:bg-gray-100">
              Profile
            </RouterLink>
          </li>
          <li>
            <RouterLink :to="{ name: 'user-settings' }" class="block px-4 py-2 hover:bg-gray-100">
              Settings
            </RouterLink>
          </li>
          <li>
            <button @click="logout" class="w-full px-4 py-2 text-left text-red-500 hover:bg-red-50">
              Logout
            </button>
          </li>
        </ul>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 150ms ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>