<script setup lang="ts">
import { RouterView } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import { useAuthStore } from "./stores/auth";

const auth = useAuthStore();

// Restore session if token exists
if (auth.token) {
  auth.fetchUser()
}
</script>

<template>
  <header>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  </header>
  <!-- Show DefaultLayout if logged in, otherwise show public routes -->
  <component :is="auth.token ? DefaultLayout : RouterView" />
</template>
