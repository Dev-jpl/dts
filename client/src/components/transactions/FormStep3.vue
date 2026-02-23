<script setup lang="ts">
import BaseButton from "../ui/buttons/BaseButton.vue";
import { useStepNavigation } from "@/composables/useStepNavigation";

const { currentStep, setStep, meta_data } = useStepNavigation();

import { useDocumentInformation } from "@/composables/useDocumentInformation"; // adjust path if needed
import { computed } from "vue";
import FileTaskCard from "../uploader/FileTaskCard.vue";
import { Fa6FilePdf, Fa6RegCopy } from "vue-icons-plus/fa6";
import { BsFileEarmarkPdf } from "vue-icons-plus/bs";
import FrmLabel from "../ui/labels/FrmLabel.vue";
import OriginBadge from "../ui/badges/OriginBadge.vue";
import ReplyHeader from "./ReplyHeader.vue";
import { useTransaction } from "@/composables/useTransaction";

const { documentInformation, submitDocument } = useDocumentInformation();


const formattedDate = computed(() =>
  new Date(documentInformation.date).toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })
);

const originBg = (originType: string): string => {
  switch (originType) {
    case "Internal":
      return "bg-lime-600";
    case "External":
      return "bg-amber-600";
    case "Email":
      return "bg-sky-500";

    default:
      return "bg-lime-200";
  }
};

const { transaction } = useTransaction();

const proceedToNext = async () => {
  console.log("====================================");
  console.log("test");
  console.log("====================================");

  if (confirm("Are you sure? This action will generate a document number, and release your document to the listed recipients")) {
    try {
      const { data } = await submitDocument();
      if (data.success) {
        meta_data.value = data.data;
      }

      alert("Success!");

      //Set Step 4 Form
      setStep(4);
    } catch (error) {
      alert(error)
    }
  }
};
</script>

