<script setup lang="ts">
import { ref, watch } from 'vue';
import type { FileFolder } from '@/types/files';

const props = defineProps<{
    show: boolean;
    editFolder?: FileFolder | null;
    folders: FileFolder[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', data: { name: string; description: string; parentId: number | null }): void;
}>();

const name = ref('');
const description = ref('');
const parentId = ref<number | null>(null);

watch(() => props.show, (val) => {
    if (val && props.editFolder) {
        name.value = props.editFolder.name;
        description.value = props.editFolder.description || '';
        parentId.value = props.editFolder.parent_id;
    } else if (val) {
        name.value = '';
        description.value = '';
        parentId.value = null;
    }
});

function submit() {
    if (!name.value.trim()) return;
    emit('save', {
        name: name.value.trim(),
        description: description.value.trim(),
        parentId: parentId.value,
    });
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="emit('close')">
            <div class="w-full max-w-sm bg-white rounded-xl shadow-xl">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700">
                        {{ editFolder ? 'Rename Folder' : 'Create Folder' }}
                    </h3>
                    <button @click="emit('close')" class="p-1 text-gray-400 rounded-lg hover:bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 space-y-3">
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600">Folder name</label>
                        <input v-model="name" type="text" maxlength="150"
                            class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            placeholder="e.g. Memorandums" @keyup.enter="submit" />
                    </div>

                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600">Description (optional)</label>
                        <input v-model="description" type="text" maxlength="500"
                            class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            placeholder="Short description" />
                    </div>

                    <div v-if="!editFolder">
                        <label class="block mb-1 text-xs font-medium text-gray-600">Parent folder</label>
                        <select v-model="parentId"
                            class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option :value="null">None (root level)</option>
                            <option v-for="f in folders" :key="f.id" :value="f.id">{{ f.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-100">
                    <button @click="emit('close')"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button @click="submit" :disabled="!name.trim()"
                        class="px-4 py-2 text-xs font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50">
                        {{ editFolder ? 'Save' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
