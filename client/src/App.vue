<script setup lang="ts">
import { ref, watch } from "vue";
import { RouterView } from "vue-router";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
import { useAuthStore } from "./stores/auth";
import ToastNotification from "./components/ui/toasts/ToastNotification.vue";


const auth = useAuthStore();
const ready = ref(false);

// Debug logging
console.log('🔄 App.vue: Initial state', { token: !!auth.token, ready: ready.value })
watch(() => auth.token, (newToken) => {
  console.log('🔄 App.vue: Token changed', { token: !!newToken })
})
watch(ready, (newReady) => {
  console.log('🔄 App.vue: Ready changed', { ready: newReady, token: !!auth.token })
})

// ── FIX: await fetchUser so auth.user is populated before anything renders ──
// Without await, components mount with auth.user = null, then update too late
// for static (non-reactive) assignments like plain object destructuring.
if (auth.token) {
  auth.fetchUser().finally(() => {
    ready.value = true;
  });
} else {
  ready.value = true;
}
</script>

<template>
  <header>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  </header>
  <!-- Hold render until user is resolved to avoid flash of null state -->
  <toast-notification />
  <template v-if="ready">
    <component :is="auth.token ? DefaultLayout : RouterView" />
  </template>
</template>