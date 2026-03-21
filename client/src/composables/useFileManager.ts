import { ref } from 'vue';
import api from '@/api';
import type { FileFolder, OfficeFile, FoldersResponse } from '@/types/files';

export function useFileManager() {
    const files = ref<OfficeFile[]>([]);
    const folders = ref<FileFolder[]>([]);
    const rootFileCount = ref(0);
    const currentFile = ref<OfficeFile | null>(null);
    const isLoading = ref(false);
    const foldersLoading = ref(false);
    const error = ref<string | null>(null);
    const searchMode = ref<'smart' | 'keyword'>('smart');
    const activeSearchMode = ref<'smart' | 'keyword' | null>(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0,
    });

    // ── Files ────────────────────────────────────────────────────────────

    async function fetchFiles(params: Record<string, any> = {}) {
        isLoading.value = true;
        error.value = null;
        try {
            // Include search_mode param if searching
            if (params.search) {
                params.search_mode = searchMode.value;
            }
            const res = await api.get('/files', { params });
            const data = res.data.data;
            files.value = data.data;
            activeSearchMode.value = res.data.search_mode || null;
            pagination.value = {
                current_page: data.current_page,
                last_page: data.last_page,
                per_page: data.per_page,
                total: data.total,
            };
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Failed to load files.';
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchFile(id: number) {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await api.get(`/files/${id}`);
            currentFile.value = res.data.data;
            return res.data.data;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Failed to load file.';
            return null;
        } finally {
            isLoading.value = false;
        }
    }

    async function uploadFile(
        file: File,
        folderId: number | null,
        onProgress?: (pct: number) => void,
    ): Promise<OfficeFile | null> {
        const formData = new FormData();
        formData.append('file', file);
        if (folderId) formData.append('folder_id', String(folderId));

        try {
            const res = await api.post('/files/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                onUploadProgress: (e) => {
                    if (e.total && onProgress) {
                        onProgress(Math.round((e.loaded / e.total) * 100));
                    }
                },
            });
            return res.data.data;
        } catch (e: any) {
            throw e;
        }
    }

    async function uploadFiles(
        fileList: File[],
        folderId: number | null,
        onProgress?: (pct: number) => void,
    ): Promise<OfficeFile[]> {
        const formData = new FormData();
        fileList.forEach((f) => formData.append('files[]', f));
        if (folderId) formData.append('folder_id', String(folderId));

        try {
            const res = await api.post('/files/upload-bulk', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                onUploadProgress: (e) => {
                    if (e.total && onProgress) {
                        onProgress(Math.round((e.loaded / e.total) * 100));
                    }
                },
            });
            return res.data.data;
        } catch (e: any) {
            throw e;
        }
    }

    function getDownloadUrl(id: number): string {
        const token = localStorage.getItem('token');
        const base = api.defaults.baseURL;
        return `${base}/files/${id}/download?token=${token}`;
    }

    function getThumbnailUrl(id: number): string {
        const token = localStorage.getItem('token');
        const base = api.defaults.baseURL;
        return `${base}/files/${id}/thumbnail?token=${token}`;
    }

    function getSearchableDownloadUrl(id: number): string {
        const token = localStorage.getItem('token');
        const base = api.defaults.baseURL;
        return `${base}/files/${id}/download-searchable?token=${token}`;
    }

    async function deleteFile(id: number): Promise<{ success: boolean; message: string }> {
        const res = await api.delete(`/files/${id}`);
        return res.data;
    }

    async function reprocessOcr(id: number): Promise<OfficeFile> {
        const res = await api.post(`/files/${id}/reprocess`);
        return res.data.data;
    }

    // ── Folders ──────────────────────────────────────────────────────────

    async function fetchFolders() {
        foldersLoading.value = true;
        try {
            const res = await api.get('/files/folders');
            const data: FoldersResponse = res.data.data;
            folders.value = data.folders;
            rootFileCount.value = data.root_file_count;
        } catch (e: any) {
            console.error('Failed to load folders', e);
        } finally {
            foldersLoading.value = false;
        }
    }

    async function createFolder(name: string, description?: string, parentId?: number | null) {
        const res = await api.post('/files/folders', {
            name,
            description: description || null,
            parent_id: parentId || null,
        });
        return res.data.data as FileFolder;
    }

    async function updateFolder(id: number, name: string, description?: string) {
        const res = await api.put(`/files/folders/${id}`, {
            name,
            description: description || null,
        });
        return res.data.data as FileFolder;
    }

    async function deleteFolder(id: number): Promise<{ success: boolean; message: string }> {
        const res = await api.delete(`/files/folders/${id}`);
        return res.data;
    }

    return {
        // State
        files,
        folders,
        rootFileCount,
        currentFile,
        isLoading,
        foldersLoading,
        error,
        pagination,
        searchMode,
        activeSearchMode,
        // Files
        fetchFiles,
        fetchFile,
        uploadFile,
        uploadFiles,
        getDownloadUrl,
        getThumbnailUrl,
        getSearchableDownloadUrl,
        deleteFile,
        reprocessOcr,
        // Folders
        fetchFolders,
        createFolder,
        updateFolder,
        deleteFolder,
    };
}
