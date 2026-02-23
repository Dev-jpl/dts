<script setup lang="ts">
import {
  HiCheckCircle,
  HiOutlinePauseCircle,
  HiOutlineTrash,
} from "vue-icons-plus/hi2";
const props = defineProps<{
  task: {
    name?: string;
    size?: string;
    type?: string;
    url?: string;
    progress?: number;
    finished?: boolean;
    file?: File;
  };
}>();
</script>

<template>
  <div class="p-3 hover:bg-gray-50 dark:hover:bg-neutral-800">
    <!-- File Info -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-x-3">
        <span
          class="flex items-center justify-center text-gray-500 border border-gray-200 rounded-lg size-8 dark:text-neutral-500 dark:border-neutral-700"
        >
          📄
        </span>
        <div>
          <template v-if="props.task?.url">
            <a class="text-[11px] hover:underline hover:text-teal-700 font-medium text-gray-800 dark:text-white" :href="props.task.url" target="_blank">
              {{ props.task.name }}
            </a>
          </template>

          <template v-else>
            <p class="text-[11px] font-medium text-gray-800 dark:text-white">
              {{ props.task.name }}
            </p>
          </template>
          <p class="text-[8px] text-gray-500 dark:text-neutral-500">
            {{ props.task.size }}
          </p>
        </div>
      </div>

      <div class="inline-flex items-center gap-x-2">
        <HiCheckCircle
          v-if="props.task.finished"
          class="size-5 text-lime-500"
        />
        <button
          v-if="props.task.file && !props.task.finished"
          class="p-2 text-gray-500 rounded-full hover:bg-gray-100 hover:text-gray-800 dark:text-neutral-500 dark:hover:text-neutral-200"
          disabled
        >
          <HiOutlinePauseCircle class="size-4" />
        </button>
        <button
          class="p-2 text-gray-500 rounded-full hover:bg-gray-100 hover:text-red-600 dark:text-neutral-500 dark:hover:text-neutral-200"
          disabled
        >
          <HiOutlineTrash class="size-4" />
        </button>
      </div>
    </div>
    <!-- Progress Bar -->
    <template v-if="task">
      <div
        v-show="
          props.task?.progress !== undefined && props.task?.progress < 100
        "
        class="flex items-center gap-x-3 whitespace-nowrap"
      >
        <div
          class="flex w-full h-1.5 overflow-hidden bg-gray-200 rounded-full dark:bg-neutral-700"
        >
          <div
            class="h-full rounded-full transition-[width,background-color] duration-300 ease-in-out"
            :class="props.task.progress === 100 ? 'bg-lime-500' : 'bg-teal-700'"
            :style="{ width: props.task.progress + '%' }"
          ></div>
        </div>
        <div class="w-10 text-xs text-right text-gray-800 dark:text-white">
          {{ props.task.progress }}%
        </div>
      </div>
    </template>
  </div>
</template>
