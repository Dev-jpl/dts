<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import BaseButton from "../ui/buttons/BaseButton.vue";
import DateInput from "../ui/inputs/DateInput.vue";
import Input from "../ui/inputs/FrmInput.vue";
import FrmLabel from "../ui/labels/FrmLabel.vue";
import Toggle from "../ui/switches/Toggle.vue";
import Textarea from "../ui/textareas/Textarea.vue";
import { useStepNavigation } from "@/composables/useStepNavigation";
import { useDocumentInformation } from "@/composables/useDocumentInformation";
import BaseRadioButton from "../ui/buttons/BaseRadioButton.vue";
import SearchSelect from "../ui/select/SearchSelect.vue";
const { setStep } = useStepNavigation();
const { documentInformation, removeRecipient, removeSignatory } = useDocumentInformation();
import { useLibraryStore } from "@/stores/libraries";
import SearchMultiSelectOffice from "../ui/select/SearchMultiSelectOffice.vue";
import { useReplyDocument } from "@/composables/useReplyDoc";
import ReplyHeader from "./ReplyHeader.vue";
import { useOfficeLibrary } from '@/composables/useOfficeLibrary';

const {
  documentTypeLibrary,
  actionLibrary,
  // officeLibrary,
  loadDocumentTypeLibrary,
  loadActionLibrary,
  loadOfficeLibrary,
} = useLibraryStore();

const officeLibrary = useOfficeLibrary()
const signatoryLibrary = useSignatoriesLibrary()

onMounted(async () => {
  loadDocumentTypeLibrary();
  loadActionLibrary();
  loadOfficeLibrary();
  await officeLibrary.fetchOffices();
  await signatoryLibrary.fetchSignatories();

  console.log(signatoryLibrary.data.value);


  documentInformation.sender = "Gusioni Bumbini";
  documentInformation.sender_position = "Delivery Guy";
  documentInformation.sender_office = "Tekinologia Mekus Inc";
  documentInformation.sender_email = "gcash@gmail.com";
});


//reset recipients if routing is changed
watch(
  () => documentInformation.routing,
  (newVal, oldVal) => {
    documentInformation.recipients = [];
  }
);





const recipients_default = ref<Recipient[]>([]) // your "To" recipients
const recipients_cc = ref<Recipient[]>([])
const recipients_bcc = ref<Recipient[]>([])

const updateSequence = () => {
  if (documentInformation.routing === "Sequential") {
    recipients_default.value.forEach((recipient, index) => {
      recipient.sequence = index + 1;
    });
  }
};

//Add sequence numbers to recipients if routing is sequential, remove if not sequential
watch(
  () => recipients_default.value,
  (newVal, oldVal) => {

    if (documentInformation.routing === "Sequential") {
      // Assign sequence numbers based on the order of recipients
      recipients_default.value.forEach((recipient, index) => {
        recipient.sequence = index + 1; // Start sequence from 1
      });
    } else {
      // If not sequential, remove any existing sequence numbers
      recipients_default.value.forEach((recipient) => {
        delete recipient.sequence;
      });
    }
  }
);

// Merge everything into documentInformation.recipients
const recipients = computed(() => {
  const defaults = recipients_default.value.map((r: Recipient, idx) => ({
    recipient_type: "default",
    sequence: idx,
    ...r,
  }))
  const cc = recipients_cc.value.map((r: Recipient, idx) => ({
    recipient_type: "cc",
    sequence: idx,
    ...r,
  }))
  const bcc = recipients_bcc.value.map((r: Recipient, idx) => ({
    recipient_type: "bcc",
    sequence: idx,
    ...r,
  }))
  return [...defaults, ...cc, ...bcc]
})

// Keep documentInformation.recipients in sync
watch(
  recipients,
  (newVal) => {
    documentInformation.recipients = newVal
  },
  { immediate: true }
)



import draggable from "vuedraggable"; // 👈 import
import SingleArrowIcon from "../ui/icons/SingleArrowIcon.vue";
import MultipleArrowIcon from "../ui/icons/MultipleArrowIcon.vue";
import SequencialArrowIcon from "../ui/icons/SequencialArrowIcon.vue";
import SequenceArrowIcon from "../ui/icons/SequenceArrowIcon.vue";
import { useSignatoriesLibrary } from "@/composables/useSignatoryLibrary";
import type { Recipient } from "@/types";
import PlusIcon from "../ui/icons/PlusIcon.vue";
import MinusIcon from "../ui/icons/MinusIcon.vue";
import { useToggle } from "@/utils/toggler";


const toggleRecipientCC = useToggle();
const toggleRecipientBCC = useToggle();

</script>

