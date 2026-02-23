<script setup lang="ts">
import { MdDocumentScanner, MdInfo } from "vue-icons-plus/md";
import { Fa6UserCheck } from "vue-icons-plus/fa6";
import { PiQrCodeFill } from "vue-icons-plus/pi";
import { useRoute } from "vue-router";
import { BiSearch } from "vue-icons-plus/bi";
import { ImHome } from "vue-icons-plus/im";
import { onMounted, ref, watch } from "vue";
import Logo from "@/components/Logo.vue";

const route = useRoute();
const logoPosition = ref<"center" | "top">("center");

watch(
  () => route.name,
  (newName) => {
    setTimeout(() => {
      logoPosition.value = newName === "home" ? "center" : "top";
    }, 50);
  }
);

// Sync on initial load
onMounted(() => {
  logoPosition.value = route.name === "home" ? "center" : "top";
});
</script>
<template>
  <div
    class="absolute -bottom-[50vh] mb-[5vh] sm:mb-[3vh] flex items-center rounded-full -z-10 opacity-99 tilt-orbit justxify-center animate-spin-slow beat w-[100vw] h-[50vh] bg-gradient-to-tr from-teal-900 to-green-700"
  >
    <!-- 💡 Menu Cards Grid -->
    <div class="relative w-[100vw] h-[50vh] mx-auto">
      <!-- Spinning Ring outer outester -->
      <div
        class="absolute tilt-orbit border-4 border-green-600 border-dashed rounded-full blur-[2px] -inset-72 animate-spin-slow beat opacity-30"
      ></div>

      <!-- Spinning Ring outer outester -->
      <div
        class="absolute border-green-600 rounded-full opacity-50 tilt-orbit border-3 custom-dash -inset-52 animate-spin-slow"
      ></div>

      <!-- Spinning Ring outer outest -->
      <div
        class="absolute border-green-600 border-dashed rounded-full tilt-orbit border-1 custom-dash -inset-28 animate-spin-slow beat opacity-90"
      ></div>

      <!-- Spinning Ring outer -->
      <div
        class="absolute border-green-600 border-dashed rounded-full tilt-orbit border-1 custom-dash -inset-15 animate-spin-slow opacity-90"
      ></div>

      <!-- Spinning Ring -->
      <div
        class="absolute border-green-600 border-dashed rounded-full tilt-orbit border-1 custom-dash -inset-4 animate-spin-slow beat opacity-90"
      ></div>

      <!-- 🏠 Home -->
      <div class="absolute top-[10%] left-[5%]">
        <RouterLink :to="{ name: 'home' }">
          <div
            :class="
              route.name === 'home'
                ? ' text-white isActive bg-lime-500 shadow-[0_0_20px_#eab308] border border-lime-400'
                : 'text-lime-600 bg-white shadow-lg'
            "
            class="p-4 text-center border border-gray-200 rounded-full w-fit"
          >
            <ImHome class="mx-auto size-5" />
            <!-- <div class="font-semibold">Home</div>
          <p class="text-xs text-lime-600">Landing Page</p> -->
          </div>
        </RouterLink>
      </div>

      <!-- 👤 Login -->
      <div class="absolute -top-[5%] left-[28%]">
        <RouterLink :to="{ name: 'login' }">
          <div
            :class="
            ['login','recover-password'].includes(route.name as string)
              ? ' text-white isActive bg-yellow-400 border-2 border-yellow-300 shadow-[0_0_20px_#eab308]'
              : 'text-yellow-500 bg-white shadow-lg'
          "
            class="p-4 text-center rounded-full w-fit"
          >
            <Fa6UserCheck class="mx-auto size-6" />
            <!-- <div class="font-semibold">Login</div>
          <p class="text-xs text-gray-500">Sign in here</p> -->
          </div>
        </RouterLink>
      </div>

      <!-- 📄 Track Document -->
      <div class="absolute -top-[5%] right-[28%]">
        <RouterLink :to="{ name: 'track-document' }">
          <div
            :class="
              route.name === 'track-document'
                  ? ' text-white isActive bg-amber-500 border-2 border-amber-400 shadow-[0_0_20px_#d97706]'
                  : 'text-amber-500 bg-white shadow-lg'
            "
            class="p-4 text-center rounded-full w-fit"
          >
            <BiSearch class="w-6 h-6 mx-auto" />
            <!-- <div class="font-semibold">Track</div>
          <p class="text-xs text-gray-500">Search by Doc No.</p> -->
          </div>
        </RouterLink>
      </div>

      <!-- ℹ️ About -->
      <div class="absolute top-[10%] right-[5%]">
        <RouterLink :to="{ name: 'about' }">
          <div
            :class="
              route.name === 'about'
                  ? ' text-white isActive bg-teal-700 border-2 border-teal-700 shadow-[0_0_20px_#eab308]'
                  : 'text-teal-700 bg-white shadow-lg'
            "
            class="flex flex-col items-center p-4 text-center border border-gray-200 rounded-full w-fit"
          >
            <MdInfo class="size-6" />
            <!-- <div class="font-semibold">About</div>
          <p class="text-xs text-center text-gray-500">Know more</p> -->
          </div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
