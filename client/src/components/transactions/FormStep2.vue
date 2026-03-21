<script setup lang="ts">
import FrmLabel from "../ui/labels/FrmLabel.vue";
import { useStepNavigation } from "@/composables/useStepNavigation";
const { setStep } = useStepNavigation();
import { computed, ref } from "vue";
import FileTaskCard from "../uploader/FileTaskCard.vue";
import FileTaskHeader from "../uploader/FileTaskHeader.vue";
import BaseButton from "../ui/buttons/BaseButton.vue";
import { LuUpload } from "vue-icons-plus/lu";
import { useUploader } from "@/composables/useUploader";
import { useDocumentInformation } from "@/composables/useDocumentInformation";
import ReplyHeader from "./ReplyHeader.vue";
import FilePickerModal from "@/components/files/FilePickerModal.vue";
import type { OfficeFile } from "@/types/files";
import type { UploadedFile } from "@/types/document";

const { documentInformation, setFiles, setAttachments } =
  useDocumentInformation();

const baseFile = useUploader();
const attachmentFile = useUploader();

const onFileInputChange = async (event: Event) => {
  const responses = await baseFile.handleFileInput(event);
  // documentInformation.files = responses;
  setFiles(responses);
};

const onFileInputDrop = async (event: DragEvent) => {
  const responses = await baseFile.handleDrop(event);
  setFiles(responses);
  // documentInformation.files = responses;
};

const onAttachmentInputChange = async (event: Event) => {
  const responses = await attachmentFile.handleFileInput(event);
  setAttachments(responses);
};

const onAttachmentInputDrop = async (event: DragEvent) => {
  const responses = await attachmentFile.handleDrop(event);
  setAttachments(responses);
};

// ── File Picker (repository) ────────────────────────────────────────────────
const showBaseFilePicker = ref(false);
const showAttachmentFilePicker = ref(false);

function mapRepoFileToUploaded(file: OfficeFile): UploadedFile {
  return {
    name: file.original_name,
    size: formatFileSize(file.file_size),
    size_bytes: file.file_size,
    type: file.mime_type,
    temp_path: '',
    source: 'repository',
    office_file_id: file.id,
  };
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}

function onBaseFilesPicked(selected: OfficeFile[]) {
  const mapped = selected.map(mapRepoFileToUploaded);
  documentInformation.files = [...documentInformation.files, ...mapped];
  showBaseFilePicker.value = false;
}

function onAttachmentFilesPicked(selected: OfficeFile[]) {
  const mapped = selected.map(mapRepoFileToUploaded);
  documentInformation.attachments = [...documentInformation.attachments, ...mapped];
  showAttachmentFilePicker.value = false;
}

function removeRepoFile(list: 'files' | 'attachments', index: number) {
  documentInformation[list].splice(index, 1);
}
</script>

