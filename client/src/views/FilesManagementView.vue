<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import FolderTree from "@/components/files/FolderTree.vue";
import FileCard from "@/components/files/FileCard.vue";
import FileUploadModal from "@/components/files/FileUploadModal.vue";
import FilePreviewModal from "@/components/files/FilePreviewModal.vue";
import { useFileManager } from "@/composables/useFileManager";
import { useOcr } from "@/composables/useOcr";
import type { OfficeFile, FileFolder } from "@/types/files";
import { ref, onMounted } from "vue";

// ── Composables ─────────────────────────────────────────────────────────────
const {
    files, folders, rootFileCount, isLoading, foldersLoading, pagination, error,
    searchMode, activeSearchMode,
    fetchFiles, fetchFile, fetchFolders, uploadFile, uploadFiles,
    getDownloadUrl, getThumbnailUrl, getSearchableDownloadUrl,
    deleteFile, reprocessOcr,
    createFolder, updateFolder, deleteFolder,
} = useFileManager();

const { pollOcrStatus } = useOcr();

// ── State ───────────────────────────────────────────────────────────────────
const selectedFolderId = ref<number | null>(null);
const selectedIncludesSubfolders = ref(false);
const searchQuery = ref("");
const viewMode = ref<"list" | "grid">("list");

// Modals
const showUploadModal = ref(false);
const showPreviewModal = ref(false);
const previewFile = ref<OfficeFile | null>(null);

// Upload state
const uploading = ref(false);
const uploadProgress = ref(0);


// ── Load data ───────────────────────────────────────────────────────────────
function loadFiles(page = 1) {
    const params: Record<string, any> = { page, per_page: 25 };

    if (selectedFolderId.value !== null) {
        if (selectedFolderId.value === 0) {
            params.root_only = "true";
        } else {
            params.folder_id = selectedFolderId.value;
            if (selectedIncludesSubfolders.value) {
                params.include_subfolders = "true";
            }
        }
    }

    if (searchQuery.value.trim()) {
        params.search = searchQuery.value.trim();
    }

    fetchFiles(params);
}

function selectFolder(folderId: number | null, includeSubfolders?: boolean) {
    selectedFolderId.value = folderId;
    selectedIncludesSubfolders.value = includeSubfolders ?? false;
    searchQuery.value = "";
    loadFiles();
}

function search() {
    loadFiles();
}

function clearSearch() {
    searchQuery.value = "";
    loadFiles();
}

// ── Upload ──────────────────────────────────────────────────────────────────
async function handleUpload(fileList: File[], folderId: number | null) {
    uploading.value = true;
    uploadProgress.value = 0;

    try {
        if (fileList.length === 1) {
            const result = await uploadFile(fileList[0], folderId, (pct) => {
                uploadProgress.value = pct;
            });
            if (result) {
                pollOcrStatus(result.id, (updated) => {
                    const idx = files.value.findIndex(f => f.id === updated.id);
                    if (idx !== -1) files.value[idx] = updated;
                });
            }
        } else {
            await uploadFiles(fileList, folderId, (pct) => {
                uploadProgress.value = pct;
            });
        }

        showUploadModal.value = false;
        loadFiles();
        fetchFolders();
    } catch (e: any) {
        alert(e.response?.data?.message || "Upload failed.");
    } finally {
        uploading.value = false;
    }
}

// ── File actions ────────────────────────────────────────────────────────────
async function handlePreview(file: OfficeFile) {
    const detail = await fetchFile(file.id);
    if (detail) {
        previewFile.value = detail;
        showPreviewModal.value = true;
    }
}

function handleDownload(file: OfficeFile) {
    const url = getDownloadUrl(file.id);
    window.open(url, "_blank");
}

async function handleDelete(file: OfficeFile) {
    if (!confirm(`Delete "${file.original_name}"? This cannot be undone.`)) return;

    try {
        await deleteFile(file.id);
        showPreviewModal.value = false;
        previewFile.value = null;
        loadFiles();
        fetchFolders();
    } catch (e: any) {
        alert(e.response?.data?.message || "Delete failed.");
    }
}

async function handleReprocess() {
    if (!previewFile.value) return;
    try {
        const updated = await reprocessOcr(previewFile.value.id);
        previewFile.value = updated;
        pollOcrStatus(updated.id, (f) => {
            previewFile.value = f;
            const idx = files.value.findIndex(x => x.id === f.id);
            if (idx !== -1) files.value[idx] = f;
        });
    } catch (e: any) {
        alert(e.response?.data?.message || "Reprocess failed.");
    }
}

