<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import api from '@/api';
import type { OfficeFile } from '@/types/files';

const props = defineProps<{
    show: boolean;
    file: OfficeFile | null;
    searchableDownloadUrl?: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'download'): void;
    (e: 'delete'): void;
    (e: 'reprocess'): void;
}>();

const blobUrl = ref<string | null>(null);
const loadingPreview = ref(false);
const previewVersion = ref<'enhanced' | 'original'>('enhanced');

const isImage = computed(() => {
    return props.file?.mime_type?.startsWith('image/') ?? false;
});

const isPdf = computed(() => {
    return props.file?.mime_type === 'application/pdf';
});

const canPreview = computed(() => isImage.value || isPdf.value);

const canDelete = computed(() => {
    return (props.file?.attachment_count ?? 0) === 0;
});

const canReprocess = computed(() => {
    const enh = props.file?.enhancement_status;
    const ocr = props.file?.ocr_status;
    return enh === 'failed' || enh === 'skipped' || ocr === 'failed' || ocr === 'skipped';
});

const isAnyProcessing = computed(() => {
    return props.file?.enhancement_status === 'processing' ||
        props.file?.enhancement_status === 'pending' ||
        props.file?.ocr_status === 'processing' ||
        props.file?.ocr_status === 'pending';
});

async function loadPreview() {
    if (blobUrl.value) {
        URL.revokeObjectURL(blobUrl.value);
        blobUrl.value = null;
    }

    if (!props.show || !props.file?.id || !canPreview.value) return;

    loadingPreview.value = true;
    try {
        const versionParam = previewVersion.value === 'original' ? '?version=original' : '';
        const res = await api.get(`/files/${props.file.id}/preview${versionParam}`, { responseType: 'blob' });
        blobUrl.value = URL.createObjectURL(res.data);
    } catch {
        blobUrl.value = null;
    } finally {
        loadingPreview.value = false;
    }
}

// Fetch file as blob when modal opens or version changes
watch(() => [props.show, props.file?.id], () => {
    previewVersion.value = 'enhanced';
    loadPreview();
}, { immediate: true });