<template>
  <div class="flex-1 w-full h-full bg-white">
    <div class="flex items-center h-[50px] justify-between p-2 border-t border-gray-200 bg-gray-50">
      <!-- <h1 class="text-lg font-semibold text-gray-800">Document Overview</h1> -->
      <ReplyHeader />
      <div class="flex gap-2 ml-auto">
        <BaseButton :btn-text="'Back to previous step'" :action="() => setStep(2)"
          :className="'flex items-center ml-auto'">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
            class="mr-2 rotate-180 size-4">
            <path fill-rule="evenodd"
              d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm4.28 10.28a.75.75 0 0 0 0-1.06l-3-3a.75.75 0 1 0-1.06 1.06l1.72 1.72H8.25a.75.75 0 0 0 0 1.5h5.69l-1.72 1.72a.75.75 0 1 0 1.06 1.06l3-3Z"
              clip-rule="evenodd" />
          </svg>
        </BaseButton>
        <BaseButton :btn-text="'Proceed to next step'" :action="() => proceedToNext()"
          :className="'flex items-center ml-auto'">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-2 size-4">
            <path fill-rule="evenodd"
              d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm4.28 10.28a.75.75 0 0 0 0-1.06l-3-3a.75.75 0 1 0-1.06 1.06l1.72 1.72H8.25a.75.75 0 0 0 0 1.5h5.69l-1.72 1.72a.75.75 0 1 0 1.06 1.06l3-3Z"
              clip-rule="evenodd" />
          </svg>
        </BaseButton>
      </div>
    </div>
    <hr class="text-gray-200" />
    <div class="flex flex-col h-[calc(100svh-100px)] overflow-y-auto p-4">
      <!-- <h2 class="mb-2 text-lg font-semibold">Document Overview</h2> -->
      <div class="p-4 dark:bg-gray-800 dark:text-white">
        <div>
          <div class="space-y-3">
            <!-- <h1 class="mb-2 font-bold ">Document Informaiton</h1> -->
            <!-- Document Information Header Start -->
            <div class="p-5 text-white bg-teal-800 rounded-lg">
              <div class="flex items-center justify-between">
                <!-- Document No. -->
                <div class="flex-row sm:flex-col">
                  <div class="flex-row items-center font-bold sm:flex">
                    Document Number:
                    <span class="flex items-center font-bold sm:ml-3 text-lime-200">
                      DA-CO-TO-123-XXXXXX
                    </span>
                  </div>
                </div>
                <!-- Document status -->
                <div class="flex items-center">
                  <!-- <span
                      class="bg-gray-400 text-[10px] px-2 py-1 uppercase font-bold rounded-full"
                      >Draft</span
                    > -->

                  <div class="text-[10px] text-gray-300 mr-2">Status:</div>
                  <span
                    class="px-2 bg-gray-400 border shadow-2xl py-1 text-white uppercase text-[9px] font-black rounded-full">
                    Draft
                  </span>
                </div>
              </div>

              <hr class="my-4 text-teal-700" />

              <div class="grid grid-flow-row gap-4 sm:grid-flow-col sm:grid-cols-12">
                <div class="col-span-2">
                  <div class="text-[10px] text-gray-300">Date</div>
                  <div class="text-xs font-medium">
                    {{ formattedDate }}
                  </div>
                </div>
                <div class="col-span-4">
                  <div class="text-[10px] text-gray-300">Document Type</div>
                  <div class="text-xs font-medium">
                    {{ documentInformation.documentType.type }}
                  </div>
                </div>
                <div class="col-span-4">
                  <div class="text-[10px] text-gray-300">Action Taken</div>
                  <div class="text-xs font-medium">
                    {{ documentInformation.actionTaken.action }}
                  </div>
                </div>
                <div class="col-span-2">
                  <div class="text-[10px] text-gray-300">Origin Type</div>
                  <OriginBadge :origin-type="documentInformation.originType" />
                </div>
              </div>
            </div>
            <!-- Document Information Header End -->

            <!-- Alert card for external documents -->
            <!-- External Document Start -->
            <div v-if="documentInformation.originType === 'External'"
              class="p-2 bg-orange-100 border border-dashed rounded-lg border-amber-600">
              <h3 class="text-xs font-bold text-amber-600">
                External Document
              </h3>
              <p class="text-xs text-gray-600">
                This document is from an external source and delivered by sender
                <span class="font-bold">{{ documentInformation.sender }}</span> a
                <span class="font-bold">{{ documentInformation.sender_position }}</span>
                of
                <span class="font-bold">{{ documentInformation.sender_office }}</span>
              </p>
            </div>
            <!-- External Document End -->

            <!-- Email Document Start -->
            <div v-if="documentInformation.originType === 'Email'"
              class="p-2 border border-dashed rounded-lg bg-sky-100 border-sky-600">
              <h3 class="text-xs font-bold text-sky-600">Emailed Document</h3>
              <p class="text-xs text-gray-600">
                This document is from an external source and was sent through
                email via <span class="font-bold">{{ documentInformation.sender_email }}</span>
              </p>
            </div>
            <!-- Email Document End -->

            <!-- Document Origin -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 divide-white rounded-lg overflow-clip divide-x-5 bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="From:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <div class="text-xs">
                  Systems Application Development Division
                </div>
              </div>
            </div>
            <!-- Document Origin End -->

            <!-- Document Recipients -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 divide-white rounded-lg overflow-clip divide-x-5 bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="To:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <span v-if="documentInformation.recipients?.length > 1" class="py-2 text-xs">
                  {{
                    `${documentInformation.recipients[0].service}-${documentInformation.recipients[0].office
                    }... and ${documentInformation.recipients.length - 1
                    } more office/s`
                  }}
                </span>
                <ul v-else class="grid">
                  <li class="py-2 text-xs" v-for="recipient in documentInformation.recipients">
                    {{ `${recipient.service}-${recipient.office}` }}
                  </li>
                </ul>
              </div>
            </div>
            <!-- Document Recipients End -->

            <!-- Document Subject Start-->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 divide-white rounded-lg overflow-clip divide-x-5 bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Subject:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <p class="text-xs font-light">
                  {{ documentInformation.subject }}
                </p>
              </div>
            </div>
            <!-- Document Subject End -->

            <!-- Document Remarks Start -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 divide-white rounded-lg overflow-clip divide-x-5 bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Remarks:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <p class="text-xs font-light">
                  {{ documentInformation.remarks }}
                </p>
              </div>
            </div>
            <!-- Document Remarks End -->

            <!-- Document Signatories Start -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 divide-white rounded-lg overflow-clip divide-x-5 bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Signatories:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <ul class="space-y-4">
                  <li>
                    <!-- Signatory Name -->
                    <div class="text-xs font-medium">Oyie Mahomie</div>
                    <!-- Signatory Designation and Office -->
                    <div class="text-xs font-light text-gray-600">
                      Director, Information Communications Technology Service
                    </div>
                  </li>
                  <li>
                    <!-- Signatory Name -->
                    <div class="text-xs font-medium">Camilo A. Andi Jr.</div>
                    <!-- Signatory Designation and Office -->
                    <div class="text-xs font-light text-gray-600">
                      Chief, Systems Application Development Division
                    </div>
                  </li>
                </ul>
              </div>
            </div>
            <!-- Document Signatories End -->

            <!-- Document Attachments Start -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 divide-white rounded-lg overflow-clip divide-x-5 bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Attachments:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <div class="grid items-stretch grid-flow-row gap-5">
                  <div class="h-full col-span-1">
                    <FrmLabel label="Main File:" class="mb-2" />
                    <ul class="space-y-1">
                      <li v-if="documentInformation.files.length === 0"
                        class="p-2 bg-gray-100 border border-gray-200 rounded-lg">
                        <div class="text-xs text-center">No file uploaded</div>
                      </li>
                      <li v-else v-for="file in documentInformation.files"
                        class="flex items-center p-2 bg-gray-100 border border-gray-200 rounded-lg">
                        <BsFileEarmarkPdf class="mr-2 text-red-500" />
                        <div>
                          <div class="text-xs font-semibold">
                            <a :href="file.url" target="_blank">
                              {{ file.name }}
                            </a>
                          </div>
                          <div class="text-[10px] text-gray-400">
                            {{ file.size }}
                          </div>
                        </div>
                      </li>
                    </ul>
                  </div>
                  <div class="h-full col-span-1">
                    <FrmLabel label="Attached File:" class="mb-2" />
                    <ul class="space-y-1">
                      <li v-if="documentInformation.attachments.length === 0"
                        class="p-2 bg-gray-100 border border-gray-200 rounded-lg">
                        <div class="text-xs text-center">No file uploaded</div>
                      </li>
                      <li v-else v-for="attachment in documentInformation.attachments"
                        class="flex items-center p-2 bg-gray-100 border border-gray-200 rounded-lg">
                        <BsFileEarmarkPdf class="mr-2 text-red-500" />
                        <div>
                          <div class="text-xs font-semibold">
                            <a :href="attachment.url" target="_blank">
                              {{ attachment.name }}
                            </a>
                          </div>
                          <div class="text-[10px] text-gray-400">
                            {{ attachment.size }}
                          </div>
                        </div>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <!-- Document Signatories End -->

            <div class="hidden">
              <!-- Document Information Content Start -->
              <div class="grid sm:grid-cols-12 gap-7">
                <!-- Document Information -->
                <div class="space-y-6 sm:col-span-7">
                  <!--Start Subject Block -->
                  <div class="relative bg-white border-gray-300 rounded-lg">
                    <h1 class="mb-2 text-xs font-bold bg-white -top-5 left-2">
                      Subject
                    </h1>

                    <div class="text-xs font-light">
                      {{
                        documentInformation.subject
                          ? documentInformation.subject
                          : "N/A"
                      }}
                    </div>
                  </div>
                  <!--End Subject Block -->

                  <!--Start Remarks Block -->
                  <div class="relative bg-white border-gray-300 rounded-lg">
                    <h1 class="mb-2 text-xs font-bold bg-white -top-5 left-2">
                      Remarks
                    </h1>

                    <div class="text-xs font-light">
                      {{
                        documentInformation.remarks
                          ? documentInformation.remarks
                          : "N/A"
                      }}
                    </div>
                  </div>
                  <!--Start Remarks Block -->

                  <!--Start Signatories Block -->
                  <div class="relative bg-white border-gray-300 rounded-lg">
                    <h1 class="mb-2 text-xs font-bold bg-white -top-5 left-2">
                      Signatories
                    </h1>

                    <div class="text-xs font-light"></div>
                  </div>
                </div>

                <div class="relative bg-white border-gray-300 rounded-lg sm:col-span-5">
                  <h1 class="mb-2 text-xs font-bold bg-white -top-5 left-2">
                    Attachments
                  </h1>

                  <div class="grid items-stretch grid-flow-row gap-5">
                    <div class="h-full col-span-1">
                      <h1 class="mb-2 text-xs font-bold bg-white">Main File</h1>
                      <ul class="space-y-1">
                        <li v-if="documentInformation.files.length === 0"
                          class="p-2 bg-gray-100 border border-gray-200 rounded-lg">
                          <div class="text-xs text-center">
                            No file uploaded
                          </div>
                        </li>
                        <li v-else v-for="file in documentInformation.files"
                          class="flex items-center p-2 bg-gray-100 border border-gray-200 rounded-lg">
                          <BsFileEarmarkPdf class="mr-2 text-red-500" />
                          <div>
                            <div class="text-xs font-semibold">
                              <a :href="file.url" target="_blank">
                                {{ file.name }}
                              </a>
                            </div>
                            <div class="text-[10px] text-gray-400">
                              {{ file.size }}
                            </div>
                          </div>
                        </li>
                      </ul>
                    </div>
                    <div class="h-full col-span-1">
                      <h1 class="mb-2 text-xs font-bold bg-white">
                        Attachment File
                      </h1>
                      <ul class="space-y-1">
                        <li v-if="documentInformation.attachments.length === 0"
                          class="p-2 bg-gray-100 border border-gray-200 rounded-lg">
                          <div class="text-xs text-center">
                            No file uploaded
                          </div>
                        </li>
                        <li v-else v-for="attachment in documentInformation.attachments"
                          class="flex items-center p-2 bg-gray-100 border border-gray-200 rounded-lg">
                          <BsFileEarmarkPdf class="mr-2 text-red-500" />
                          <div>
                            <div class="text-xs font-semibold">
                              <a :href="attachment.url" target="_blank">
                                {{ attachment.name }}
                              </a>
                            </div>
                            <div class="text-[10px] text-gray-400">
                              {{ attachment.size }}
                            </div>
                          </div>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <!-- Document Information Content End -->
              </div>

              <hr class="my-4 text-gray-200" />
              <h1 class="my-4 font-bold bg-white">Receiver's Informaiton</h1>
              <!-- Receivers Information Content Start -->
              <div class="grid grid-cols-12">
                <div class="col-span-12">
                  <div class="relative bg-white border-gray-300 rounded-lg">
                    <h1 class="mb-2 text-xs font-bold bg-white -top-5 left-2">
                      Recipients
                    </h1>
                    <ul
                      class="hidden border border-gray-200 divide-gray-200 rounded-lg md:block divide-y-1 overflow-clip">
                      <li class="grid grid-cols-12 p-2 bg-gray-100 border-b">
                        <div class="col-span-2 text-xs font-bold">
                          Agency/Region
                        </div>
                        <div class="col-span-5 text-xs font-bold">Service</div>
                        <div class="col-span-5 text-xs font-bold">Office</div>
                      </li>
                      <li v-for="recipient in documentInformation.recipients" :key="recipient.office_code"
                        class="grid grid-cols-12 p-2">
                        <div class="col-span-2 text-xs">
                          {{ recipient.region }}
                        </div>
                        <div class="col-span-5 text-xs">
                          {{ recipient.service }}
                        </div>
                        <div class="col-span-5 text-xs">
                          {{ recipient.office }}
                        </div>
                      </li>
                    </ul>
                  </div>
                </div>
                <!-- Receivers Information Content End -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