// ── Folder actions ──────────────────────────────────────────────────────────
async function handleInlineCreateFolder(data: { name: string; parentId: number | null }) {
    try {
        await createFolder(data.name, undefined, data.parentId);
        fetchFolders();
    } catch (e: any) {
        alert(e.response?.data?.message || "Folder creation failed.");
    }
}

async function handleRenameFolder(folder: FileFolder, newName: string) {
    try {
        await updateFolder(folder.id, newName);
        fetchFolders();
    } catch (e: any) {
        alert(e.response?.data?.message || "Rename failed.");
    }
}

async function handleDeleteFolder(folder: FileFolder) {
    if (!confirm(`Delete folder "${folder.name}"?`)) return;

    try {
        await deleteFolder(folder.id);
        if (selectedFolderId.value === folder.id) {
            selectedFolderId.value = null;
        }
        fetchFolders();
        loadFiles();
    } catch (e: any) {
        alert(e.response?.data?.message || "Delete folder failed.");
    }
}

// ── Pagination ──────────────────────────────────────────────────────────────
function goToPage(page: number) {
    loadFiles(page);
}

// ── Init ────────────────────────────────────────────────────────────────────
onMounted(() => {
    fetchFolders();
    loadFiles();
});
</script>

<template>
    <ScrollableContainer padding="0" px="0" background="white" class="bg-white">
        <div class="flex w-full h-full">
            <!-- ── Left: Folder sidebar ──────────────────────────────────── -->
            <div class="flex flex-col border-r border-gray-200 w-52 bg-gray-50/50 shrink-0">
                <FolderTree :folders="folders" :selected-folder-id="selectedFolderId"
                    :selected-includes-subfolders="selectedIncludesSubfolders"
                    :root-file-count="rootFileCount" :loading="foldersLoading" @select="selectFolder"
                    @create="handleInlineCreateFolder" @rename="handleRenameFolder" @delete="handleDeleteFolder" />
            </div>

            <!-- ── Right: File list ──────────────────────────────────────── -->
            <div class="flex flex-col flex-1 min-w-0">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                    <div>
                        <h1 class="text-lg font-bold text-gray-700">Files</h1>
                        <p class="text-xs text-gray-400">Manage your office file repository</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Smart search toggle -->
                        <button @click="searchMode = searchMode === 'smart' ? 'keyword' : 'smart'"
                            class="flex items-center gap-1 px-2 py-1.5 text-[10px] font-medium rounded-lg border transition-colors"
                            :class="searchMode === 'smart'
                                ? 'bg-violet-50 text-violet-700 border-violet-200'
                                : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-100'"
                            :title="searchMode === 'smart' ? 'Smart Search: finds contextually related files' : 'Keyword Search: exact match only'">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                            </svg>
                            Smart
                        </button>

                        <!-- Search -->
                        <div class="relative">
                            <input v-model="searchQuery" type="text"
                                :placeholder="searchMode === 'smart' ? 'Search by content or meaning...' : 'Search files or OCR content...'"
                                class="w-56 pl-8 pr-8 py-1.5 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                @keyup.enter="search" />
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <button v-if="searchQuery" @click="clearSearch"
                                class="absolute text-gray-400 -translate-y-1/2 right-2 top-1/2 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- View toggle -->
                        <div class="flex overflow-hidden border border-gray-200 rounded-lg">
                            <button @click="viewMode = 'list'"
                                class="p-1.5 transition-colors"
                                :class="viewMode === 'list' ? 'bg-teal-600 text-white' : 'text-gray-400 hover:bg-gray-50'">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </button>
                            <button @click="viewMode = 'grid'"
                                class="p-1.5 transition-colors"
                                :class="viewMode === 'grid' ? 'bg-teal-600 text-white' : 'text-gray-400 hover:bg-gray-50'">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Upload button -->
                        <button @click="showUploadModal = true"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            Upload
                        </button>
                    </div>
                </div>

                <!-- Upload progress bar -->
                <div v-if="uploading" class="px-4 py-2 border-b border-teal-100 bg-teal-50">
                    <div class="flex items-center gap-2 text-xs text-teal-700">
                        <div class="w-4 h-4 border-2 border-teal-600 rounded-full border-t-transparent animate-spin shrink-0"></div>
                        <span>Uploading... {{ uploadProgress }}%</span>
                        <div class="flex-1 h-1.5 bg-teal-200 rounded-full overflow-hidden">
                            <div class="h-full transition-all bg-teal-600 rounded-full"
                                :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Search mode indicator -->
                <div v-if="activeSearchMode && searchQuery"
                    class="flex items-center gap-2 px-4 py-1.5 text-[10px] border-b border-gray-100 bg-gray-50/50">
                    <span class="text-gray-400">Results via</span>
                    <span :class="['px-1.5 py-0.5 rounded-full font-medium',
                        activeSearchMode === 'smart' ? 'bg-violet-100 text-violet-700' : 'bg-gray-200 text-gray-600']">
                        {{ activeSearchMode === 'smart' ? 'Smart Search' : 'Keyword' }}
                    </span>
                    <span class="text-gray-400">&middot; {{ pagination.total }} result(s)</span>
                </div>

                <!-- Content area -->
                <div class="flex-1 overflow-auto">
                    <!-- Loading -->
                    <div v-if="isLoading" class="flex items-center justify-center py-16">
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <div class="w-4 h-4 border-2 border-teal-600 rounded-full border-t-transparent animate-spin"></div>
                            Loading files...
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="py-16 text-xs text-center text-red-400">{{ error }}</div>

                    <!-- Empty -->
                    <div v-else-if="files.length === 0" class="flex flex-col items-center justify-center py-16">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                            stroke="currentColor" class="mb-3 text-gray-200 size-12">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9.75m0 0 2.25 2.25M9.75 15l2.25-2.25M13.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <p class="text-xs text-gray-400">No files yet.</p>
                        <button @click="showUploadModal = true"
                            class="mt-2 px-3 py-1.5 text-xs font-medium text-teal-600 border border-teal-200 rounded-lg hover:bg-teal-50 transition-colors">
                            Upload your first file
                        </button>
                    </div>

                    <!-- List view -->
                    <table v-else-if="viewMode === 'list'" class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <td class="px-3 py-2 text-xs font-semibold text-gray-600">Name</td>
                                <td class="px-3 py-2 text-xs font-semibold text-gray-600">Folder</td>
                                <td class="px-3 py-2 text-xs font-semibold text-gray-600">Size</td>
                                <td class="px-3 py-2 text-xs font-semibold text-center text-gray-600">OCR</td>
                                <td class="px-3 py-2 text-xs font-semibold text-right text-gray-600">Uploaded</td>
                                <td class="w-20 px-3 py-2 text-xs font-semibold text-gray-600"></td>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <FileCard v-for="file in files" :key="file.id" :file="file" view-mode="list"
                                @preview="handlePreview" @download="handleDownload" @delete="handleDelete" />
                        </tbody>
                    </table>

                    <!-- Grid view -->
                    <div v-else class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        <FileCard v-for="file in files" :key="file.id" :file="file" view-mode="grid"
                            :thumbnail-url="file.has_thumbnail ? getThumbnailUrl(file.id) : undefined"
                            @preview="handlePreview" @download="handleDownload" @delete="handleDelete" />
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1"
                    class="flex items-center justify-between px-4 py-2.5 border-t border-gray-200 shrink-0">
                    <span class="text-[10px] text-gray-400">
                        Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }}–{{
                            Math.min(pagination.current_page * pagination.per_page, pagination.total)
                        }} of {{ pagination.total }}
                    </span>
                    <div class="flex gap-1">
                        <button v-for="p in pagination.last_page" :key="p" @click="goToPage(p)"
                            class="px-2.5 py-1 text-[10px] rounded-md transition-colors"
                            :class="p === pagination.current_page
                                ? 'bg-teal-600 text-white'
                                : 'text-gray-500 hover:bg-gray-100'">
                            {{ p }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ScrollableContainer>

    <!-- ── Modals ────────────────────────────────────────────────────────── -->
    <FileUploadModal :show="showUploadModal" :folders="folders"
        :current-folder-id="selectedFolderId" @close="showUploadModal = false" @upload="handleUpload" />

    <FilePreviewModal :show="showPreviewModal" :file="previewFile"
        :searchable-download-url="previewFile?.has_searchable_pdf ? getSearchableDownloadUrl(previewFile.id) : undefined"
        @close="showPreviewModal = false" @download="() => previewFile && handleDownload(previewFile)"
        @delete="() => previewFile && handleDelete(previewFile)" @reprocess="handleReprocess" />

</template>
