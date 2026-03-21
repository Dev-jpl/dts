<script setup lang="ts">
import { computed } from 'vue';
import type { OfficeFile } from '@/types/files';

const props = defineProps<{
    file: OfficeFile;
    viewMode?: 'grid' | 'list';
    thumbnailUrl?: string;
}>();

const emit = defineEmits<{
    (e: 'preview', file: OfficeFile): void;
    (e: 'download', file: OfficeFile): void;
    (e: 'delete', file: OfficeFile): void;
}>();

const processingLabel = computed(() => {
    if (props.file.enhancement_status === 'processing') return 'Enhancing...';
    if (props.file.ocr_status === 'processing') return 'OCR...';
    if (props.file.enhancement_status === 'pending' || props.file.ocr_status === 'pending') return 'pending';
    return props.file.ocr_status;
});

const isProcessing = computed(() => {
    return props.file.enhancement_status === 'processing' ||
        props.file.ocr_status === 'processing' ||
        props.file.enhancement_status === 'pending' ||
        props.file.ocr_status === 'pending';
});

function formatSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '\u2014';
    return new Date(dateStr).toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function getFileIcon(mimeType: string): string {
    if (mimeType === 'application/pdf') return 'pdf';
    if (mimeType.startsWith('image/')) return 'image';
    return 'file';
}

function getStatusBadgeClass(status: string): string {
    switch (status) {
        case 'completed': return 'bg-emerald-100 text-emerald-700';
        case 'processing':
        case 'Enhancing...':
        case 'OCR...': return 'bg-blue-100 text-blue-700';
        case 'pending': return 'bg-yellow-100 text-yellow-700';
        case 'failed': return 'bg-red-100 text-red-700';
        case 'skipped': return 'bg-gray-100 text-gray-500';
        default: return 'bg-gray-100 text-gray-500';
    }
}
</script>

<template>
    <!-- Grid mode -->
    <div v-if="viewMode === 'grid'"
        class="flex flex-col p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-teal-300 hover:shadow-sm transition-all group"
        @click="emit('preview', file)">
        <!-- Thumbnail or Icon -->
        <div class="flex items-center justify-center w-full h-20 mb-2 rounded-md bg-gray-50 overflow-hidden">
            <img v-if="file.has_thumbnail && thumbnailUrl" :src="thumbnailUrl" :alt="file.original_name"
                class="object-cover w-full h-full" />
            <!-- PDF icon fallback -->
            <svg v-else-if="getFileIcon(file.mime_type) === 'pdf'" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"
                class="size-10 text-red-400">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <!-- Image icon fallback -->
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                stroke="currentColor" class="size-10 text-blue-400">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
            </svg>
        </div>

        <!-- Info -->
        <p class="text-xs font-medium text-gray-700 truncate" :title="file.original_name">{{ file.original_name }}</p>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[10px] text-gray-400">{{ formatSize(file.file_size) }}</span>
            <span :class="['px-1.5 py-0.5 text-[9px] font-medium rounded-full', getStatusBadgeClass(processingLabel)]">
                <span v-if="isProcessing" class="inline-flex items-center gap-1">
                    <span class="w-2 h-2 border border-current border-t-transparent rounded-full animate-spin"></span>
                    {{ processingLabel }}
                </span>
                <span v-else>{{ processingLabel }}</span>
            </span>
        </div>
    </div>

    <!-- List mode -->
    <tr v-else class="hover:bg-gray-50 cursor-pointer transition-colors group" @click="emit('preview', file)">
        <td class="px-3 py-2">
            <div class="flex items-center gap-2">
                <svg v-if="getFileIcon(file.mime_type) === 'pdf'" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-red-400 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-5 text-blue-400 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                </svg>
                <span class="text-xs text-gray-700 truncate max-w-[300px]" :title="file.original_name">
                    {{ file.original_name }}
                </span>
            </div>
        </td>
        <td class="px-3 py-2 text-xs text-gray-500">{{ file.folder?.name || 'Unsorted' }}</td>
        <td class="px-3 py-2 text-xs text-gray-500">{{ formatSize(file.file_size) }}</td>
        <td class="px-3 py-2 text-center">
            <span :class="['px-1.5 py-0.5 text-[10px] font-medium rounded-full inline-flex items-center gap-1', getStatusBadgeClass(processingLabel)]">
                <span v-if="isProcessing" class="w-2 h-2 border border-current border-t-transparent rounded-full animate-spin"></span>
                {{ processingLabel }}
            </span>
        </td>
        <td class="px-3 py-2 text-xs text-gray-400 text-right">{{ formatDate(file.created_at) }}</td>
        <td class="px-3 py-2 text-right">
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click.stop="emit('download', file)"
                    class="p-1 rounded hover:bg-gray-200" title="Download">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-3.5 text-gray-500">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </button>
                <button @click.stop="emit('delete', file)"
                    class="p-1 rounded hover:bg-red-100" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-3.5 text-red-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </button>
            </div>
        </td>
    </tr>
</template>
