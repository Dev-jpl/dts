<script setup lang="ts">
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";

const open = ref(false);
const router = useRouter();
const auth = useAuthStore();

const user = {
  name: auth.user?.username || "John Dev",
  email: auth.user?.email || "john@example.com",
  avatarUrl: "", // Add avatar logic if you have one
};
const defaultAvatar = "https://ui-avatars.com/api/?name=John+Dev";

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
  <div
    class="relative inline-block text-left"
    v-outside-click="closeDropdown"
  >
    <!-- Trigger -->
    <button
      @click="toggleMenu"
      class="flex items-center px-3 py-2 text-xs font-light text-gray-700 transition-all duration-200 border border-gray-100 rounded-full focus:outline-white focus:outline-2 focus:bg-white hover:bg-white hover:border hover:border-gray-200 dark:text-gray-400 dark:hover:text-white"
    >
      <div class="flex items-center">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="sm:mr-2 size-5"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
          />
        </svg>
        <span class="hidden text-xs sm:block">{{ user.name }}</span>
      </div>
    </button>

    <!-- Dropdown Menu -->
    <transition name="fade">
      <div
        v-if="open"
        class="absolute right-0 z-50 w-48 mt-2 bg-white border border-gray-200 rounded-md shadow-xl"
      >
        <ul class="py-1 text-sm text-gray-700">
          <li>
            <RouterLink
              :to="{ name: 'user-profile' }"
              class="block px-4 py-2 hover:bg-gray-100"
              >Profile</RouterLink
            >
          </li>
          <li>
            <RouterLink
              :to="{ name: 'user-settings' }"
              class="block px-4 py-2 hover:bg-gray-100"
              >Settings</RouterLink
            >
          </li>
          <li>
            <button
              @click="logout"
              class="w-full px-4 py-2 text-left text-red-500 hover:bg-red-50"
            >
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
