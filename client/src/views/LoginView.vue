<script setup lang="ts">
import { onUnmounted, ref } from "vue";
import { useRouter } from "vue-router";
import { FiLock, FiMail } from "vue-icons-plus/fi";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const identifier = ref(""); // email
const password = ref("");   // password
const error = ref("");

async function login() {
  error.value = "";

  try {
    // Only call loginUser here
    await authStore.login(identifier.value, password.value);

    // Redirect after successful login
    router.push("/dashboard");
  } catch (err) {
    error.value = "Login failed. Please check your credentials.";
  }
}

console.log("✅ LoginView mounted");
onUnmounted(() => {
  console.log("🧹 LoginView unmounted");
});
</script>

<template>
  <div class="w-full max-w-md p-8">
    <h1 class="flex items-center justify-center text-2xl font-bold text-teal-800">
      <!-- <MdDocumentScanner class="w-6 h-6 text-teal-800" /> -->
      Sign In to Your Account
    </h1>
    <p class="mt-1 text-sm italic text-center text-gray-500">
      Enter your credentials below to access the system
    </p>
    <form @submit.prevent="login" class="space-y-4">
      <div class="relative w-full mt-10">
        <input v-model="identifier" type="email" placeholder="Email"
          class="w-full py-3 pl-10 pr-4 text-sm transition bg-gray-100 border rounded-full shadow-inner border-gray-50 focus:outline-none focus:ring-2 focus:ring-lime-600" />
        <!-- Centered Search Icon -->
        <div class="absolute text-gray-400 -translate-y-1/2 left-3 top-1/2">
          <FiMail class="w-5 h-5" />
        </div>
      </div>
      <div class="relative w-full">
        <input v-model="password" type="password" placeholder="Password"
          class="w-full py-3 pl-10 pr-4 text-sm transition bg-gray-100 border rounded-full shadow-inner border-gray-50 focus:outline-none focus:ring-2 focus:ring-lime-600" />
        <div class="absolute text-gray-400 -translate-y-1/2 left-3 top-1/2">
          <FiLock class="w-5 h-5" />
        </div>
      </div>
      <button type="submit"
        class="w-full py-2 font-semibold text-white transition-colors rounded-full bg-gradient-to-r from-teal-900 to-green-800 hover:cursor-pointer hover:[filter:brightness(85%)]">
        Login
      </button>
      <p class="text-xs text-center text-gray-400">
        Forgot password?
        <RouterLink :to="{ name: 'recover' }" class="text-lime-600 hover:underline">Recover</RouterLink>
      </p>
    </form>
  </div>
</template>
