<script setup lang="ts">
import { ref } from "vue"
import { FiMessageCircle } from "vue-icons-plus/fi"
import Textarea from "../ui/textareas/Textarea.vue"
import BaseButton from "../ui/buttons/BaseButton.vue"
import dayjs from "dayjs"
import relativeTime from "dayjs/plugin/relativeTime"

dayjs.extend(relativeTime)

const props = defineProps<{
  comments: any[]
  trxNo: string
  onPost: (trxNo: string, comment: string) => Promise<any>
}>()

const newComment = ref('')
const submitting = ref(false)

async function submitComment() {
  if (!newComment.value.trim()) return
  submitting.value = true
  try {
    await props.onPost(props.trxNo, newComment.value.trim())
    newComment.value = ''
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="relative h-[calc(100svh-10rem)] overflow-hidden w-full">
    <div class="h-[calc(100svh-10rem)] overflow-y-auto w-full">

      <div v-if="comments.length === 0" class="py-10 text-xs text-center text-gray-400">
        No comments yet. Be the first to comment.
      </div>

      <template v-for="(item, index) in comments" :key="item.id ?? index">
        <div class="flex pb-6 gap-x-3">
          <div
            class="relative last:after:hidden after:absolute after:top-7 after:bottom-0 after:start-3.5 after:w-px after:-translate-x-[0.5px] after:bg-gray-300">
            <div class="relative z-10 flex items-center justify-center size-7">
              <FiMessageCircle class="w-4 h-4 mr-1" />
            </div>
          </div>
          <div class="grow">
            <h3 class="text-xs font-medium text-gray-800">
              {{ item.assigned_personnel_name }}
            </h3>
            <span class="px-2 text-xs py-0.5 font-medium text-gray-500 bg-gray-100 rounded">
              {{ item.office_name }}
            </span>
            <p class="mt-1 text-sm text-gray-600">{{ item.comment }}</p>
            <p class="text-[10px] text-gray-400 mt-1">
              {{ dayjs(item.created_at).fromNow() }}
            </p>
          </div>
        </div>
      </template>

    </div>

    <div class="absolute left-0 w-full p-4 bottom-10">
      <Textarea v-model="newComment" :label="'Comment'" />
      <div class="flex justify-end mt-2">
        <BaseButton :btn-text="submitting ? 'Posting...' : 'Post Comment'" :action="submitComment"
          :disabled="submitting || !newComment.trim()" />
      </div>
    </div>
  </div>
</template>