watch(previewVersion, () => {
    loadPreview();
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
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getStatusBadgeClass(status: string): string {
    switch (status) {
        case 'completed': return 'bg-emerald-100 text-emerald-700';
        case 'processing': return 'bg-blue-100 text-blue-700';
        case 'pending': return 'bg-yellow-100 text-yellow-700';
        case 'failed': return 'bg-red-100 text-red-700';
        case 'skipped': return 'bg-gray-100 text-gray-500';
        default: return 'bg-gray-100 text-gray-500';
    }
}

function getFileIcon(mime: string): string {
    if (mime === 'application/pdf') return 'pdf';
    if (mime?.startsWith('image/')) return 'image';
    return 'file';
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show && file" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="emit('close')">
            <div class="w-full max-w-5xl bg-white rounded-xl shadow-2xl max-h-[90vh] flex flex-col mx-4">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- File type icon -->
                        <div class="flex items-center justify-center rounded-lg size-9 shrink-0"
                            :class="getFileIcon(file.mime_type) === 'pdf' ? 'bg-red-50' : 'bg-blue-50'">
                            <svg v-if="getFileIcon(file.mime_type) === 'pdf'" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                class="text-red-500 size-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="text-blue-500 size-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-gray-700 truncate">{{ file.original_name }}</h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">
                                {{ file.mime_type }} &middot; {{ formatSize(file.file_size) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Enhanced/Original toggle -->
                        <div v-if="file.has_enhanced && canPreview"
                            class="flex items-center rounded-md border border-gray-200 text-[10px] overflow-hidden">
                            <button @click="previewVersion = 'enhanced'"
                                :class="['px-2.5 py-1 transition-colors', previewVersion === 'enhanced' ? 'bg-teal-600 text-white' : 'text-gray-500 hover:bg-gray-50']">
                                Enhanced
                            </button>
                            <button @click="previewVersion = 'original'"
                                :class="['px-2.5 py-1 transition-colors', previewVersion === 'original' ? 'bg-teal-600 text-white' : 'text-gray-500 hover:bg-gray-50']">
                                Original
                            </button>
                        </div>
                        <button @click="emit('close')"
                            class="p-1.5 text-gray-400 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body: Two panels -->
                <div class="flex flex-1 overflow-hidden">
                    <!-- Left: File preview -->
                    <div class="flex items-center justify-center flex-1 min-w-0 bg-gray-100">
                        <!-- Loading -->
                        <div v-if="loadingPreview" class="flex flex-col items-center gap-2 text-gray-400">
                            <div class="w-6 h-6 border-2 border-teal-600 rounded-full border-t-transparent animate-spin"></div>
                            <p class="text-xs">Loading preview...</p>
                        </div>

                        <!-- Image preview -->
                        <img v-else-if="isImage && blobUrl" :src="blobUrl" :alt="file.original_name"
                            class="object-contain w-full h-full max-h-[75vh] p-4" />

                        <!-- PDF preview -->
                        <iframe v-else-if="isPdf && blobUrl" :src="blobUrl" class="w-full h-full border-0"
                            style="min-height: 500px;" />

                        <!-- No preview fallback -->
                        <div v-else-if="!loadingPreview" class="flex flex-col items-center justify-center py-16 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                                stroke="currentColor" class="mb-3 size-16">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <p class="text-xs">Preview not available</p>
                            <p class="text-[10px] mt-1">Download the file to view it</p>
                        </div>
                    </div>

                    <!-- Right: File details sidebar -->
                    <div class="flex flex-col border-l border-gray-200 w-72 shrink-0">
                        <div class="flex-1 p-4 space-y-4 overflow-auto">
                            <!-- Info section -->
                            <div>
                                <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                    File Information
                                </h4>
                                <div class="space-y-2.5">
                                    <div>
                                        <label class="text-[10px] text-gray-400">File Name</label>
                                        <p class="text-xs text-gray-700 break-all">{{ file.original_name }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-400">Folder</label>
                                        <p class="text-xs text-gray-700">{{ file.folder?.name || 'Unsorted' }}</p>
                                    </div>
                                    <div class="flex gap-4">
                                        <div>
                                            <label class="text-[10px] text-gray-400">Type</label>
                                            <p class="text-xs text-gray-700">
                                                {{ file.mime_type.split('/')[1]?.toUpperCase() }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-400">Size</label>
                                            <p class="text-xs text-gray-700">{{ formatSize(file.file_size) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-gray-100" />

                            <!-- Upload info -->
                            <div>
                                <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                    Upload Details
                                </h4>
                                <div class="space-y-2.5">
                                    <div>
                                        <label class="text-[10px] text-gray-400">Uploaded By</label>
                                        <p class="text-xs text-gray-700">{{ file.uploaded_by_name }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-400">Uploaded At</label>
                                        <p class="text-xs text-gray-700">{{ formatDate(file.created_at) }}</p>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-gray-100" />

                            <!-- Enhancement Status -->
                            <div>
                                <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                    Enhancement
                                </h4>
                                <div class="flex items-center gap-2">
                                    <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full inline-flex items-center gap-1', getStatusBadgeClass(file.enhancement_status)]">
                                        <span v-if="file.enhancement_status === 'processing'" class="w-2 h-2 border border-current border-t-transparent rounded-full animate-spin"></span>
                                        {{ file.enhancement_status }}
                                    </span>
                                </div>
                                <div v-if="file.enhancement_error"
                                    class="p-2 mt-2 text-[10px] text-red-600 bg-red-50 rounded-lg break-all">
                                    {{ file.enhancement_error }}
                                </div>
                            </div>

                            <hr class="border-gray-100" />

                            <!-- OCR Status -->
                            <div>
                                <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                    OCR Status
                                </h4>
                                <div class="flex items-center gap-2">
                                    <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full inline-flex items-center gap-1', getStatusBadgeClass(file.ocr_status)]">
                                        <span v-if="file.ocr_status === 'processing'" class="w-2 h-2 border border-current border-t-transparent rounded-full animate-spin"></span>
                                        {{ file.ocr_status }}
                                    </span>
                                    <button v-if="canReprocess" @click="emit('reprocess')"
                                        class="text-[10px] text-blue-600 hover:text-blue-800 hover:underline">
                                        Reprocess
                                    </button>
                                </div>
                                <div v-if="file.ocr_error"
                                    class="p-2 mt-2 text-[10px] text-red-600 bg-red-50 rounded-lg break-all">
                                    {{ file.ocr_error }}
                                </div>
                                <div v-if="isAnyProcessing"
                                    class="flex items-center gap-1.5 mt-2 text-[10px] text-blue-600">
                                    <div
                                        class="w-3 h-3 border-2 rounded-full border-blue-600 border-t-transparent animate-spin">
                                    </div>
                                    Processing...
                                </div>
                            </div>

                            <hr class="border-gray-100" />

                            <!-- Transaction usage -->
                            <div>
                                <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                    Usage
                                </h4>
                                <p class="text-xs text-gray-700">
                                    Attached to <span class="font-semibold">{{ file.attachment_count ?? 0
                                        }}</span> transaction(s)
                                </p>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="p-4 space-y-2 border-t border-gray-200 shrink-0">
                            <button @click="emit('download')"
                                class="flex items-center justify-center w-full gap-1.5 px-3 py-2 text-xs font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Download
                            </button>
                            <!-- Searchable PDF download -->
                            <a v-if="file.has_searchable_pdf && searchableDownloadUrl"
                                :href="searchableDownloadUrl" target="_blank"
                                class="flex items-center justify-center w-full gap-1.5 px-3 py-2 text-xs font-medium text-teal-700 border border-teal-200 rounded-lg hover:bg-teal-50 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                Download Searchable PDF
                            </a>
                            <button v-if="canDelete" @click="emit('delete')"
                                class="flex items-center justify-center w-full gap-1.5 px-3 py-2 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Delete File
                            </button>
                            <p v-else class="text-[10px] text-center text-gray-400">
                                Cannot delete — attached to transactions
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
