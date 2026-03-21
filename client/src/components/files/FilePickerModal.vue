<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useFileManager } from '@/composables/useFileManager';
import type { OfficeFile, FileFolder } from '@/types/files';

defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'select', files: OfficeFile[]): void;
}>();

const {
    files, folders, rootFileCount, isLoading, foldersLoading, pagination,
    fetchFiles, fetchFolders,
} = useFileManager();

const selectedFolderId = ref<number | null>(null);
const searchQuery = ref('');
const selectedIds = ref<Set<number>>(new Set());

const selectedFiles = computed(() => {
    return files.value.filter(f => selectedIds.value.has(f.id));
});

function toggleSelect(file: OfficeFile) {
    if (selectedIds.value.has(file.id)) {
        selectedIds.value.delete(file.id);
    } else {
        selectedIds.value.add(file.id);
    }
    // Force reactivity
    selectedIds.value = new Set(selectedIds.value);
}

function loadFiles(page = 1) {
    const params: Record<string, any> = { page, per_page: 20 };

    if (selectedFolderId.value !== null) {
        if (selectedFolderId.value === 0) {
            params.root_only = 'true';
        } else {
            params.folder_id = selectedFolderId.value;
        }
    }

    if (searchQuery.value.trim()) {
        params.search = searchQuery.value.trim();
    }

    fetchFiles(params);
}

function selectFolder(folderId: number | null) {
    selectedFolderId.value = folderId;
    loadFiles();
}

function search() {
    loadFiles();
}

function confirm() {
    if (selectedIds.value.size === 0) return;
    emit('select', [...selectedFiles.value]);
    selectedIds.value = new Set();
}

function close() {
    selectedIds.value = new Set();
    emit('close');
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

onMounted(() => {
    fetchFolders();
    loadFiles();
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="close">
            <div class="flex w-full max-w-2xl bg-white rounded-xl shadow-xl max-h-[80vh]">
                <!-- Left: Folders -->
                <div class="w-44 border-r border-gray-200 flex flex-col shrink-0">
                    <div class="px-3 py-2.5 border-b border-gray-200">
                        <span class="text-xs font-semibold text-gray-600 uppercase">Folders</span>
                    </div>
                    <div class="flex-1 overflow-auto py-1">
                        <button @click="selectFolder(null)"
                            class="flex items-center w-full gap-1.5 px-3 py-1.5 text-xs transition-colors"
                            :class="selectedFolderId === null ? 'bg-teal-50 text-teal-700 font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                            All Files
                        </button>
                        <button @click="selectFolder(0)"
                            class="flex items-center w-full gap-1.5 px-3 py-1.5 text-xs transition-colors"
                            :class="selectedFolderId === 0 ? 'bg-teal-50 text-teal-700 font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                            Unsorted
                        </button>
                        <button v-for="f in folders" :key="f.id" @click="selectFolder(f.id)"
                            class="flex items-center w-full gap-1.5 px-3 py-1.5 text-xs transition-colors"
                            :class="selectedFolderId === f.id ? 'bg-teal-50 text-teal-700 font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                            {{ f.name }}
                        </button>
                    </div>
                </div>

                <!-- Right: Files -->
                <div class="flex flex-col flex-1 min-w-0">
                    <!-- Header -->
                    <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200">
                        <h3 class="text-sm font-bold text-gray-700 shrink-0">Pick from Files</h3>
                        <div class="flex-1"></div>
                        <input v-model="searchQuery" type="text" placeholder="Search by name or OCR content..."
                            class="w-48 px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            @keyup.enter="search" />
                        <button @click="search"
                            class="px-2.5 py-1.5 text-xs font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors">
                            Search
                        </button>
                    </div>

                    <!-- File list -->
                    <div class="flex-1 overflow-auto">
                        <div v-if="isLoading" class="flex items-center justify-center py-10">
                            <div class="w-5 h-5 border-2 rounded-full border-teal-600 border-t-transparent animate-spin"></div>
                        </div>

                        <div v-else-if="files.length === 0" class="py-10 text-xs text-center text-gray-400">
                            No files found.
                        </div>

                        <div v-else class="divide-y divide-gray-100">
                            <div v-for="file in files" :key="file.id"
                                class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors"
                                :class="selectedIds.has(file.id) ? 'bg-teal-50' : 'hover:bg-gray-50'"
                                @click="toggleSelect(file)">
                                <!-- Checkbox -->
                                <div class="flex items-center justify-center w-4 h-4 border rounded shrink-0"
                                    :class="selectedIds.has(file.id) ? 'bg-teal-600 border-teal-600' : 'border-gray-300'">
                                    <svg v-if="selectedIds.has(file.id)" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white"
                                        class="size-3">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </div>
                                <!-- Icon -->
                                <svg v-if="file.mime_type === 'application/pdf'" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    class="size-5 text-red-400 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor"
                                    class="size-5 text-blue-400 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                </svg>
                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-700 truncate">{{ file.original_name }}</p>
                                    <p class="text-[10px] text-gray-400">
                                        {{ formatSize(file.file_size) }}
                                        <span v-if="file.folder"> · {{ file.folder.name }}</span>
                                    </p>
                                </div>
                                <!-- OCR badge -->
                                <span v-if="file.ocr_status === 'completed'"
                                    class="px-1.5 py-0.5 text-[9px] bg-emerald-100 text-emerald-700 rounded-full shrink-0">
                                    OCR
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1"
                        class="flex items-center justify-center gap-2 px-4 py-2 border-t border-gray-100">
                        <button v-for="p in pagination.last_page" :key="p" @click="loadFiles(p)"
                            class="px-2 py-1 text-[10px] rounded"
                            :class="p === pagination.current_page ? 'bg-teal-600 text-white' : 'text-gray-500 hover:bg-gray-100'">
                            {{ p }}
                        </button>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 shrink-0">
                        <span class="text-xs text-gray-500">
                            {{ selectedIds.size }} file(s) selected
                        </span>
                        <div class="flex gap-2">
                            <button @click="close"
                                class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button @click="confirm" :disabled="selectedIds.size === 0"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50">
                                Add Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
