<script setup lang="ts">
import { onUnmounted, ref } from "vue";
import { useRouter } from "vue-router";
import { MdSearch } from "vue-icons-plus/md";
import { IoArrowForward } from "vue-icons-plus/io";

const router = useRouter();

const trackingNumber = ref("");
const trackError = ref("");

function trackDocument() {
  if (!trackingNumber.value) {
    trackError.value = "Please enter a document number.";
    return;
  }
  router.push(`/track/${trackingNumber.value}`);
}
console.log("✅ TrackDocumentView mounted");
onUnmounted(() => {
  console.log("🧹 TrackDocumentView unmounted");
});
</script>

<template>
  <!-- 🔍 Tracker Form -->
  <div class="w-full max-w-md p-8">
    <h1
      class="flex items-center justify-center gap-2 text-2xl font-bold text-center text-teal-800"
    >
      <!-- <MdDocumentScanner class="w-6 h-6 text-teal-800" /> -->
      Track Your Document
    </h1>
    <p class="mt-1 text-sm italic text-center text-gray-500">
      Real-time visibility across departments.
    </p>

    <div class="relative w-full mt-10">
      <form @submit.prevent="trackDocument">
        <input
          v-model="trackingNumber"
          type="text"
          placeholder="Search by Document No..."
          class="w-full py-3 pl-10 pr-4 text-sm transition bg-gray-100 border rounded-full shadow-inner border-gray-50 focus:outline-none focus:ring-2 focus:ring-lime-600"
        />
        <!-- Centered Search Icon -->
        <div class="absolute text-gray-400 -translate-y-1/2 left-3 top-1/2">
          <MdSearch class="w-5 h-5" />
        </div>
        <!-- Right-aligned Button with Icon on Left -->
        <button
          type="submit"
          class="absolute flex items-center gap-1 p-2 pl-3 pr-3 text-sm font-semibold text-white transition -translate-y-1/2 rounded-full shadow-2xl right-2 top-1/2 group hover:cursor-pointer bg-lime-600 hover:bg-lime-500"
        >
          <IoArrowForward class="transition-all size-4" />
          <!-- <span>Track</span> -->
        </button>
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
