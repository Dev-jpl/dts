<script setup lang="ts">
import FrmInput from "@/components/ui/inputs/FrmInput.vue";
import FrmLabel from "@/components/ui/labels/FrmLabel.vue";
import { useLibraryStore } from "@/stores/libraries";
import { computed, onMounted, ref } from "vue";
import { IoArrowForward } from "vue-icons-plus/io";
import { useDocumentToReleaseDetails } from "@/composables/useDocumentToReleaseDetails";
import SearchSelect from "@/components/ui/select/SearchSelect.vue";
import Textarea from "@/components/ui/textareas/Textarea.vue";
import SearchMultiSelectOffice from "@/components/ui/select/SearchMultiSelectOffice.vue";
import { useUploader } from "@/composables/useUploader";
import FileTaskCard from "@/components/uploader/FileTaskCard.vue";
import FileTaskHeader from "@/components/uploader/FileTaskHeader.vue";
import { LuUpload } from "vue-icons-plus/lu";
const { documentToReleaseDetails, removeRecipient, setAttachments } =
  useDocumentToReleaseDetails();
const documentNumber = ref(null);

const receive = () => {
  alert("Document has been received successfully");
};

const recentlyReceived = ref([
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00192",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00193",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00194",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00195",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00196",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
]);

const maxSubjectLength = 140;
const expandedStates = ref<Record<string, boolean>>({});

const isExpanded = (id: string) => expandedStates.value[id] === true;
const toggleExpanded = (id: string) => {
  expandedStates.value[id] = !expandedStates.value[id];
};

const getSubjectDisplay = (doc: (typeof recentlyReceived.value)[0]) => {
  const isOpen = isExpanded(doc.documentNumber); // ✅ using documentNumber
  return isOpen || doc.subject.length <= maxSubjectLength
    ? doc.subject
    : doc.subject.slice(0, maxSubjectLength) + "…";
};

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
</script>

