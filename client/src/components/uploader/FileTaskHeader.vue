<script setup lang="ts">
import { LuPause, LuTrash2 } from 'vue-icons-plus/lu';

const props = defineProps<{
  completedCount: any;
  totalCount: any;
}>();
</script>
<template>
  <div
    class="px-4 py-2 pb-2 border-b border-gray-50 md:px-5 dark:bg-white/10 dark:border-neutral-700"
  >
    <div class="flex flex-wrap items-center justify-between gap-x-3">
      <div class="text-xs font-semibold text-gray-800 dark:text-white">
        Uploaded Files
      </div>
      <div>
        <span class="text-xs font-semibold text-gray-800 dark:text-white">
          {{ completedCount }} / {{ totalCount }} Completed
        </span>
      </div>
      <!-- End Col -->
      <!-- <div class="-me-2.5">
        <button
          type="button"
          class="py-2 px-3 inline-flex items-center gap-x-1.5 text-sm font-medium rounded-lg border border-transparent text-gray-500 hover:bg-gray-200 hover:text-gray-800 focus:outline-hidden focus:bg-gray-200 focus:text-gray-800 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white dark:focus:bg-neutral-800 dark:focus:text-white"
        >
          <LuPause class="size-4" />
          <span>Pause</span>
        </button>
        <button
          type="button"
          class="py-2 px-3 inline-flex items-center gap-x-1.5 text-sm font-medium rounded-lg border border-transparent text-gray-500 hover:bg-gray-200 hover:text-gray-800 focus:outline-hidden focus:bg-gray-200 focus:text-gray-800 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white dark:focus:bg-neutral-800 dark:focus:text-white"
        >
          <LuTrash2 class="size-4" />
          <span> Delete </span>
        </button>
      </div> -->
      <!-- End Col -->
    </div>
  </div>
</template>
