<script setup lang="ts">
import { ref, nextTick } from 'vue';
import type { FileFolder } from '@/types/files';

const props = defineProps<{
    folders: FileFolder[];
    selectedFolderId: number | null;
    selectedIncludesSubfolders?: boolean;
    rootFileCount: number;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: 'select', folderId: number | null, includeSubfolders?: boolean): void;
    (e: 'create', data: { name: string; parentId: number | null }): void;
    (e: 'rename', folder: FileFolder, newName: string): void;
    (e: 'delete', folder: FileFolder): void;
}>();

// ── Expand/collapse state ──────────────────────────────────────────────────
const expandedIds = ref<Set<number>>(new Set());

function toggleExpand(folderId: number) {
    if (expandedIds.value.has(folderId)) {
        expandedIds.value.delete(folderId);
    } else {
        expandedIds.value.add(folderId);
    }
}

function isExpanded(folderId: number): boolean {
    return expandedIds.value.has(folderId);
}

// ── Inline new-folder creation ─────────────────────────────────────────────
const newFolderParentId = ref<number | null | 'root'>(null); // null = not creating, 'root' = root level
const newFolderName = ref('');
const newFolderFocused = ref(false);
function setNewFolderInputRef(el: any) {
    if (el && !newFolderFocused.value) {
        newFolderFocused.value = true;
        nextTick(() => {
            (el as HTMLInputElement).focus();
            (el as HTMLInputElement).select();
        });
    }
}

function getUntitledName(parentId: number | null): string {
    // Get siblings at same level
    const siblings = parentId === null
        ? props.folders
        : props.folders.find(f => f.id === parentId)?.children ?? [];

    const baseName = 'Untitled';
    const existingNames = new Set(siblings.map(f => f.name));

    if (!existingNames.has(baseName)) return baseName;

    let i = 1;
    while (existingNames.has(`${baseName} (${i})`)) i++;
    return `${baseName} (${i})`;
}

async function startNewFolder(parentId: number | null) {
    // Expand parent if creating inside
    if (parentId !== null) {
        expandedIds.value.add(parentId);
    }

    newFolderFocused.value = false;
    newFolderParentId.value = parentId === null ? 'root' : parentId;
    newFolderName.value = getUntitledName(parentId);
}

function commitNewFolder() {
    const name = newFolderName.value.trim();
    if (!name) {
        cancelNewFolder();
        return;
    }

    const parentId = newFolderParentId.value === 'root' ? null : (newFolderParentId.value as number);
    emit('create', { name, parentId });
    newFolderParentId.value = null;
    newFolderName.value = '';
    newFolderFocused.value = false;
}

function cancelNewFolder() {
    newFolderParentId.value = null;
    newFolderName.value = '';
    newFolderFocused.value = false;
}

function handleNewFolderKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
        e.preventDefault();
        commitNewFolder();
    } else if (e.key === 'Escape') {
        e.preventDefault();
        cancelNewFolder();
    }
}

// ── Inline rename ──────────────────────────────────────────────────────────
const renamingFolderId = ref<number | null>(null);
const renameValue = ref('');
const renameInput = ref<HTMLInputElement | null>(null);

async function startRename(folder: FileFolder) {
    renamingFolderId.value = folder.id;
    renameValue.value = folder.name;
    await nextTick();
    renameInput.value?.focus();
    renameInput.value?.select();
}

function commitRename(folder: FileFolder) {
    const name = renameValue.value.trim();
    if (name && name !== folder.name) {
        emit('rename', folder, name);
    }
    renamingFolderId.value = null;
    renameValue.value = '';
}

function cancelRename() {
    renamingFolderId.value = null;
    renameValue.value = '';
}

function handleRenameKeydown(e: KeyboardEvent, folder: FileFolder) {
    if (e.key === 'Enter') {
        e.preventDefault();
        commitRename(folder);
    } else if (e.key === 'Escape') {
        e.preventDefault();
        cancelRename();
    }
}

