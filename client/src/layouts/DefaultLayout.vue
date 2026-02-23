<script setup lang="ts">
import { RouterView } from "vue-router";
import SideNav from "./SideNav.vue";
import TopNav from "./TopNav.vue";
</script>

<template>
  <div class="flex w-full h-full">
    <!-- Sidebar -->
    <SideNav />
    <!-- Right section -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <TopNav />
      <main class="flex-1 h-full bg-gray-200">
        <div class="h-full mx-auto">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>
