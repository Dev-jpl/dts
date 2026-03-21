<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { FileFolder } from '@/types/files';

const props = defineProps<{
    show: boolean;
    folders: FileFolder[];
    currentFolderId?: number | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'upload', files: File[], folderId: number | null): void;
}>();

const selectedFiles = ref<File[]>([]);
const folderId = ref<number | null>(props.currentFolderId ?? null);
const isDragging = ref(false);

// Sync folder selection when modal opens or current folder changes
watch(() => props.show, (val) => {
    if (val) {
        // Use current folder, but treat 0 (Unsorted) as null (root)
        folderId.value = (props.currentFolderId && props.currentFolderId > 0)
            ? props.currentFolderId
            : null;
        selectedFiles.value = [];
    }
});

const acceptedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

const allFolders = computed(() => {
    const flat: { id: number; name: string; depth: number }[] = [];
    for (const folder of props.folders) {
        flat.push({ id: folder.id, name: folder.name, depth: 0 });
        if (folder.children) {
            for (const child of folder.children) {
                flat.push({ id: child.id, name: child.name, depth: 1 });
            }
        }
    }
    return flat;
});

function handleFileSelect(e: Event) {
    const input = e.target as HTMLInputElement;
    if (input.files) {
        addFiles(Array.from(input.files));
    }
    input.value = '';
}

function handleDrop(e: DragEvent) {
    isDragging.value = false;
    if (e.dataTransfer?.files) {
        addFiles(Array.from(e.dataTransfer.files));
    }
}

function addFiles(files: File[]) {
    const valid = files.filter(f => acceptedTypes.includes(f.type));
    selectedFiles.value.push(...valid);
}

function removeFile(index: number) {
    selectedFiles.value.splice(index, 1);
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function submit() {
    if (selectedFiles.value.length === 0) return;
    emit('upload', selectedFiles.value, folderId.value);
}

function close() {
    selectedFiles.value = [];
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="close">
            <div class="w-full max-w-lg bg-white rounded-xl shadow-xl">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700">Upload Files</h3>
                    <button @click="close" class="p-1 text-gray-400 rounded-lg hover:bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 space-y-4">
                    <!-- Folder selector -->
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600">Save to folder</label>
                        <select v-model="folderId"
                            class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option :value="null">Unsorted (no folder)</option>
                            <option v-for="f in allFolders" :key="f.id" :value="f.id">
                                {{ f.depth > 0 ? '  └ ' : '' }}{{ f.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Drop zone -->
                    <div class="relative border-2 border-dashed rounded-lg p-6 text-center transition-colors"
                        :class="isDragging ? 'border-teal-400 bg-teal-50' : 'border-gray-200 hover:border-gray-300'"
                        @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                            stroke="currentColor" class="mx-auto mb-2 size-8 text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <p class="text-xs text-gray-500">Drag & drop files here, or
                            <label class="text-teal-600 cursor-pointer hover:underline">
                                browse
                                <input type="file" class="hidden" multiple accept=".jpg,.jpeg,.png,.pdf"
                                    @change="handleFileSelect" />
                            </label>
                        </p>
                        <p class="mt-1 text-[10px] text-gray-400">JPEG, PNG, PDF</p>
                    </div>

                    <!-- Selected files -->
                    <div v-if="selectedFiles.length > 0" class="space-y-1.5 max-h-40 overflow-auto">
                        <div v-for="(file, i) in selectedFiles" :key="i"
                            class="flex items-center justify-between px-3 py-2 text-xs bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-2 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span class="truncate">{{ file.name }}</span>
                                <span class="text-gray-400 shrink-0">{{ formatSize(file.size) }}</span>
                            </div>
                            <button @click="removeFile(i)" class="p-0.5 text-red-400 hover:text-red-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-100">
                    <button @click="close"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button @click="submit" :disabled="selectedFiles.length === 0"
                        class="px-4 py-2 text-xs font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Upload {{ selectedFiles.length > 0 ? `(${selectedFiles.length})` : '' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
