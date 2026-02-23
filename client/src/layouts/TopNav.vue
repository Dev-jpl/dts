<script setup lang="ts">
import BreadCrumbs from "@/components/BreadCrumbs.vue";
import TrackDocumentModal from "@/components/track-document/TrackDocumentModal.vue";
import UserDropdown from "@/components/user/UserDropdown.vue";
import { ref, watchEffect } from "vue";
import { useRoute } from "vue-router";

const router = useRoute();
const currentRouteLabel = ref("");

watchEffect(() => {
  currentRouteLabel.value =
    (router.meta?.label as string) || router.name?.toString() || "";
});

const userName = "John Doe Nut";


const trackModalOpen = ref(false);

const toggleTrackModal = () => {
  trackModalOpen.value = !trackModalOpen.value;
};
</script>

<template>
  <header class="w-full px-4 py-1.5 h-[50px] bg-gray-100 border-gray-200 shrink-0">
    <div class="container flex items-center justify-between mx-auto">
      <div class="flex items-center space-x-2">
        <!-- <button class="mr-t4">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="size-5"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
          />
        </svg>
      </button> -->
        <BreadCrumbs />
        <!-- <span class="text-gray-600 text-md"> {{ currentRouteLabel }}</span> -->
      </div>
      <TrackDocumentModal :is-open="trackModalOpen" title="Track Your Document" @close="toggleTrackModal" />
      <div class="md:block">
        <div class="flex items-center">
          <!-- Track -->
          <div @click="toggleTrackModal"
            class="flex items-center px-3 py-2 rounded-full hover:bg-white hover:cursor-pointer">
            <button type="button" class="mr-5 text-xs italic text-gray-400 sm:block">
              Track a document...
            </button>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="size-5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
          </div>

          <!-- Notifications -->
          <div
            class="flex items-center px-2 py-2 text-xs font-light text-gray-700 transition-all duration-200 border border-gray-100 rounded-full focus:outline-white focus:outline-2 focus:bg-white hover:bg-white hover:border hover:border-gray-200 dark:text-gray-400 dark:hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="size-5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
          </div>

          <!-- Message -->
          <div
            class="flex items-center px-2 py-2 text-xs font-light text-gray-700 transition-all duration-200 border border-gray-100 rounded-full focus:outline-white focus:outline-2 focus:bg-white hover:bg-white hover:border hover:border-gray-200 dark:text-gray-400 dark:hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="size-5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
            </svg>
          </div>

          <!-- User -->
          <UserDropdown />
        </div>
      </div>
    </div>
  </header>
</template>
