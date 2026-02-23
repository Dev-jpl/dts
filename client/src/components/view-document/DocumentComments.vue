<script setup lang="ts">
import { FiMessageCircle } from "vue-icons-plus/fi";
import Textarea from "../ui/textareas/Textarea.vue";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";

dayjs.extend(relativeTime);

defineProps({ comments: { type: Array } });
</script>

<template>
  <div class="relative h-full">
    <div class="h-[calc(100svh-350px)] overflow-y-auto w-full">
      <template
        v-for="(commentItem, index) in comments"
        :key="index"
      >
        <!-- Date Heading -->
        <!-- <div class="mb-3 ps-2 first:mt-0">
          <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-400">
            {{ commentItem.date }}
          </h3>
        </div> -->

        <!-- Comment Entry -->
        <div class="flex pb-6 gap-x-3">
          <!-- Timeline Line -->
          <div
            class="relative last:after:hidden after:absolute after:top-7 after:bottom-0 after:start-3.5 after:w-px after:-translate-x-[0.5px] after:bg-gray-300 dark:after:bg-neutral-700"
          >
            <div class="relative z-10 flex items-center justify-center size-7">
              <!-- <div
                class="bg-gray-500 rounded-full size-2 dark:bg-gray-400"
              ></div> -->
              <FiMessageCircle class="w-4 h-4 mr-1" />
            </div>
          </div>

          <!-- Comment Body -->
          <div class="grow">
            <h3
              class="flex items-center text-xs font-medium text-gray-800 dark:text-white gap-x-2"
            >
              {{ commentItem.user }}
            </h3>
            <span
              class="px-2 text-xs py-0.5 font-medium text-gray-500 bg-gray-100 rounded dark:bg-neutral-700 dark:text-neutral-300"
            >
              {{ commentItem.service }}
            </span>
            <p class="mt-1 text-sm text-gray-600 dark:text-neutral-400">
              {{ commentItem.comment }}
            </p>

            <!-- Metadata Row -->
            <div class="flex flex-wrap items-center gap-2 mt-2 text-xs">
              <!-- <span class="italic text-gray-500 dark:text-neutral-300">
              — {{ commentItem.user }}
            </span> -->
            </div>

            <h3
              class="text-[10px] font-semibold text-gray-600 dark:text-gray-400"
            >
              {{ dayjs(commentItem.date).fromNow() }}
            </h3>
          </div>
        </div>
      </template>
    </div>
    <div class="absolute left-0 w-full p-4 bottom-10">
      <Textarea :label="'Comment'" />
    </div>
  </div>
</template>
