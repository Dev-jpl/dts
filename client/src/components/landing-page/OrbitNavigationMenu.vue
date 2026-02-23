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
    class="relative flex items-center rounded-full w-60 h-60 -z-10 opacity-99 tilt-orbit justxify-center animate-spin-slow beat sm:w-80 sm:h-80 bg-gradient-to-tr from-teal-900 to-green-700"
  >
    <div class="mx-auto animate-spin-slow">
      <svg
        width="250"
        height="250"
        viewBox="0 0 200 200"
        class="mx-auto my-auto text-lime-500"
      >
        <circle
          cx="100"
          cy="100"
          r="90"
          fill="none"
          stroke="currentColor"
          stroke-width="1"
          stroke-dasharray="5, 10"
          stroke-linecap="round"
        />
      </svg>
    </div>
    <!-- Spinning Ring outer outestest -->
    <!-- <div
          class="absolute border-green-600 border-dashed rounded-full border-5 -inset-96 animate-spin-slow beat blur-3xl opacity-10"
        ></div> -->

    <!-- Spinning Ring outer outestest -->
    <!-- <div
          class="absolute border-2 border-green-600 border-dashed rounded-full -inset-72 animate-spin-slow beat blur-3xl opacity-10"
        ></div> -->

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

    <!-- Objects -->

    <!-- Floating Card near orbit -->

    <!--Start Home Card -->
    <div
      :class="
        route.name === 'home' ? 'isActive  shadow-[0_0_20px_#eab308]' : ''
      "
      class="absolute bg-white focus:outline-2 focus:outline-teal-300 transition-all duration-150 ease-in-out tilt -top-[11%] left-[5%] rounded-2xl shadow-2xl px-4 py-2 text-sm border border-gray-200"
    >
      <RouterLink :to="{ name: 'home' }">
        <div class="font-semibold text-teal-800 whitespace-nowrap">Home</div>
        <p class="text-xs text-lime-600">Landing Page</p>
      </RouterLink>
    </div>
    <div
      :class="
        route.name === 'home'
          ? ' text-white isActive bg-lime-500 shadow-[0_0_20px_#eab308] border border-lime-400'
          : 'text-lime-600 bg-white shadow-lg'
      "
      class="absolute top-[8%] left-[35%] w-10 h-10 rounded-full flex items-center justify-center"
    >
      <ImHome class="w-5 h-5" />
    </div>
    <!--End Home Card -->

    <!--Start User Login Card -->
    <div
      :class="
            ['login','recover'].includes(route.name as string) ? 'isActive  shadow-[0_0_20px_#eab308]' : ''
          "
      class="absolute bg-white focus:outline-2 focus:outline-teal-300 transition-all duration-150 ease-in-out tilt top-[18%] -right-[20%] rounded-2xl shadow-2xl px-4 py-2 text-sm border border-gray-200"
    >
      <RouterLink :to="{ name: 'login' }">
        <div class="font-semibold text-yellow-600 whitespace-nowrap">
          User Login
        </div>
        <p class="text-xs text-gray-500">Sign in here</p>
      </RouterLink>
    </div>
    <div
      :class="
            ['login','recover'].includes(route.name as string)
              ? ' text-white isActive bg-yellow-400 border-2 border-yellow-300 shadow-[0_0_20px_#eab308]'
              : 'text-yellow-500 bg-white'
          "
      class="absolute top-[35%] right-[10%] w-10 h-10 rounded-full flex items-center justify-center"
    >
      <Fa6UserCheck class="w-5 h-5" />
    </div>
    <!--End User Login Card -->

    <!-- Start Track Document Card -->
    <div
      class="absolute transition-all duration-300 ease-in-out top-[60%] right-[75%]"
    >
      <RouterLink :to="{ name: 'track' }">
        <div class="relative">
          <div
            :class="[
              'absolute -top-[50px] -left-[180px] transition-all ease-in-out  tilt  bg-white rounded-2xl px-3 py-2 shadow text-sm duration-400',
              route.name === 'track'
                ? 'isActive bg-white shadow-[0_0_20px_#d97706] border border-amber-300'
                : '',
            ]"
          >
            <div class="flex items-start">
              <PiQrCodeFill class="inline w-4 h-4 mt-0.5 mr-1 text-amber-600" />
              <div
                class="p-0 mt-0 transition-all duration-300 ease-in-out text-nowrap"
              >
                Track My Document
                <p class="text-xs text-gray-500">Search by Document No.</p>
              </div>
            </div>
          </div>
        </div>
        <div
          :class="
            route.name === 'track'
              ? ' text-white isActive bg-amber-500 shadow-[0_0_20px_#d97706] border border-amber-300'
              : 'text-amber-600 bg-white shadow-lg'
          "
          class="flex items-center justify-center w-10 h-10 transition-all duration-300 ease-in-out rounded-full"
        >
          <BiSearch class="w-6 h-6" />
        </div>
      </RouterLink>
    </div>
    <!-- End Track Document Card -->

    <!-- Start About Card  -->
    <div
      :class="
        route.name === 'about'
          ? 'isActive text-green-700  shadow-[0_0_20px_#65a30d] rounded-full'
          : 'text-gray-500'
      "
      class="absolute bottom-10 -right-[30%] tilt"
    >
      <RouterLink :to="{ name: 'about' }">
        <div
          class="flex items-center justify-center p-2 bg-white rounded-full shadow-lg"
        >
          <MdInfo class="mr-2 size-7" />
          <div class="flex flex-col justify-start">
            <div class="text-xs font-semibold whitespace-nowrap">About</div>
            <p class="text-xs text-gray-500">Know more about our system</p>
          </div>
        </div>
      </RouterLink>
    </div>
    <!-- End About Card -->
  </div>
</template>
