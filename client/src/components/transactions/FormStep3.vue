<script setup lang="ts">
import { computed, ref } from "vue";
import BaseButton from "../ui/buttons/BaseButton.vue";
import { useStepNavigation } from "@/composables/useStepNavigation";
import { useDocumentInformation } from "@/composables/useDocumentInformation";
import FileTaskCard from "../uploader/FileTaskCard.vue";
import { BsFileEarmarkPdf } from "vue-icons-plus/bs";
import FrmLabel from "../ui/labels/FrmLabel.vue";
import OriginBadge from "../ui/badges/OriginBadge.vue";
import ReplyHeader from "./ReplyHeader.vue";
import { useAuthStore } from "@/stores/auth";

const { currentStep, setStep, meta_data } = useStepNavigation();
const { documentInformation, submitDocument } = useDocumentInformation();
const authStore = useAuthStore();

const formattedDate = computed(() =>
  new Date(documentInformation.date).toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })
);

// ─── Submit confirm modal ─────────────────────────────────────────────────────

const showConfirmModal = ref(false);
const submitError = ref('');
const isSubmitting = ref(false);

const proceedToNext = async () => {
  isSubmitting.value = true;
  submitError.value = '';
  try {
    const { data } = await submitDocument();
    if (data.success) {
      meta_data.value = data.data;
      showConfirmModal.value = false;
      setStep(4);
    }
  } catch (error: any) {
    submitError.value = error.response?.data?.message ?? error.message ?? 'Submission failed. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="flex-1 w-full h-full bg-white">
    <div class="flex items-center h-[50px] justify-between p-2 border-t border-gray-200 bg-gray-50">
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
        <BaseButton :btn-text="'Proceed to next step'" :action="() => showConfirmModal = true"
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
      <div class="p-4 dark:bg-gray-800 dark:text-white">
        <div>
          <div class="space-y-3">
            <!-- Document Information Header -->
            <div class="p-5 text-white bg-teal-800 rounded-lg">
              <div class="flex items-center justify-between">
                <!-- Document No. -->
                <div class="flex-row sm:flex-col">
                  <div class="flex-row items-center font-bold sm:flex">
                    Document Number:
                    <span class="flex items-center font-bold sm:ml-3 text-lime-200/70 italic text-sm">
                      Pending — assigned on submit
                    </span>
                  </div>
                </div>
                <!-- Document status -->
                <div class="flex items-center">
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
                  <div class="text-xs font-medium">{{ formattedDate }}</div>
                </div>
                <div class="col-span-4">
                  <div class="text-[10px] text-gray-300">Document Type</div>
                  <div class="text-xs font-medium">{{ documentInformation.documentType.type }}</div>
                </div>
                <div class="col-span-4">
                  <div class="text-[10px] text-gray-300">Action Taken</div>
                  <div class="text-xs font-medium">{{ documentInformation.actionTaken.action }}</div>
                </div>
                <div class="col-span-2">
                  <div class="text-[10px] text-gray-300">Origin Type</div>
                  <OriginBadge :origin-type="documentInformation.originType" />
                </div>
              </div>
            </div>

            <!-- External Document Alert -->
            <div v-if="documentInformation.originType === 'External'"
              class="p-2 bg-orange-100 border border-dashed rounded-lg border-amber-600">
              <h3 class="text-xs font-bold text-amber-600">External Document</h3>
              <p class="text-xs text-gray-600">
                This document is from an external source and delivered by sender
                <span class="font-bold">{{ documentInformation.sender }}</span> a
                <span class="font-bold">{{ documentInformation.sender_position }}</span>
                of
                <span class="font-bold">{{ documentInformation.sender_office }}</span>
              </p>
            </div>

            <!-- Email Document Alert -->
            <div v-if="documentInformation.originType === 'Email'"
              class="p-2 border border-dashed rounded-lg bg-sky-100 border-sky-600">
              <h3 class="text-xs font-bold text-sky-600">Emailed Document</h3>
              <p class="text-xs text-gray-600">
                This document is from an external source and was sent through
                email via <span class="font-bold">{{ documentInformation.sender_email }}</span>
              </p>
            </div>

            <!-- From -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 rounded-lg overflow-clip bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="From:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <div class="text-xs">{{ authStore.user?.office_name ?? '—' }}</div>
              </div>
            </div>

            <!-- To -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 rounded-lg overflow-clip bg-gray-50">
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

            <!-- Subject -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 rounded-lg overflow-clip bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Subject:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <p class="text-xs font-light">{{ documentInformation.subject }}</p>
              </div>
            </div>

            <!-- Remarks -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 rounded-lg overflow-clip bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Remarks:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <p class="text-xs font-light">{{ documentInformation.remarks }}</p>
              </div>
            </div>

            <!-- Signatories -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 rounded-lg overflow-clip bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Signatories:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <ul class="space-y-4">
                  <li v-if="documentInformation.signatories.length === 0">
                    <div class="text-xs text-gray-400 italic">No signatories selected.</div>
                  </li>
                  <li v-else v-for="sig in documentInformation.signatories" :key="sig.id">
                    <div class="text-xs font-medium">{{ sig.name }}</div>
                    <div class="text-xs font-light text-gray-600">{{ sig.position }} — {{ sig.office }}</div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Attachments -->
            <div
              class="grid w-full grid-cols-12 border border-gray-100 rounded-lg overflow-clip bg-gray-50">
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
                            <a :href="file.url" target="_blank">{{ file.name }}</a>
                          </div>
                          <div class="text-[10px] text-gray-400">{{ file.size }}</div>
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
                            <a :href="attachment.url" target="_blank">{{ attachment.name }}</a>
                          </div>
                          <div class="text-[10px] text-gray-400">{{ attachment.size }}</div>
                        </div>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Confirmation Modal -->
    <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-5 space-y-4">
        <h3 class="text-base font-semibold text-gray-800">Submit Document</h3>
        <p class="text-sm text-gray-600">
          This will generate a <strong>Document Number</strong>, <strong>Transaction Number</strong>,
          and <strong>QR Code</strong>. The document will be saved as a draft ready for release in the next step.
        </p>
        <p v-if="submitError" class="text-sm text-red-600">{{ submitError }}</p>
        <div class="flex justify-end gap-3">
          <button type="button" @click="showConfirmModal = false"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
          </button>
          <button type="button" :disabled="isSubmitting" @click="proceedToNext"
            class="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-medium">
            {{ isSubmitting ? 'Submitting…' : 'Submit & Continue' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
