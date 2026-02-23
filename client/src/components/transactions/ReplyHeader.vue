<script setup lang="ts">
import { useReplyDocument } from '@/composables/useReplyDoc';

const { documentNo, isReply } = useReplyDocument();
</script>
<template>
    <div class="flex items-center text-sm text-gray-600" v-if="isReply">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="mr-2 size-4">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M7.49 12 3.74 8.248m0 0 3.75-3.75m-3.75 3.75h16.5V19.5" />
        </svg>
        Replying to document:
        <b class="ml-2 text-gray-900">
            <RouterLink :to="{ name: 'view-document', params: { doc_no: documentNo } }" class="hover:underline">
                {{ documentNo }}
            </RouterLink>
        </b>
    </div>
</template>