<template>
  <div class="flex-1 w-full h-full bg-gray-50">
    <div class="flex items-center justify-between col-span-5 p-2">
      <ReplyHeader />
      <BaseButton :btn-text="'Proceed to next step'" :action="() => setStep(2)"
        :className="'flex items-center ml-auto'">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="sm:mr-2 size-4">
          <path fill-rule="evenodd"
            d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm4.28 10.28a.75.75 0 0 0 0-1.06l-3-3a.75.75 0 1 0-1.06 1.06l1.72 1.72H8.25a.75.75 0 0 0 0 1.5h5.69l-1.72 1.72a.75.75 0 1 0 1.06 1.06l3-3Z"
            clip-rule="evenodd" />
        </svg>
      </BaseButton>
    </div>
    <hr class="text-gray-200" />
    <div
      class="grid sm:grid-cols-12 h-[calc(100svh-6.3rem)] max-h-[calc(100svh+6rem)] overflow-auto divide-x divide-gray-200">
      <div class="flex flex-col justify-start h-full p-6 sm:col-span-7">
        <!-- Add content here -->
        <h1 class="mb-4 text-lg font-semibold text-gray-800">
          Document Information
        </h1>
        <div class="grid gap-3 sm:grid-cols-12">
          <div class="mb-2 sm:col-span-3">
            <FrmLabel :label="'Date'" class="mb-2" />
            <DateInput v-model="documentInformation.date" />
          </div>

          <!-- Document Type Field -->
          <div class="sm:col-span-4">
            <FrmLabel :label="'Document Type'" class="mb-2" />
            <SearchSelect v-model="documentInformation.documentType" :items="documentTypeLibrary.data ?? []"
              placeholder="Select Document Type" :isLoading="documentTypeLibrary.isLoading" />
          </div>

          <!-- Action Taken Field -->
          <div class="mb-2 sm:col-span-5">
            <div class="flex">
              <FrmLabel :label="'Action Taken'" class="mb-2" :isRequired="true" />
            </div>

            <SearchSelect v-model="documentInformation.actionTaken" :items="actionLibrary.data ?? []"
              placeholder="Select Action Type" :isLoading="actionLibrary.isLoading" />
          </div>
        </div>

        <!-- Origin Type Field -->
        <div class="mb-2">
          <FrmLabel :label="'Origin Type'" class="mb-2" />
          <ul
            class="flex mt-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 divide-x divide-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <li class="w-full">
              <BaseRadioButton v-model="documentInformation.originType" label="Internal" :value="'Internal'" />
            </li>
            <li class="w-full">
              <BaseRadioButton v-model="documentInformation.originType" label="External" :value="'External'" />
            </li>
            <li class="w-full">
              <BaseRadioButton v-model="documentInformation.originType" label="Email" :value="'Email'" />
            </li>
          </ul>
        </div>
        <template v-if="documentInformation.originType === 'External'">
          <div class="mb-2">
            <FrmLabel :label="'Sender'" class="mb-2" />
            <Input v-model="documentInformation.sender" placeholder="Enter sender name" />
          </div>
          <div class="mb-2">
            <FrmLabel :label="'Sender Position'" class="mb-2" />
            <Input v-model="documentInformation.sender_position" placeholder="Enter sender position" />
          </div>
          <div class="mb-2">
            <FrmLabel :label="'Sender Office'" class="mb-2" />
            <Input v-model="documentInformation.sender_office" placeholder="Enter sender office" />
          </div>
        </template>
        <template v-if="documentInformation.originType === 'Email'">
          <div class="mb-2">
            <FrmLabel :label="'Sent By'" class="mb-2" />
            <Input v-model="documentInformation.sender_email" placeholder="Enter sender email" />
          </div>
        </template>

        <div class="mb-2">
          <FrmLabel v-model="documentInformation.sender_email" :label="'Subject'" class="mb-2" />
          <Textarea v-model="documentInformation.subject" :rows="'8'" class="mb-2" />
        </div>
        <div class="mb-4">
          <FrmLabel :label="'Remarks'" class="mb-2" />
          <Textarea v-model="documentInformation.remarks" class="mb-2" />
        </div>

        <Toggle :title="'Bind Document'" />

        <!-- Signatories Field -->
        <div class="mt-5 mb-2">
          <div class="flex">
            <FrmLabel :label="'Signatories'" class="mb-2" :isRequired="true" />
          </div>

          <SearchSelect v-model="documentInformation.signatories" :items="signatoryLibrary.data.value ?? []"
            placeholder="Select Signatories" :isMultiple="true" :isLoading="signatoryLibrary.isLoading.value" />

          <div v-if="documentInformation.signatories.length === 0">
            <div class="p-3 mt-2 text-xs text-center text-gray-600 bg-white border border-gray-200 rounded">
              No signatory selected.
            </div>
          </div>
          <div v-else
            class="flex flex-col mt-2 text-xs text-gray-600 bg-white border border-gray-200 divide-y divide-gray-200 rounded-md">

            <div v-for="signatory in documentInformation.signatories"
              class="flex items-center justify-between px-4 py-3">
              <div>
                <div class="font-bold">
                  {{ signatory.name }}
                </div>
                <div class="text-xs">
                  {{ signatory.position }} - {{ signatory.office }}
                </div>
              </div>
              <div>
                <button type="button" @click="removeSignatory(signatory.id)"
                  class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100 hover:underline">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                  </svg>
                </button>
              </div>
            </div>


          </div>
        </div>
      </div>

      <div class="flex flex-col justify-start h-full p-6 sm:col-span-5">
        <h1 class="mb-4 text-lg font-semibold text-gray-800">
          Routing Details
        </h1>
        <div class="mb-2">
          <FrmLabel :label="'Routing Type'" class="mb-2" />
          <ul
            class="flex mt-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 divide-x divide-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            :class="documentInformation.routing ? 'rounded-b-none border-b-0' : ''">
            <li class="w-full">
              <BaseRadioButton v-model="documentInformation.routing" :name="'routing'" class="mb-0 " label="Single"
                :value="'Single'" />

            </li>
            <li class="w-full">
              <BaseRadioButton v-model="documentInformation.routing" :name="'routing'" label="Multiple"
                :value="'Multiple'" />

            </li>
            <li class="w-full">
              <BaseRadioButton v-model="documentInformation.routing" :name="'routing'" label="Sequential"
                :value="'Sequential'" />
            </li>
          </ul>
          <div v-if="documentInformation.routing"
            class="p-4 bg-gray-100 border border-gray-200 rounded-lg rounded-t-none">
            <div v-if="documentInformation.routing === 'Single'" class="flex items-start">
              <SingleArrowIcon class="text-gray-400 size-10" />
              <p class="px-4 text-xs text-gray-500 w-[20rem]">
                (Default) Send to one recipient. If selected, the sender can only choose one recipient from the list.
              </p>
            </div>
            <div v-else-if="documentInformation.routing === 'Multiple'" class="flex items-start">
              <MultipleArrowIcon class="text-gray-400 size-10" />
              <p class="w-[20rem] px-4 text-xs text-gray-500 ">
                Send to multiple recipients. If selected, the sender can choose multiple recipients from the list.
              </p>
            </div>
            <div v-else-if="documentInformation.routing === 'Sequential'" class="flex items-start">
              <SequencialArrowIcon class="text-gray-400 size-10" />
              <p class="w-[20rem] px-4 text-xs text-gray-500 ">
                Send to multiple recipients in a specific order. If selected, the sender can choose multiple recipients
                and
                arrange them in a sequence.
              </p>
            </div>
          </div>
        </div>

        <div v-if="documentInformation.routing" class="mb-2">
          <div class="flex justify-between mb-2">
            <FrmLabel :label="'Send To'" class="mb-1" />
          </div>
          <!-- //adjust v-if to documentInformation.routing === 'Multiple' or Single -->
          <SearchMultiSelectOffice v-model="recipients_default" :items="officeLibrary.office.value ?? []" :placeholder="recipients_default
            ? recipients_default.length + ' recipients selected'
            : 'Select Recipient'
            " :isLoading="officeLibrary.isLoading.value" :has-desc="true"
            :is-multiple="(documentInformation.routing === 'Multiple' || documentInformation.routing === 'Sequential') ? true : false" />
          <div v-if="
            documentInformation.routing !== 'Multiple' &&
            recipients_default.length
          " class="mb-2">
            <ul v-if="documentInformation.routing === 'Single'"
              class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-if="recipients_default.length === 0">
                <div class="p-3 text-xs text-center text-gray-600 bg-white">
                  No recipients selected.
                </div>
              </li>
              <li v-else v-for="recipient in recipients_default" :key="recipient.office_code"
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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                      stroke="currentColor" class="size-4">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
        <div v-if="documentInformation.routing === 'Multiple'" class="mb-2">
          <FrmLabel :label="'Recipient/s (' + recipients_default.length + ')'
            " class="mb-1" />
          <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
            <li v-if="recipients_default.length === 0">
              <div class="p-3 text-xs text-center text-gray-600 bg-white">
                No recipients selected.
              </div>
            </li>
            <li v-else v-for="recipient in recipients_default" :key="recipient.office_code"
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
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                  </svg>
                </button>
              </div>
            </li>
          </ul>
        </div>
        <div v-if="documentInformation.routing === 'Sequential'" class="mb-2 ">
          <FrmLabel :label="'Recipient/s (' + recipients_default.length + ')'
            " class="z-[1000] mb-1" />


          <div class="relative mt-4">
            <draggable v-model="recipients_default" item-key="office_code" @end="updateSequence" handle=".drag-handle"
              ghost-class="drag-ghost" chosen-class="drag-chosen">
              <template #item="{ element, index }">
                <div class="relative">
                  <div class="pl-8 mb-[1.6rem] ">
                    <!-- Sequence dot -->
                    <span
                      class="absolute flex items-center justify-center w-8 h-8 text-xs font-bold text-white bg-teal-700 border-2 rounded-full shadow-md z-100 -left-2 top-4">
                      {{ element.sequence }}
                    </span>

                    <!-- Recipient card -->
                    <div
                      class="z-[10000] drag-handle p-4 bg-white border border-gray-200 rounded-lg shadow-sm transition-transform duration-300 ease-in-out cursor-move">
                      <div class="text-gray-400 text-[10px]">
                        {{ element.region }} - {{ element.service }}
                      </div>
                      <div class="text-xs font-medium">{{ element.office }}</div>
                    </div>

                    <!-- Arrow -->
                    <SequenceArrowIcon v-if="index < recipients_default.length - 1"
                      class="absolute text-gray-400 transform -translate-x-1/2 size-8 bottom-[-1.8rem] left-1/2" />

                    <!-- Remove -->
                    <div class="absolute text-gray-400 cursor-move drag-handle right-2 top-4">
                      <button type="button" @click="removeRecipient(element)"
                        class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100 hover:underline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                          stroke="currentColor" class="size-4">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </draggable>

            <!-- Timeline vertical line -->
            <div class="absolute z-0 left-2 top-0 bottom-0 w-[2px] bg-gray-300"></div>
          </div>
        </div>

        <!-- //Recipients CC -->
        <div class="p-2 mt-2 border border-gray-200 rounded-md">
          <div class="flex items-center justify-between">
            <FrmLabel :label="'Cc'" :isOptional="true" />

            <button type="button" @click="toggleRecipientCC.toggle()">
              <PlusIcon class="size-4" v-if="!toggleRecipientCC.isToggled.value" />
              <MinusIcon class="size-4" v-else />
            </button>
          </div>
          <div v-if="toggleRecipientCC.isToggled.value" class="mt-3">
            <SearchMultiSelectOffice v-model="recipients_cc" :items="officeLibrary.office.value ?? []"
              placeholder="Select Signatories" :isMultiple="true" :isLoading="officeLibrary.isLoading.value" />

            <ul class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-if="recipients_cc.length === 0">
                <div class="p-3 text-xs text-center text-gray-600 bg-white">
                  No recipients selected.
                </div>
              </li>
              <li v-else v-for="recipient in recipients_cc" :key="recipient.office_code"
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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                      stroke="currentColor" class="size-4">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
        <!-- //Recipients CC End -->

        <!-- //Recipients BCC -->
        <div class="p-2 mt-2 border border-gray-200 rounded-md">
          <div class="flex items-center justify-between">
            <FrmLabel :label="'Bcc'" :isOptional="true" />

            <button type="button" @click="toggleRecipientBCC.toggle()">
              <PlusIcon class="size-4" v-if="!toggleRecipientBCC.isToggled.value" />
              <MinusIcon class="size-4" v-else />
            </button>
          </div>
          <div v-if="toggleRecipientBCC.isToggled.value" class="mt-3">
            <SearchMultiSelectOffice v-model="recipients_bcc" :items="officeLibrary.office.value ?? []"
              placeholder="Select Signatories" :isMultiple="true" :isLoading="officeLibrary.isLoading.value" />

            <ul class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-if="recipients_bcc.length === 0">
                <div class="p-3 text-xs text-center text-gray-600 bg-white">
                  No recipients selected.
                </div>
              </li>
              <li v-else v-for="recipient in recipients_bcc" :key="recipient.office_code"
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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                      stroke="currentColor" class="size-4">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
        <!-- //Recipients BCC End -->
      </div>
      <!-- <hr class="text-gray-200" /> -->
    </div>

    <!-- Left section -->
  </div>
</template>

<style scoped>
.fade-move-enter-active,
.fade-move-leave-active {
  transition: all 0.3s ease;
}

.fade-move-enter-from,
.fade-move-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.fade-move-move {
  transition: transform 0.3s ease;
}

.draggable-item {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.draggable-item.dragging {
  opacity: 0.6;
  transform: scale(1.05);
}

.drag-ghost {
  opacity: 0.4;
  transform: scale(0.95);
  transition: all 0.2s ease;
}

.drag-chosen {
  transform: rotate(2deg);
  transition: transform 0.2s ease;
}

.draggable-item {
  transition: transform 0.3s ease;
}
</style>