<script setup lang="ts">
import { IoArrowForward } from 'vue-icons-plus/io';
import FrmLabel from '../ui/labels/FrmLabel.vue';
import LargeModal from '../ui/modals/LargeModal.vue';
import FileTaskHeader from '../uploader/FileTaskHeader.vue';
import FileTaskCard from '../uploader/FileTaskCard.vue';
import { LuUpload } from 'vue-icons-plus/lu';
import SearchMultiSelectOffice from '../ui/select/SearchMultiSelectOffice.vue';
import { useDocumentToReleaseDetails } from '@/composables/useDocumentToReleaseDetails';
import { useLibraryStore } from '@/stores/libraries';
import { onMounted, ref } from 'vue';
import { useUploader } from '@/composables/useUploader';
import SearchSelect from '../ui/select/SearchSelect.vue';
import Textarea from '../ui/textareas/Textarea.vue';
import BaseButton from '../ui/buttons/BaseButton.vue';
import Toggle from '../ui/switches/Toggle.vue';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleReleaseModal: { type: Function, default: null } //This is an function/method
});

const { documentToReleaseDetails, removeRecipient, setAttachments } =
    useDocumentToReleaseDetails();

const {
    documentTypeLibrary,
    actionLibrary,
    officeLibrary,
    loadDocumentTypeLibrary,
    loadActionLibrary,
    loadOfficeLibrary,
} = useLibraryStore();

onMounted(() => {
    loadDocumentTypeLibrary();
    loadActionLibrary();
    loadOfficeLibrary();
});

const attachmentFile = useUploader();
const onAttachmentInputChange = async (event: Event) => {
    const responses = await attachmentFile.handleFileInput(event);
    setAttachments(responses);
};

const onAttachmentInputDrop = async (event: DragEvent) => {
    const responses = await attachmentFile.handleDrop(event);
    setAttachments(responses);
};

const releaseDocument = () => { // handle release logic here 
    console.log('Document Released:', documentToReleaseDetails);
};

const documentNumber = ref(null);
</script>


<template>
    <LargeModal title="Release Document" :isOpen="props.isOpen" @close="props.toggleReleaseModal"
        @confirm="releaseDocument">
        <!-- Release Form -->
        <div class="">
            <form @submit.prevent="releaseDocument">
                <div class="max-h-[calc(100svh-15rem)] px-4 overflow-auto">
                    <div class="mt-5 mb-2">
                        <div class="flex">
                            <FrmLabel :label="'Action Taken'" class="mb-2" :isRequired="true" />
                        </div>
                        <SearchSelect v-model="documentToReleaseDetails.actionTaken" :items="actionLibrary.data ?? []"
                            placeholder="Select Action Type" :isLoading="actionLibrary.isLoading" />
                    </div>


                    <div class="mb-4">
                        <div class="flex items-center mb-2">
                            <FrmLabel :label="'Remarks'" />
                            <span class="text-[10px] italic ml-1 text-gray-500">
                                (Optional)
                            </span>
                        </div>
                        <Textarea v-model="documentToReleaseDetails.remarks" class="mb-2" />
                    </div>

                    <!-- Attachment Block -->
                    <!-- Start Attachments -->
                    <div class="mb-4">
                        <FrmLabel :label="'Attachments'" />
                        <div class="mb-3 text-xs text-gray-500">
                            Documents added to the base file, serving as supplementary materials
                            or supporting information related to the primary document.
                        </div>
                        <div>
                            <div v-if="attachmentFile.tasks.value.length > 0"
                                class="mt-4 bg-white border border-gray-200 rounded-lg overflow-clip dark:bg-gray-800">
                                <!-- Header -->
                                <FileTaskHeader :completedCount="attachmentFile.completedCount"
                                    :totalCount="attachmentFile.totalCount" />
                                <!-- End Header -->
                                <!-- Progressess Loop -->
                                <div class="max-h-[300px] divide-y divide-gray-200 overflow-y-auto">
                                    <FileTaskCard v-for="(task, i) in attachmentFile.orderedTasks.value" :key="i"
                                        :task="task" />
                                </div>
                            </div>
                            <input type="file" multiple class="hidden" id="dropzone-attachment-file"
                                @change="onAttachmentInputChange" accept=".png,.jpg,.pdf"
                                aria-label="Upload attachments" />
                            <label for="dropzone-attachment-file" @dragover.prevent @dragenter.prevent
                                @drop.prevent="onAttachmentInputDrop"
                                class="flex flex-col items-center justify-center w-full mt-2 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer h-28 bg-gray-50 hover:bg-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                                <div
                                    class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-500 dark:text-gray-400">
                                    <LuUpload class="mb-2 size-7" />
                                    <p class="mb-2 text-sm">
                                        <span class="font-semibold">Click to upload</span> or drag and
                                        drop
                                    </p>
                                    <!-- <p class="text-xs">SVG, PNG, JPG or GIF (MAX. 800x400px)</p> -->
                                </div>
                            </label>
                        </div>
                    </div>
                    <!-- End Attachments -->

                    <!-- Recipients block -->
                    <div class="mb-2">
                        <div class="flex justify-between mb-2">
                            <FrmLabel :label="'Release To'" isRequired="true" />
                        </div>
                        <SearchMultiSelectOffice v-if="
                            documentToReleaseDetails.isSendToMany ||
                            !documentToReleaseDetails.recipients.length
                        " v-model="documentToReleaseDetails.recipients" :items="officeLibrary.data ?? []" :placeholder="documentToReleaseDetails.recipients.length
                            ? documentToReleaseDetails.recipients.length +
                            ' recipients selected'
                            : 'Select Recipient'
                            " :isLoading="officeLibrary.isLoading" :hasDesc="true"
                            :isMultiple="documentToReleaseDetails.isSendToMany" />
                        <div v-if="
                            !documentToReleaseDetails.isSendToMany &&
                            documentToReleaseDetails.recipients.length
                        " class="mb-2">
                            <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                                <li v-if="documentToReleaseDetails.recipients.length === 0">
                                    <div class="p-3 text-xs text-center text-gray-600 bg-white">
                                        No recipients selected.
                                    </div>
                                </li>
                                <li v-else v-for="recipient in documentToReleaseDetails.recipients"
                                    :key="recipient.office_code"
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
                        <!-- <Toggle v-model="documentToReleaseDetails.isSendToMany" class="mt-2" :title="'Send to Many'" /> -->
                    </div>
                    <div v-if="documentToReleaseDetails.isSendToMany" class="mb-2">
                        <FrmLabel :label="'Recipient/s (' + documentToReleaseDetails.recipients.length + ')'
                            " class="mb-1" />
                        <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                            <li v-if="documentToReleaseDetails.recipients.length === 0">
                                <div class="p-3 text-xs text-center text-gray-600 bg-white">
                                    No recipients selected.
                                </div>
                            </li>
                            <li v-else v-for="recipient in documentToReleaseDetails.recipients"
                                :key="recipient.office_code"
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
                    <!-- Recipients block -->
                </div>
            </form>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton textColorClass="text-gray-700" backgroundClass="bg-gray-200 hover:bg-gray-300"
                    :action="toggleReleaseModal">
                    Cancel
                </BaseButton>
                <BaseButton @click="releaseDocument">
                    Release Document
                </BaseButton>
            </div>
        </template>
    </LargeModal>
</template>