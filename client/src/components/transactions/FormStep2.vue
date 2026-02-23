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
      <FrmLabel :label="'Base File'" />
      <div class="mb-3 text-xs text-gray-500">
        Primary document being encoded, serving as the original source from
        which information is entered or processed.
      </div>
      <div>
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
    <!-- Start Base File -->

    <!-- Start Attachments -->
    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
      <FrmLabel :label="'Attachments'" />
      <div class="mb-3 text-xs text-gray-500">
        Documents added to the base file, serving as supplementary materials or
        supporting information related to the primary document.
      </div>
      <div>
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
</template>