<template>
  <!-- Start Header -->
  <div class="flex items-center h-[50px] justify-between col-span-5 p-2 border-t border-gray-200 bg-gray-50">
    <!-- <h1 class="text-lg font-semibold text-gray-800">Document Overview</h1> -->
    <ReplyHeader />
    <div class="flex gap-2 ml-auto">
      <BaseButton :btn-text="'Back to previous step'" :action="() => setStep(1)"
        :className="'flex items-center ml-auto'">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-2 rotate-180 size-4">
          <path fill-rule="evenodd"
            d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm4.28 10.28a.75.75 0 0 0 0-1.06l-3-3a.75.75 0 1 0-1.06 1.06l1.72 1.72H8.25a.75.75 0 0 0 0 1.5h5.69l-1.72 1.72a.75.75 0 1 0 1.06 1.06l3-3Z"
            clip-rule="evenodd" />
        </svg>
      </BaseButton>
      <BaseButton :btn-text="'Proceed to next step'" :action="() => setStep(3)"
        :className="'flex items-center ml-auto'">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-2 size-4">
          <path fill-rule="evenodd"
            d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm4.28 10.28a.75.75 0 0 0 0-1.06l-3-3a.75.75 0 1 0-1.06 1.06l1.72 1.72H8.25a.75.75 0 0 0 0 1.5h5.69l-1.72 1.72a.75.75 0 1 0 1.06 1.06l3-3Z"
            clip-rule="evenodd" />
        </svg>
      </BaseButton>
    </div>
  </div>
  <!-- End Header -->
  <hr class="text-gray-200" />

  <!-- Start Content -->
  <div class="h-[calc(100vh-100px)] p-4 overflow-auto space-y-8">
    <!-- Start Base File -->
    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
      <div class="flex items-center justify-between mb-1">
        <FrmLabel :label="'Base File'" />
        <button @click="showBaseFilePicker = true"
          class="flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-teal-700 border border-teal-200 rounded-lg hover:bg-teal-50 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
          </svg>
          Pick from Files
        </button>
      </div>
      <div class="mb-3 text-xs text-gray-500">
        Primary document being encoded, serving as the original source from
        which information is entered or processed.
      </div>
      <div>
        <!-- Repository files already picked -->
        <div v-if="documentInformation.files.filter(f => f.source === 'repository').length > 0"
          class="mb-2 space-y-1.5">
          <div v-for="(file, i) in documentInformation.files" :key="'repo-base-' + i">
            <div v-if="file.source === 'repository'"
              class="flex items-center justify-between px-3 py-2 text-xs bg-teal-50 border border-teal-100 rounded-lg">
              <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-teal-600">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                </svg>
                <span class="text-teal-800">{{ file.name }}</span>
                <span class="text-teal-500">{{ file.size }}</span>
                <span class="px-1.5 py-0.5 text-[9px] bg-teal-200 text-teal-800 rounded-full">Repository</span>
              </div>
              <button @click="removeRepoFile('files', i)" class="p-0.5 text-red-400 hover:text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div v-if="baseFile.tasks.value.length > 0"
          class="mt-4 bg-white border border-gray-200 rounded-lg overflow-clip dark:bg-gray-800">
          <!-- Header -->
          <FileTaskHeader :completedCount="baseFile.completedCount" :totalCount="baseFile.totalCount" />
          <!-- End Header -->
          <!-- Progressess Loop -->
          <div class="max-h-[300px] divide-y divide-gray-200 overflow-y-auto">
            <FileTaskCard v-for="(task, i) in baseFile.orderedTasks.value" :key="i" :task="task" />
          </div>
        </div>
        <input type="file" multiple class="hidden" id="dropzone-base-file" @change="onFileInputChange"
          accept=".png,.jpg,.pdf" />
        <label for="dropzone-base-file" @dragover.prevent @dragenter.prevent @drop.prevent="onFileInputDrop"
          class="flex flex-col items-center justify-center w-full mt-2 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer h-28 bg-gray-50 hover:bg-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
          <div class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-500 dark:text-gray-400">
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
    <!-- End Base File -->

    <!-- Start Attachments -->
    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
      <div class="flex items-center justify-between mb-1">
        <FrmLabel :label="'Attachments'" />
        <button @click="showAttachmentFilePicker = true"
          class="flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-teal-700 border border-teal-200 rounded-lg hover:bg-teal-50 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
          </svg>
          Pick from Files
        </button>
      </div>
      <div class="mb-3 text-xs text-gray-500">
        Documents added to the base file, serving as supplementary materials or
        supporting information related to the primary document.
      </div>
      <div>
        <!-- Repository files already picked -->
        <div v-if="documentInformation.attachments.filter(f => f.source === 'repository').length > 0"
          class="mb-2 space-y-1.5">
          <div v-for="(file, i) in documentInformation.attachments" :key="'repo-att-' + i">
            <div v-if="file.source === 'repository'"
              class="flex items-center justify-between px-3 py-2 text-xs bg-teal-50 border border-teal-100 rounded-lg">
              <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-teal-600">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                </svg>
                <span class="text-teal-800">{{ file.name }}</span>
                <span class="text-teal-500">{{ file.size }}</span>
                <span class="px-1.5 py-0.5 text-[9px] bg-teal-200 text-teal-800 rounded-full">Repository</span>
              </div>
              <button @click="removeRepoFile('attachments', i)" class="p-0.5 text-red-400 hover:text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div v-if="attachmentFile.tasks.value.length > 0"
          class="mt-4 bg-white border border-gray-200 rounded-lg overflow-clip dark:bg-gray-800">
          <!-- Header -->
          <FileTaskHeader :completedCount="attachmentFile.completedCount" :totalCount="attachmentFile.totalCount" />
          <!-- End Header -->
          <!-- Progressess Loop -->
          <div class="max-h-[300px] divide-y divide-gray-200 overflow-y-auto">
            <FileTaskCard v-for="(task, i) in attachmentFile.orderedTasks.value" :key="i" :task="task" />
          </div>
        </div>
        <input type="file" multiple class="hidden" id="dropzone-attachment-file" @change="onAttachmentInputChange"
          accept=".png,.jpg,.pdf" />
        <label for="dropzone-attachment-file" @dragover.prevent @dragenter.prevent @drop.prevent="onAttachmentInputDrop"
          class="flex flex-col items-center justify-center w-full mt-2 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer h-28 bg-gray-50 hover:bg-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
          <div class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-500 dark:text-gray-400">
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
  </div>
  <!-- End Content -->

  <!-- File Picker Modals -->
  <FilePickerModal :show="showBaseFilePicker" @close="showBaseFilePicker = false" @select="onBaseFilesPicked" />
  <FilePickerModal :show="showAttachmentFilePicker" @close="showAttachmentFilePicker = false" @select="onAttachmentFilesPicked" />
</template>
