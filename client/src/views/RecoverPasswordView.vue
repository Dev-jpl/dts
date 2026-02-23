<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { StrapiAuthService } from "@/services/StrapiAuthService";

import {
  MdSearch,
  MdDocumentScanner,
  MdDescription,
  MdLocationOn,
  MdCheckCircle,
  MdInfo,
} from "vue-icons-plus/md";
import { IoArrowForward } from "vue-icons-plus/io";
import { Fa6UserCheck, Fa6UserLock } from "vue-icons-plus/fa6";
import { PiQrCodeFill, PiUserCircleCheckFill } from "vue-icons-plus/pi";
import { BiInfoCircle, BiSearch } from "vue-icons-plus/bi";
import { FiLock, FiMail } from "vue-icons-plus/fi";

const identifier = ref("");
const password = ref("");
const error = ref("");
const router = useRouter();

async function login() {
  error.value = "";

  try {
    const { jwt, user } = await StrapiAuthService.login({
      identifier: identifier.value,
      password: password.value,
    });

    localStorage.setItem("strapi_jwt", jwt);
    console.log("Welcome,", user.username);
    window.location.href = "/";
  } catch (err) {
    error.value = "Login failed. Please check your credentials.";
  }
}

const trackingNumber = ref("");
const trackError = ref("");

function trackDocument() {
  if (!trackingNumber.value) {
    trackError.value = "Please enter a document number.";
    return;
  }
  router.push(`/track/${trackingNumber.value}`);
}
</script>

<template>
  <!-- 🔍 Tracker Form -->
  <div class="w-full max-w-md p-8">
    <h1
      class="flex items-center justify-center gap-2 text-2xl font-bold text-center text-teal-800"
    >
      <!-- <MdDocumentScanner class="w-6 h-6 text-teal-800" /> -->
      Recover Password
    </h1>
    <p class="mt-1 text-sm italic text-center text-gray-500">
      Enter Email to receive recovery link
    </p>

    <div class="relative w-full mt-10">
      <form
        @submit.prevent="login"
        class="space-y-4"
      >
        <div class="relative w-full mt-10">
          <input
            v-model="identifier"
            type="email"
            placeholder="Email"
            class="w-full py-3 pl-10 pr-4 text-sm transition bg-gray-100 border rounded-full shadow-inner border-gray-50 focus:outline-none focus:ring-2 focus:ring-lime-600"
          />
          <!-- Centered Search Icon -->
          <div class="absolute text-gray-400 -translate-y-1/2 left-3 top-1/2">
            <FiMail class="w-5 h-5" />
          </div>
        </div>
        <button
          type="submit"
          class="w-full py-2 font-semibold text-white transition-colors rounded-full hover:cursor-pointer bg-gradient-to-r from-teal-900 to-green-800 hover:[filter:brightness(85%)"
        >
          Submit
        </button>
        <p class="text-xs text-center text-gray-400">
         Back to
          <RouterLink
            :to="{ name: 'login' }"
            class="text-lime-600 hover:underline"
            >Login</RouterLink
          >
        </p>
      </form>
    </div>
    <p
      v-if="trackError"
      class="text-sm text-center text-red-500"
    >
      {{ trackError }}
    </p>
  </div>
</template>
