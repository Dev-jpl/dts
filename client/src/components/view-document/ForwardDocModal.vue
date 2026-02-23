<script setup lang="ts">
import { ref } from 'vue';
import ModalDialog from '../ui/modals/ModalDialog.vue';
import Textarea from '../ui/textareas/Textarea.vue';
import LargeModal from '../ui/modals/LargeModal.vue';
import BaseButton from '../ui/buttons/BaseButton.vue';
import FrmLabel from '../ui/labels/FrmLabel.vue';
import SearchMultiSelectOffice from '../ui/select/SearchMultiSelectOffice.vue';
import { useLibraryStore } from '@/stores/libraries';
import Toggle from '../ui/switches/Toggle.vue';
const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleForwardModal: { type: Function, default: null } //This is an function/method
});

const {
    documentTypeLibrary,
    actionLibrary,
    officeLibrary,
    loadDocumentTypeLibrary,
    loadActionLibrary,
    loadOfficeLibrary,
} = useLibraryStore();

const recipients = ref<Array<any>>([]);
const cc_recipients = ref<Array<any>>([]);
const bcc_recipients = ref<Array<any>>([]);
const isSendToMany = ref<boolean>(false);

const removeRecipient = (recipient: any) => {
    const index = recipients.findIndex(
        (r: any) => r.office_code === recipient.office_code
    );
    if (index !== -1) {
        recipients.splice(index, 1);
    }
};
</script>
<template>
    <LargeModal title="Forward Document" :isOpen="props.isOpen" @close="props.toggleForwardModal"
        @confirm="props.toggleForwardModal">

        <!-- Are you sure you want to forward this document? -->
        <div class="flex max-h-[calc(100svh-15rem)] overflow-auto flex-col p-4 space-y-3">
            <!-- <Toggle v-model="isSendToMany" class="mt-2" :title="'Send to Many'" /> -->

            <!-- Recipients block -->
            <div class="p-4 border border-gray-200 rounded-md bg-gray-50">
                <div class="mb-2">
                    <div class="flex justify-between mb-2">
                        <FrmLabel :label="'Forward To'" :isRequired="true" />
                    </div>
                    <SearchMultiSelectOffice v-if="
                        isSendToMany ||
                        !recipients.length
                    " v-model="recipients" :items="officeLibrary.data ?? []" :placeholder="recipients.length
                        ? recipients.length +
                        ' recipient/s selected'
                        : 'Select Recipient'
                        " :isLoading="officeLibrary.isLoading" :hasDesc="true" :isMultiple="isSendToMany" />
                    <div v-if="
                        !isSendToMany &&
                        recipients.length
                    " class="mb-2">
                        <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                            <li v-if="recipients.length === 0">
                                <div class="p-3 text-xs text-center text-gray-600 bg-white">
                                    No recipients selected.
                                </div>
                            </li>
                            <li v-else v-for="recipient in recipients" :key="recipient.office_code"
                                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                                <div class="col-span-10">
                                    <div style="font-size: 10px" class="text-gray-400">
                                        {{ recipient.region }} - {{ recipient.service }}
                                    </div>
                                    {{ recipient.office }}
                                </div>
                                <div class="flex items-center justify-end col-span-2">
                                    <button type="button" @click="removeRecipient(recipient)"
                                        class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100 hover:underline">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <Toggle v-model="isSendToMany" class="mt-2" :title="'Send to Many'" />
                </div>
                <div v-if="isSendToMany">
                    <FrmLabel :label="'Recipient/s (' + recipients.length + ')'
                        " class="mb-1" />
                    <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                        <li v-if="recipients.length === 0">
                            <div class="p-3 text-xs text-center text-gray-600 bg-white">
                                No recipients selected.
                            </div>
                        </li>
                        <li v-else v-for="recipient in recipients" :key="recipient.office_code"
                            class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                            <div class="col-span-10">
                                <div style="font-size: 10px" class="text-gray-400">
                                    {{ recipient.region }} - {{ recipient.service }}
                                </div>
                                {{ recipient.office }}
                            </div>
                            <div class="flex items-center justify-end col-span-2">
                                <button type="button" @click="removeRecipient(recipient)"
                                    class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100 hover:underline">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Recipients block -->

            <!-- CC block -->
            <div class="p-4 border border-gray-200 rounded-md bg-gray-50">
                <div class="mb-2">
                    <div class="flex justify-between mb-2">
                        <FrmLabel :label="'Cc'" />
                    </div>
                    <SearchMultiSelectOffice v-model="cc_recipients" :items="officeLibrary.data ?? []" :placeholder="cc_recipients.length
                        ? cc_recipients.length +
                        ' recipient/s selected'
                        : 'Select Recipient'
                        " :isLoading="officeLibrary.isLoading" :hasDesc="true" :isMultiple="true" />
                    <div v-if="cc_recipients.length > 0" class="my-2">
                        <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                            <li v-if="cc_recipients.length === 0">
                                <div class="p-3 text-xs text-center text-gray-600 bg-white">
                                    No recipients selected.
                                </div>
                            </li>
                            <li v-else v-for="recipient in cc_recipients" :key="recipient.office_code"
                                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                                <div class="col-span-10">
                                    <div style="font-size: 10px" class="text-gray-400">
                                        {{ recipient.region }} - {{ recipient.service }}
                                    </div>
                                    {{ recipient.office }}
                                </div>
                                <div class="flex items-center justify-end col-span-2">
                                    <button type="button" @click="removeRecipient(recipient)"
                                        class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100 hover:underline">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
            <!-- CC block -->

            <!-- Bcc block -->
            <div class="p-4 border border-gray-200 rounded-md bg-gray-50">
                <div class="mb-2">
                    <div class="flex justify-between mb-2">
                        <FrmLabel :label="'Bcc'" />
                    </div>
                    <SearchMultiSelectOffice v-model="bcc_recipients" :items="officeLibrary.data ?? []" :placeholder="bcc_recipients.length
                        ? bcc_recipients.length +
                        ' recipient/s selected'
                        : 'Select Recipient'
                        " :isLoading="officeLibrary.isLoading" :hasDesc="true" :isMultiple="true" />
                    <div v-if="bcc_recipients.length > 0" class="my-2">
                        <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                            <li v-if="bcc_recipients.length === 0">
                                <div class="p-3 text-xs text-center text-gray-600 bg-white">
                                    No recipients selected.
                                </div>
                            </li>
                            <li v-else v-for="recipient in bcc_recipients" :key="recipient.office_code"
                                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                                <div class="col-span-10">
                                    <div style="font-size: 10px" class="text-gray-400">
                                        {{ recipient.region }} - {{ recipient.service }}
                                    </div>
                                    {{ recipient.office }}
                                </div>
                                <div class="flex items-center justify-end col-span-2">
                                    <button type="button" @click="removeRecipient(recipient)"
                                        class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100 hover:underline">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Bcc block -->
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton @click="props.toggleForwardModal" backgroundClass="bg-gray-200" textColorClass="text-black">
                    Cancel
                </BaseButton>
                <BaseButton @click="props.toggleForwardModal" class="px-4 py-2 text-white bg-red-600 rounded">
                    Forward Document
                </BaseButton>
            </div>
        </template>
    </LargeModal>
</template>