<template>
  <div
    class="flex flex-col items-center h-[calc(100svh-50px)] overflow-auto bg-gradient-to-b from-gray-100 to-gray-200"
  >
    <div class="mt-[5rem] w-full max-w-xl">
      <h1 class="mb-2 text-2xl font-bold text-center">Release Document</h1>
      <p class="text-xs text-center text-gray-400">
        Please place the cursor in the input field, then either scan the QR code
        attached <br />
        to your document or manually enter the document number you wish to
        release.
      </p>
      <form @submit.prevent="receive">
        <div class="relative w-full mt-10">
          <input
            v-model="documentNumber"
            type="text"
            placeholder="Document No"
            class="w-full px-4 py-3 text-sm transition bg-white border border-gray-200 rounded-lg shadow-inner focus:outline-none focus:ring-2 focus:ring-lime-600"
          />
          <button
            type="submit"
            class="absolute flex items-center gap-1 px-3 py-2 text-xs font-semibold text-white transition -translate-y-1/2 rounded-lg shadow-2xl right-2 top-1/2 group hover:cursor-pointer bg-amber-600 hover:bg-lime-500"
          >
            Release <IoArrowForward class="transition-all size-4" />
            <!-- <span>Track</span> -->
          </button>
        </div>

        <div class="mt-5 mb-2">
          <div class="flex">
            <FrmLabel
              :label="'Action Taken'"
              class="mb-2"
              :isRequired="true"
            />
          </div>
          <SearchSelect
            v-model="documentToReleaseDetails.actionTaken"
            :items="actionLibrary.data ?? []"
            placeholder="Select Action Type"
            :isLoading="actionLibrary.isLoading"
          />
        </div>
        <div class="mb-4">
          <div class="flex items-center mb-2">
            <FrmLabel :label="'Remarks'" />
            <span class="text-[10px] italic ml-1 text-gray-500">
              (Optional)
            </span>
          </div>
          <Textarea
            v-model="documentToReleaseDetails.remarks"
            class="mb-2"
          />
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
            <div
              v-if="attachmentFile.tasks.value.length > 0"
              class="mt-4 bg-white border border-gray-200 rounded-lg overflow-clip dark:bg-gray-800"
            >
              <!-- Header -->
              <FileTaskHeader
                :completedCount="attachmentFile.completedCount"
                :totalCount="attachmentFile.totalCount"
              />
              <!-- End Header -->
              <!-- Progressess Loop -->
              <div
                class="max-h-[300px] divide-y divide-gray-200 overflow-y-auto"
              >
                <FileTaskCard
                  v-for="(task, i) in attachmentFile.orderedTasks.value"
                  :key="i"
                  :task="task"
                />
              </div>
            </div>
            <input
              type="file"
              multiple
              class="hidden"
              id="dropzone-attachment-file"
              @change="onAttachmentInputChange"
              accept=".png,.jpg,.pdf"
            />
            <label
              for="dropzone-attachment-file"
              @dragover.prevent
              @dragenter.prevent
              @drop.prevent="onAttachmentInputDrop"
              class="flex flex-col items-center justify-center w-full mt-2 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer h-28 bg-gray-50 hover:bg-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600"
            >
              <div
                class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-500 dark:text-gray-400"
              >
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
            <FrmLabel
              :label="'Release To'"
              :is-required="true"
            />
          </div>
          <SearchMultiSelectOffice
            v-if="
              documentToReleaseDetails.isSendToMany ||
              !documentToReleaseDetails.recipients.length
            "
            v-model="documentToReleaseDetails.recipients"
            :items="officeLibrary.data ?? []"
            :placeholder="
              documentToReleaseDetails.recipients.length
                ? documentToReleaseDetails.recipients.length +
                  ' recipients selected'
                : 'Select Recipient'
            "
            :isLoading="officeLibrary.isLoading"
            :has-desc="true"
            :is-multiple="documentToReleaseDetails.isSendToMany"
          />
          <div
            v-if="
              !documentToReleaseDetails.isSendToMany &&
              documentToReleaseDetails.recipients.length
            "
            class="mb-2"
          >
            <ul
              class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip"
            >
              <li v-if="documentToReleaseDetails.recipients.length === 0">
                <div class="p-3 text-xs text-center text-gray-600 bg-white">
                  No recipients selected.
                </div>
              </li>
              <li
                v-else
                v-for="recipient in documentToReleaseDetails.recipients"
                :key="recipient.office_code"
                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white"
              >
                <div class="col-span-10">
                  <div
                    style="font-size: 10px"
                    class="text-gray-400"
                  >
                    {{ recipient.region }} - {{ recipient.service }}
                  </div>
                  {{ recipient.office }}
                </div>
                <div class="flex items-center justify-end col-span-2">
                  <button
                    type="button"
                    @click="removeRecipient(recipient)"
                    class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100 hover:underline"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke-width="1.5"
                      stroke="currentColor"
                      class="size-4"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                      />
                    </svg>
                  </button>
                </div>
              </li>
            </ul>
          </div>
          <Toggle
            v-model="documentToReleaseDetails.isSendToMany"
            class="mt-2"
            :title="'Send to Many'"
          />
        </div>
        <div
          v-if="documentToReleaseDetails.isSendToMany"
          class="mb-2"
        >
          <FrmLabel
            :label="
              'Recipient/s (' + documentToReleaseDetails.recipients.length + ')'
            "
            class="mb-1"
          />
          <ul
            class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip"
          >
            <li v-if="documentToReleaseDetails.recipients.length === 0">
              <div class="p-3 text-xs text-center text-gray-600 bg-white">
                No recipients selected.
              </div>
            </li>
            <li
              v-else
              v-for="recipient in documentToReleaseDetails.recipients"
              :key="recipient.office_code"
              class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white"
            >
              <div class="col-span-10">
                <div
                  style="font-size: 10px"
                  class="text-gray-400"
                >
                  {{ recipient.region }} - {{ recipient.service }}
                </div>
                {{ recipient.office }}
              </div>
              <div class="flex items-center justify-end col-span-2">
                <button
                  type="button"
                  @click="removeRecipient(recipient)"
                  class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100 hover:underline"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="size-4"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                    />
                  </svg>
                </button>
              </div>
            </li>
          </ul>
        </div>
        <!-- Recipients block -->
      </form>
    </div>

    <!-- Recently -->
    <div class="flex flex-col max-w-xl mt-10 over">
      <FrmLabel
        :label="'Recently Released Documents'"
        class="mb-2"
      />
      <ul class="overflow-auto divide-y divide-gray-300">
        <li
          v-for="doc in recentlyReceived"
          class="p-2"
        >
          <div>
            <!-- Head -->
            <div>
              <div class="flex justify-between">
                <h1 class="text-sm font-bold">{{ doc.documentNumber }}</h1>
                <span class="text-[10px] text-gray-400">{{
                  doc.dateTimeStamp
                }}</span>
              </div>
              <p class="text-xs font-medium">{{ doc.documentType }}</p>
            </div>
            <!-- Content -->
            <div class="mt-2">
              <p class="text-xs font-light">
                <span class="font-medium">From: </span> {{ doc.origin }}
              </p>
              <!-- <p class="text-xs font-light">Subject: {{ doc.subject }}</p> -->
              <p class="text-xs font-light">
                <span class="font-medium"> Subject:</span>
                {{ getSubjectDisplay(doc) }}
                <span
                  v-if="doc.subject.length > maxSubjectLength"
                  class="ml-1 text-teal-700 cursor-pointer hover:underline"
                  @click="toggleExpanded(doc.documentNumber)"
                >
                  {{ isExpanded(doc.documentNumber) ? "See less" : "See more" }}
                </span>
              </p>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