// ── Helper: is creating new folder inside this parent ──────────────────────
function isCreatingInside(parentId: number | null): boolean {
    if (parentId === null) return newFolderParentId.value === 'root';
    return newFolderParentId.value === parentId;
}

// ── Folder click logic ─────────────────────────────────────────────────────
function hasChildren(folder: FileFolder): boolean {
    return !!(folder.children && folder.children.length > 0);
}

function handleFolderClick(folder: FileFolder) {
    if (hasChildren(folder)) {
        // Has subfolders: toggle expand, show consolidated files
        toggleExpand(folder.id);
        emit('select', folder.id, true);
    } else {
        // No subfolders: select directly to show files
        emit('select', folder.id, false);
    }
}

// ── Total file count for a folder (including children) ─────────────────────
function totalFileCount(folder: FileFolder): number {
    let count = folder.files_count ?? 0;
    if (folder.children) {
        for (const child of folder.children) {
            count += totalFileCount(child);
        }
    }
    return count;
}
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200">
            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Folders</span>
            <button @click="startNewFolder(null)"
                class="p-1 text-gray-400 rounded hover:text-teal-600 hover:bg-teal-50 transition-colors"
                title="New Folder">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="p-3">
            <div v-for="i in 4" :key="i" class="w-full h-6 mb-2 bg-gray-100 rounded animate-pulse"></div>
        </div>

        <!-- Tree content -->
        <div v-else class="flex-1 overflow-auto py-1">
            <!-- All Files (root) -->
            <button @click="emit('select', null)"
                class="flex items-center w-full gap-2 px-3 py-1.5 text-xs transition-colors"
                :class="selectedFolderId === null ? 'bg-gray-100 text-teal-700 font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-4 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                </svg>
                <span>All Files</span>
            </button>

            <!-- Unsorted (root files) -->
            <button @click="emit('select', 0)"
                class="flex items-center w-full gap-2 px-3 py-1.5 text-xs transition-colors"
                :class="selectedFolderId === 0 ? 'bg-gray-100 text-teal-700 font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-4 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <span>Unsorted</span>
                <span v-if="rootFileCount > 0"
                    class="ml-auto px-1.5 py-0.5 text-[10px] bg-gray-200 rounded-full">{{ rootFileCount }}</span>
            </button>

            <!-- Folder items (recursive via FolderItem) -->
            <template v-for="folder in folders" :key="folder.id">
                <!-- Parent folder row -->
                <div class="group flex items-center w-full gap-0 px-1 py-0 text-xs transition-colors relative"
                    :class="selectedFolderId === folder.id ? 'bg-gray-100 text-teal-700' : 'text-gray-600 hover:bg-gray-50'">

                    <!-- Chevron toggle -->
                    <button @click.stop="toggleExpand(folder.id)"
                        class="p-1 rounded transition-transform shrink-0"
                        :class="folder.children && folder.children.length > 0 ? 'text-gray-400 hover:text-gray-600' : 'text-transparent pointer-events-none'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-3 transition-transform duration-200"
                            :class="isExpanded(folder.id) ? 'rotate-90' : ''">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>

                    <!-- Folder icon + name (clickable) -->
                    <div class="flex items-center gap-2 flex-1 min-w-0 py-1.5 px-1 cursor-pointer rounded"
                        @click="handleFolderClick(folder)"
                        @dblclick.stop="startRename(folder)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4 shrink-0"
                            :class="selectedFolderId === folder.id ? 'text-teal-600' : 'text-amber-500'">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>

                        <!-- Rename mode -->
                        <input v-if="renamingFolderId === folder.id"
                            ref="renameInput"
                            v-model="renameValue"
                            type="text"
                            maxlength="150"
                            class="flex-1 min-w-0 px-1.5 py-0.5 text-xs border border-teal-400 rounded outline-none bg-white focus:ring-1 focus:ring-teal-400"
                            @blur="commitRename(folder)"
                            @keydown="handleRenameKeydown($event, folder)"
                            @click.stop />

                        <!-- Normal display -->
                        <span v-else class="truncate" :class="selectedFolderId === folder.id ? 'font-semibold' : ''">
                            {{ folder.name }}
                        </span>
                    </div>

                    <!-- Right side: badge / hover actions (fixed width for alignment) -->
                    <div class="flex items-center justify-end w-10 shrink-0 mr-1">
                        <!-- Count badge (shown when not hovering) -->
                        <span v-if="totalFileCount(folder) > 0 && renamingFolderId !== folder.id"
                            class="px-1.5 py-0.5 text-[10px] bg-gray-200 rounded-full group-hover:hidden">
                            {{ totalFileCount(folder) }}
                        </span>
                    </div>

                    <!-- Hover actions (overlays the fixed-width area) -->
                    <div v-if="renamingFolderId !== folder.id"
                        class="absolute right-1 items-center hidden gap-0.5 group-hover:flex shrink-0">
                        <!-- Add subfolder -->
                        <button @click.stop="startNewFolder(folder.id)"
                            class="p-0.5 rounded hover:bg-teal-100 text-gray-400 hover:text-teal-600 transition-colors"
                            title="New subfolder">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                            </svg>
                        </button>
                        <!-- Delete -->
                        <button @click.stop="emit('delete', folder)"
                            class="p-0.5 rounded hover:bg-red-100 text-gray-400 hover:text-red-500 transition-colors"
                            title="Delete folder">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Children container (collapsible) -->
                <div class="overflow-hidden transition-all duration-200 ease-in-out"
                    :style="{ maxHeight: isExpanded(folder.id) ? '500px' : '0px', opacity: isExpanded(folder.id) ? 1 : 0 }">
                    <div class="relative ml-[18px]">
                        <!-- Vertical tree line -->
                        <div class="absolute top-0 left-0 w-px bg-gray-200"
                            :style="{ height: 'calc(100% - 12px)' }"></div>

                        <!-- "Files (N)" row for parent folder's own files -->
                        <div v-if="(folder.files_count ?? 0) > 0"
                            class="relative flex items-center w-full gap-0 pr-1 py-0 text-xs transition-colors cursor-pointer"
                            :class="selectedFolderId === folder.id && !selectedIncludesSubfolders ? 'bg-gray-200 text-teal-700' : 'text-gray-500 hover:bg-gray-50'"
                            @click="emit('select', folder.id, false)">
                            <!-- Tree branch connector -->
                            <div class="flex items-center shrink-0 w-5 h-full">
                                <div class="relative w-full h-full flex items-center">
                                    <div class="absolute left-0 top-1/2 w-3.5 h-px bg-gray-200"></div>
                                    <div class="absolute left-0 top-1/2 w-px h-1/2 bg-gray-200"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-1 min-w-0 py-1.5 px-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-3.5 shrink-0 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span>Files</span>
                                <span class="ml-auto px-1.5 py-0.5 text-[10px] bg-gray-200 rounded-full mr-1">
                                    {{ folder.files_count }}
                                </span>
                            </div>
                        </div>

                        <!-- Existing children -->
                        <template v-if="folder.children && folder.children.length > 0">
                            <div v-for="(child, ci) in folder.children" :key="child.id"
                                class="relative group/child flex items-center w-full gap-0 pr-1 py-0 text-xs transition-colors"
                                :class="selectedFolderId === child.id ? 'bg-gray-200 text-teal-700' : 'text-gray-500 hover:bg-gray-50'">

                                <!-- Tree branch connector: ├── or └── -->
                                <div class="flex items-center shrink-0 w-5 h-full">
                                    <div class="relative w-full h-full flex items-center">
                                        <!-- Horizontal branch -->
                                        <div class="absolute left-0 top-1/2 w-3.5 h-px bg-gray-200"></div>
                                        <!-- Vertical extension for non-last items -->
                                        <div v-if="ci < (folder.children?.length ?? 0) - 1 || isCreatingInside(folder.id)"
                                            class="absolute left-0 top-1/2 w-px h-1/2 bg-gray-200"></div>
                                    </div>
                                </div>

                                <!-- Folder icon + name -->
                                <div class="flex items-center gap-2 flex-1 min-w-0 py-1.5 px-1 cursor-pointer rounded"
                                    @click="emit('select', child.id)"
                                    @dblclick.stop="startRename(child)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-3.5 shrink-0"
                                        :class="selectedFolderId === child.id ? 'text-teal-600' : 'text-amber-400'">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                    </svg>

                                    <!-- Rename mode -->
                                    <input v-if="renamingFolderId === child.id"
                                        ref="renameInput"
                                        v-model="renameValue"
                                        type="text"
                                        maxlength="150"
                                        class="flex-1 min-w-0 px-1.5 py-0.5 text-xs border border-teal-400 rounded outline-none bg-white focus:ring-1 focus:ring-teal-400"
                                        @blur="commitRename(child)"
                                        @keydown="handleRenameKeydown($event, child)"
                                        @click.stop />

                                    <span v-else class="truncate" :class="selectedFolderId === child.id ? 'font-semibold' : ''">
                                        {{ child.name }}
                                    </span>
                                </div>

                                <!-- Right side: badge (fixed width for alignment) -->
                                <div class="flex items-center justify-end w-10 shrink-0 mr-1">
                                    <span v-if="(child.files_count ?? 0) > 0 && renamingFolderId !== child.id"
                                        class="px-1.5 py-0.5 text-[10px] bg-gray-200 rounded-full group-hover/child:hidden">
                                        {{ child.files_count }}
                                    </span>
                                </div>

                                <!-- Hover actions -->
                                <div v-if="renamingFolderId !== child.id"
                                    class="absolute right-1 items-center hidden gap-0.5 group-hover/child:flex shrink-0">
                                    <button @click.stop="emit('delete', child)"
                                        class="p-0.5 rounded hover:bg-red-100 text-gray-400 hover:text-red-500 transition-colors"
                                        title="Delete folder">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-3">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Inline new subfolder input (with tree branch) -->
                        <div v-if="isCreatingInside(folder.id)"
                            class="relative flex items-center gap-2 pr-1 py-1">
                            <!-- └── branch connector -->
                            <div class="flex items-center shrink-0 w-5 h-full">
                                <div class="relative w-full h-full flex items-center">
                                    <div class="absolute left-0 top-1/2 w-3.5 h-px bg-gray-200"></div>
                                </div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="text-amber-400 size-3.5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                            </svg>
                            <input :ref="setNewFolderInputRef"
                                v-model="newFolderName"
                                type="text"
                                maxlength="150"
                                class="flex-1 min-w-0 px-1.5 py-0.5 text-xs border border-teal-400 rounded outline-none bg-white focus:ring-1 focus:ring-teal-400"
                                @blur="commitNewFolder"
                                @keydown="handleNewFolderKeydown" />
                        </div>
                    </div>
                </div>
            </template>

            <!-- Inline new root folder input -->
            <div v-if="isCreatingInside(null)"
                class="flex items-center gap-2 px-3 py-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="text-amber-500 size-4 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                </svg>
                <input :ref="setNewFolderInputRef"
                    v-model="newFolderName"
                    type="text"
                    maxlength="150"
                    class="flex-1 min-w-0 px-1.5 py-0.5 text-xs border border-teal-400 rounded outline-none bg-white focus:ring-1 focus:ring-teal-400"
                    @blur="commitNewFolder"
                    @keydown="handleNewFolderKeydown" />
            </div>

            <!-- Empty state -->
            <div v-if="!loading && folders.length === 0 && !isCreatingInside(null)"
                class="px-3 py-4 text-[10px] text-gray-400 text-center">
                No folders yet. Click + to create one.
            </div>
        </div>
    </div>
</template>
