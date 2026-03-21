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
import { useLibraryStore } from "@/stores/libraries";
import SearchMultiSelectOffice from "../ui/select/SearchMultiSelectOffice.vue";
import ReplyHeader from "./ReplyHeader.vue";
import { useOfficeLibrary } from '@/composables/useOfficeLibrary';
import draggable from "vuedraggable";
import SingleArrowIcon from "../ui/icons/SingleArrowIcon.vue";
import MultipleArrowIcon from "../ui/icons/MultipleArrowIcon.vue";
import SequencialArrowIcon from "../ui/icons/SequencialArrowIcon.vue";
import SequenceArrowIcon from "../ui/icons/SequenceArrowIcon.vue";
import { useSignatoriesLibrary } from "@/composables/useSignatoryLibrary";
import type { Recipient } from "@/types";
import PlusIcon from "../ui/icons/PlusIcon.vue";
import MinusIcon from "../ui/icons/MinusIcon.vue";
import { useToggle } from "@/utils/toggler";
import ModalDialog from "../ui/modals/ModalDialog.vue";
import { useAuthStore } from "@/stores/auth";

const { setStep } = useStepNavigation();
const { documentInformation, removeSignatory } = useDocumentInformation();

const {
  documentTypeLibrary,
  actionLibrary,
  loadDocumentTypeLibrary,
  loadActionLibrary,
  loadOfficeLibrary,
} = useLibraryStore();

const authStore = useAuthStore();
const officeLibrary = useOfficeLibrary();
const signatoryLibrary = useSignatoriesLibrary();

onMounted(async () => {
  loadDocumentTypeLibrary();
  loadActionLibrary();
  loadOfficeLibrary();
  await officeLibrary.fetchOffices();
  await signatoryLibrary.fetchSignatories();

  // Check for template prefill from sessionStorage
  const prefillJson = sessionStorage.getItem('template_prefill');
  if (prefillJson) {
    try {
      const tpl = JSON.parse(prefillJson);
      sessionStorage.removeItem('template_prefill');

      // Wait a tick for libraries to be populated
      await new Promise((resolve) => setTimeout(resolve, 100));

      // Prefill document type
      if (tpl.document_type) {
        const docTypeItems = documentTypeLibrary.data ?? [];
        const matchedDocType = docTypeItems.find(
          (item: any) => item.return_value?.type === tpl.document_type
        );
        if (matchedDocType) {
          documentInformation.documentType = matchedDocType.return_value;
        }
      }

      // Prefill action type
      if (tpl.action_type) {
        const actionItems = actionLibrary.data ?? [];
        const matchedAction = actionItems.find(
          (item: any) => item.return_value?.action === tpl.action_type
        );
        if (matchedAction) {
          documentInformation.actionTaken = matchedAction.return_value;
        }
      }

      // Prefill routing type
      if (tpl.routing_type) {
        documentInformation.routing = tpl.routing_type;
      }

      // Prefill origin type
      if (tpl.origin_type) {
        documentInformation.originType = tpl.origin_type;
      }

      // Prefill sender fields
      if (tpl.sender) {
        documentInformation.sender = tpl.sender;
      }
      if (tpl.sender_position) {
        documentInformation.sender_position = tpl.sender_position;
      }
      if (tpl.sender_office) {
        documentInformation.sender_office = tpl.sender_office;
      }
      if (tpl.sender_email) {
        documentInformation.sender_email = tpl.sender_email;
      }

      // Prefill remarks
      if (tpl.remarks_template) {
        documentInformation.remarks = tpl.remarks_template;
      }

      // Prefill signatories
      if (tpl.signatories && tpl.signatories.length > 0) {
        const allSignatories = signatoryLibrary.data.value ?? [];
        const mapped = tpl.signatories.map((s: any) => {
          const match = allSignatories.find(
            (lib: any) => lib.return_value?.id === s.signatory_id
          );
          if (match) {
            return match.return_value;
          }
          // Fallback
          return {
            id: s.signatory_id ?? '',
            name: s.name,
            position: s.position ?? '',
            office: s.office ?? '',
            office_id: '',
          };
        }).sort((a: any, b: any) => {
          const seqA = tpl.signatories.find((t: any) => t.signatory_id === a.id)?.sequence ?? 0;
          const seqB = tpl.signatories.find((t: any) => t.signatory_id === b.id)?.sequence ?? 0;
          return seqA - seqB;
        });
        documentInformation.signatories = mapped;
      }

      // Prefill recipients
      if (tpl.recipients && tpl.recipients.length > 0) {
        const allOffices = officeLibrary.office.value ?? [];

        const defaults: Recipient[] = [];
        const cc: Recipient[] = [];
        const bcc: Recipient[] = [];

        for (const r of tpl.recipients) {
          const officeMatch = allOffices.find(
            (o: any) => o.return_value.office_code === r.office_id
          );
          const recipient: Recipient = officeMatch
            ? { ...officeMatch.return_value, sequence: r.sequence }
            : {
                office_code: r.office_id,
                office: r.office_name,
                region: '',
                service: '',
                sequence: r.sequence,
              };

          if (r.recipient_type === 'cc') {
            cc.push(recipient);
          } else if (r.recipient_type === 'bcc') {
            bcc.push(recipient);
          } else {
            defaults.push(recipient);
          }
        }

        // Sort by sequence
        defaults.sort((a, b) => (a.sequence ?? 0) - (b.sequence ?? 0));

        recipients_default.value = defaults;
        recipients_cc.value = cc;
        recipients_bcc.value = bcc;

        // Auto-expand CC/BCC panels if they have recipients
        if (cc.length > 0) toggleRecipientCC.on();
        if (bcc.length > 0) toggleRecipientBCC.on();
      }
    } catch (e) {
      console.error('Failed to parse template prefill:', e);
    }
  }
});

// ─── Recipient local state ────────────────────────────────────────────────────

const recipients_default = ref<Recipient[]>([]);
const recipients_cc = ref<Recipient[]>([]);
const recipients_bcc = ref<Recipient[]>([]);

// Reset all recipient lists when routing type changes
watch(
  () => documentInformation.routing,
  () => {
    documentInformation.recipients = [];
    recipients_default.value = [];
    recipients_cc.value = [];
    recipients_bcc.value = [];
  }
);

// Assign sequence numbers for Sequential routing
const updateSequence = () => {
  if (documentInformation.routing === "Sequential") {
    recipients_default.value.forEach((recipient, index) => {
      recipient.sequence = index + 1;
    });
  }
};

watch(
  () => recipients_default.value,
  () => {
    if (documentInformation.routing === "Sequential") {
      recipients_default.value.forEach((recipient, index) => {
        recipient.sequence = index + 1;
      });
    } else {
      recipients_default.value.forEach((recipient) => {
        delete recipient.sequence;
      });
    }
  }
);

// Merge all local refs into documentInformation.recipients
const recipients = computed(() => {
  const defaults = recipients_default.value.map((r: Recipient, idx) => ({
    recipient_type: "default",
    sequence: idx,
    ...r,
  }));
  const cc = recipients_cc.value.map((r: Recipient, idx) => ({
    recipient_type: "cc",
    sequence: idx,
    ...r,
  }));
  const bcc = recipients_bcc.value.map((r: Recipient, idx) => ({
    recipient_type: "bcc",
    sequence: idx,
    ...r,
  }));
  return [...defaults, ...cc, ...bcc];
});

watch(
  recipients,
  (newVal) => {
    documentInformation.recipients = newVal;
  },
  { immediate: true }
);

// ─── Mutual exclusivity ───────────────────────────────────────────────────────

// Exclude the user's own office from all recipient lists
const availableOffices = computed(() =>
  (officeLibrary.office.value ?? []).filter(
    o => o.return_value.office_code !== authStore.user?.office_id
  )
);

const defaultOfficeCodes = computed(() => new Set(recipients_default.value.map(r => r.office_code)));
const ccOfficeCodes = computed(() => new Set(recipients_cc.value.map(r => r.office_code)));
const bccOfficeCodes = computed(() => new Set(recipients_bcc.value.map(r => r.office_code)));

const officesForDefault = computed(() =>
  availableOffices.value.filter(
    o => !ccOfficeCodes.value.has(o.return_value.office_code) && !bccOfficeCodes.value.has(o.return_value.office_code)
  )
);
const officesForCC = computed(() =>
  availableOffices.value.filter(
    o => !defaultOfficeCodes.value.has(o.return_value.office_code) && !bccOfficeCodes.value.has(o.return_value.office_code)
  )
);
const officesForBCC = computed(() =>
  availableOffices.value.filter(
    o => !defaultOfficeCodes.value.has(o.return_value.office_code) && !ccOfficeCodes.value.has(o.return_value.office_code)
  )
);

// ─── Exclusion hints (show which offices are unavailable and why) ─────────────

const defaultExcludedNames = computed(() =>
  availableOffices.value
    .filter(o => ccOfficeCodes.value.has(o.return_value.office_code) || bccOfficeCodes.value.has(o.return_value.office_code))
    .map(o => o.return_value.office)
);

const ccExcludedNames = computed(() =>
  availableOffices.value
    .filter(o => defaultOfficeCodes.value.has(o.return_value.office_code) || bccOfficeCodes.value.has(o.return_value.office_code))
    .map(o => o.return_value.office)
);

const bccExcludedNames = computed(() =>
  availableOffices.value
    .filter(o => defaultOfficeCodes.value.has(o.return_value.office_code) || ccOfficeCodes.value.has(o.return_value.office_code))
    .map(o => o.return_value.office)
);

// ─── Remove helpers (operate on local refs) ───────────────────────────────────

const removeFromDefault = (r: Recipient) => {
  recipients_default.value = recipients_default.value.filter(x => x.office_code !== r.office_code);
};
const removeFromCC = (r: Recipient) => {
  recipients_cc.value = recipients_cc.value.filter(x => x.office_code !== r.office_code);
};
const removeFromBCC = (r: Recipient) => {
  recipients_bcc.value = recipients_bcc.value.filter(x => x.office_code !== r.office_code);
};

// ─── Validation ───────────────────────────────────────────────────────────────

const fieldErrors = ref<Record<string, string>>({});
const showValidationModal = ref(false);

function validateAndProceed() {
  fieldErrors.value = {};
  if (!documentInformation.date) fieldErrors.value.date = 'Date is required.';
  if (!documentInformation.documentType?.code) fieldErrors.value.documentType = 'Document Type is required.';
  if (!documentInformation.actionTaken?.id) fieldErrors.value.actionTaken = 'Action Taken is required.';
  if (!documentInformation.originType) fieldErrors.value.originType = 'Origin Type is required.';
  if (!documentInformation.subject?.trim()) fieldErrors.value.subject = 'Subject is required.';
  if (!documentInformation.routing) fieldErrors.value.routing = 'Routing Type is required.';
  if (recipients_default.value.length === 0) fieldErrors.value.recipients = 'At least one recipient (Send To) is required.';

  if (Object.keys(fieldErrors.value).length > 0) {
    showValidationModal.value = true;
  } else {
    setStep(2);
  }
}

function closeValidationModal() {
  showValidationModal.value = false;
}

// Clear individual field errors when the user fills them in
watch(() => documentInformation.date, (v) => { if (v) delete fieldErrors.value.date; });
watch(() => documentInformation.documentType, (v) => { if (v?.code) delete fieldErrors.value.documentType; }, { deep: true });
watch(() => documentInformation.actionTaken, (v) => { if (v?.id) delete fieldErrors.value.actionTaken; }, { deep: true });
watch(() => documentInformation.originType, (v) => { if (v) delete fieldErrors.value.originType; });
watch(() => documentInformation.subject, (v) => { if (v?.trim()) delete fieldErrors.value.subject; });
watch(() => documentInformation.routing, (v) => { if (v) delete fieldErrors.value.routing; });
watch(recipients_default, (v) => { if (v.length > 0) delete fieldErrors.value.recipients; });

// ─── CC/BCC toggles ──────────────────────────────────────────────────────────

const toggleRecipientCC = useToggle();
const toggleRecipientBCC = useToggle();
</script>

<template>
  <div class="flex-1 w-full h-full bg-gray-50">
    <div class="flex items-center justify-between col-span-5 p-2 gap-3 flex-wrap">
      <ReplyHeader />
      <BaseButton :btn-text="'Proceed to next step'" :action="() => validateAndProceed()"
        :className="'flex items-center ml-auto shrink-0'">
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
        <h1 class="mb-4 text-lg font-semibold text-gray-800">
          Document Information
        </h1>
        <div class="grid gap-3 sm:grid-cols-12">
          <div class="mb-2 sm:col-span-3">
            <FrmLabel :label="'Date'" class="mb-2" />
            <div :class="{ 'rounded-lg ring-2 ring-red-400': fieldErrors.date }">
              <DateInput v-model="documentInformation.date" />
            </div>
            <p v-if="fieldErrors.date" class="mt-1 text-xs text-red-500">{{ fieldErrors.date }}</p>
          </div>

          <!-- Document Type Field -->
          <div class="sm:col-span-4">
            <FrmLabel :label="'Document Type'" class="mb-2" />
            <div :class="{ 'rounded-lg ring-2 ring-red-400': fieldErrors.documentType }">
              <SearchSelect v-model="documentInformation.documentType" :items="documentTypeLibrary.data ?? []"
                placeholder="Select Document Type" :isLoading="documentTypeLibrary.isLoading" />
            </div>
            <p v-if="fieldErrors.documentType" class="mt-1 text-xs text-red-500">{{ fieldErrors.documentType }}</p>
          </div>

          <!-- Action Taken Field -->
          <div class="mb-2 sm:col-span-5">
            <div class="flex">
              <FrmLabel :label="'Action Taken'" class="mb-2" :isRequired="true" />
            </div>
            <div :class="{ 'rounded-lg ring-2 ring-red-400': fieldErrors.actionTaken }">
              <SearchSelect v-model="documentInformation.actionTaken" :items="actionLibrary.data ?? []"
                placeholder="Select Action Type" :isLoading="actionLibrary.isLoading" />
            </div>
            <p v-if="fieldErrors.actionTaken" class="mt-1 text-xs text-red-500">{{ fieldErrors.actionTaken }}</p>
          </div>
        </div>

        <!-- Origin Type Field -->
        <div class="mb-2">
          <FrmLabel :label="'Origin Type'" class="mb-2" />
          <ul
            class="flex mt-2 text-sm font-medium text-gray-900 bg-white border divide-x divide-gray-300 rounded-lg"
            :class="fieldErrors.originType ? 'border-red-400 ring-2 ring-red-400' : 'border-gray-200'">
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
          <p v-if="fieldErrors.originType" class="mt-1 text-xs text-red-500">{{ fieldErrors.originType }}</p>
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
            <FrmLabel :label="'Sent By (Email)'" class="mb-2" />
            <Input v-model="documentInformation.sender_email" placeholder="Enter sender email" />
          </div>
        </template>

        <div class="mb-2">
          <FrmLabel :label="'Subject'" class="mb-2" />
          <div :class="{ 'rounded-lg ring-2 ring-red-400': fieldErrors.subject }">
            <Textarea v-model="documentInformation.subject" :rows="'8'" class="mb-2" />
          </div>
          <p v-if="fieldErrors.subject" class="mt-1 text-xs text-red-500">{{ fieldErrors.subject }}</p>
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
                <div class="font-bold">{{ signatory.name }}</div>
                <div class="text-xs">{{ signatory.position }} - {{ signatory.office }}</div>
              </div>
              <div>
                <button type="button" @click="removeSignatory(signatory.id)"
                  class="col-span-1 p-2 text-red-500 rounded-full hover:bg-gray-100">
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
            class="flex mt-2 text-sm font-medium text-gray-900 bg-white border divide-x divide-gray-300 rounded-lg"
            :class="[
              documentInformation.routing ? 'rounded-b-none border-b-0' : '',
              fieldErrors.routing ? 'border-red-400 ring-2 ring-red-400' : 'border-gray-200'
            ]">
            <li class="w-full">
              <BaseRadioButton v-model="documentInformation.routing" :name="'routing'" class="mb-0" label="Single"
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
              <p class="w-[20rem] px-4 text-xs text-gray-500">
                Send to multiple recipients. If selected, the sender can choose multiple recipients from the list.
              </p>
            </div>
            <div v-else-if="documentInformation.routing === 'Sequential'" class="flex items-start">
              <SequencialArrowIcon class="text-gray-400 size-10" />
              <p class="w-[20rem] px-4 text-xs text-gray-500">
                Send to multiple recipients in a specific order. If selected, the sender can choose multiple recipients
                and arrange them in a sequence.
              </p>
            </div>
          </div>
          <p v-if="fieldErrors.routing && !documentInformation.routing" class="mt-1 text-xs text-red-500">{{ fieldErrors.routing }}</p>
        </div>

        <!-- Send To (default recipients) -->
        <div v-if="documentInformation.routing" class="mb-2">
          <div class="flex justify-between mb-2">
            <FrmLabel :label="'Send To'" class="mb-1" />
          </div>
          <div :class="{ 'rounded-lg ring-2 ring-red-400': fieldErrors.recipients }">
            <SearchMultiSelectOffice v-model="recipients_default" :items="officesForDefault" :placeholder="recipients_default.length
              ? recipients_default.length + ' recipients selected'
              : 'Select Recipient'
              " :isLoading="officeLibrary.isLoading.value" :has-desc="true"
              :is-multiple="(documentInformation.routing === 'Multiple' || documentInformation.routing === 'Sequential') ? true : false" />
          </div>
          <p v-if="fieldErrors.recipients" class="mt-1 text-xs text-red-500">{{ fieldErrors.recipients }}</p>
          <p v-if="defaultExcludedNames.length" class="flex items-start gap-1.5 mt-1.5 text-xs text-amber-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-3.5 shrink-0">
              <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            <span>{{ defaultExcludedNames.join(', ') }} — already in {{ defaultExcludedNames.length === 1 ? 'another list' : 'other lists' }}</span>
          </p>

          <!-- Single routing list -->
          <div v-if="documentInformation.routing === 'Single' && recipients_default.length" class="mb-2">
            <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-for="recipient in recipients_default" :key="recipient.office_code"
                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                <div class="col-span-10">
                  <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{ recipient.service }}</div>
                  {{ recipient.office }}
                </div>
                <div class="flex items-center justify-end col-span-2">
                  <button type="button" @click="removeFromDefault(recipient)"
                    class="p-2 text-red-500 rounded-full hover:bg-gray-100">
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

        <!-- Multiple routing list -->
        <div v-if="documentInformation.routing === 'Multiple'" class="mb-2">
          <FrmLabel :label="'Recipient/s (' + recipients_default.length + ')'" class="mb-1" />
          <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
            <li v-if="recipients_default.length === 0">
              <div class="p-3 text-xs text-center text-gray-600 bg-white">No recipients selected.</div>
            </li>
            <li v-else v-for="recipient in recipients_default" :key="recipient.office_code"
              class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
              <div class="col-span-10">
                <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{ recipient.service }}</div>
                {{ recipient.office }}
              </div>
              <div class="flex items-center justify-end col-span-2">
                <button type="button" @click="removeFromDefault(recipient)"
                  class="p-2 text-red-500 rounded-full hover:bg-gray-100">
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

        <!-- Sequential routing drag list -->
        <div v-if="documentInformation.routing === 'Sequential'" class="mb-2">
          <FrmLabel :label="'Recipient/s (' + recipients_default.length + ')'" class="z-[1000] mb-1" />
          <div class="relative mt-4">
            <draggable v-model="recipients_default" item-key="office_code" @end="updateSequence" handle=".drag-handle"
              ghost-class="drag-ghost" chosen-class="drag-chosen">
              <template #item="{ element, index }">
                <div class="relative">
                  <div class="pl-8 mb-[1.6rem]">
                    <span
                      class="absolute flex items-center justify-center w-8 h-8 text-xs font-bold text-white bg-teal-700 border-2 rounded-full shadow-md z-100 -left-2 top-4">
                      {{ element.sequence }}
                    </span>
                    <div
                      class="z-[10000] drag-handle p-4 bg-white border border-gray-200 rounded-lg shadow-sm transition-transform duration-300 ease-in-out cursor-move">
                      <div class="text-gray-400 text-[10px]">{{ element.region }} - {{ element.service }}</div>
                      <div class="text-xs font-medium">{{ element.office }}</div>
                    </div>
                    <SequenceArrowIcon v-if="index < recipients_default.length - 1"
                      class="absolute text-gray-400 transform -translate-x-1/2 size-8 bottom-[-1.8rem] left-1/2" />
                    <div class="absolute right-2 top-4">
                      <button type="button" @click="removeFromDefault(element)"
                        class="p-2 text-red-500 rounded-full hover:bg-gray-100">
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
            <div class="absolute z-0 left-2 top-0 bottom-0 w-[2px] bg-gray-300"></div>
          </div>
        </div>

        <!-- CC Recipients -->
        <div class="p-2 mt-2 border border-gray-200 rounded-md">
          <div class="flex items-center justify-between">
            <FrmLabel :label="'Cc'" :isOptional="true" />
            <button type="button" @click="toggleRecipientCC.toggle()">
              <PlusIcon class="size-4" v-if="!toggleRecipientCC.isToggled.value" />
              <MinusIcon class="size-4" v-else />
            </button>
          </div>
          <div v-if="toggleRecipientCC.isToggled.value" class="mt-3">
            <SearchMultiSelectOffice v-model="recipients_cc" :items="officesForCC"
              placeholder="Select CC Recipients" :isMultiple="true" :isLoading="officeLibrary.isLoading.value" />
            <p v-if="ccExcludedNames.length" class="flex items-start gap-1.5 mt-1.5 text-xs text-amber-600">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-3.5 shrink-0">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
              </svg>
              <span>{{ ccExcludedNames.join(', ') }} — already in {{ ccExcludedNames.length === 1 ? 'another list' : 'other lists' }}</span>
            </p>
            <ul class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-if="recipients_cc.length === 0">
                <div class="p-3 text-xs text-center text-gray-600 bg-white">No recipients selected.</div>
              </li>
              <li v-else v-for="recipient in recipients_cc" :key="recipient.office_code"
                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                <div class="col-span-10">
                  <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{ recipient.service }}</div>
                  {{ recipient.office }}
                </div>
                <div class="flex items-center justify-end col-span-2">
                  <button type="button" @click="removeFromCC(recipient)"
                    class="p-2 text-red-500 rounded-full hover:bg-gray-100">
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

        <!-- BCC Recipients -->
        <div class="p-2 mt-2 border border-gray-200 rounded-md">
          <div class="flex items-center justify-between">
            <FrmLabel :label="'Bcc'" :isOptional="true" />
            <button type="button" @click="toggleRecipientBCC.toggle()">
              <PlusIcon class="size-4" v-if="!toggleRecipientBCC.isToggled.value" />
              <MinusIcon class="size-4" v-else />
            </button>
          </div>
          <div v-if="toggleRecipientBCC.isToggled.value" class="mt-3">
            <SearchMultiSelectOffice v-model="recipients_bcc" :items="officesForBCC"
              placeholder="Select BCC Recipients" :isMultiple="true" :isLoading="officeLibrary.isLoading.value" />
            <p v-if="bccExcludedNames.length" class="flex items-start gap-1.5 mt-1.5 text-xs text-amber-600">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-3.5 shrink-0">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
              </svg>
              <span>{{ bccExcludedNames.join(', ') }} — already in {{ bccExcludedNames.length === 1 ? 'another list' : 'other lists' }}</span>
            </p>
            <ul class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-if="recipients_bcc.length === 0">
                <div class="p-3 text-xs text-center text-gray-600 bg-white">No recipients selected.</div>
              </li>
              <li v-else v-for="recipient in recipients_bcc" :key="recipient.office_code"
                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                <div class="col-span-10">
                  <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{ recipient.service }}</div>
                  {{ recipient.office }}
                </div>
                <div class="flex items-center justify-end col-span-2">
                  <button type="button" @click="removeFromBCC(recipient)"
                    class="p-2 text-red-500 rounded-full hover:bg-gray-100">
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
      </div>
    </div>

    <!-- Validation Modal -->
    <ModalDialog
      :isOpen="showValidationModal"
      title="Incomplete Fields"
      @close="closeValidationModal"
      @confirm="closeValidationModal"
    >
      <div class="w-full mt-3">
        <p class="mb-3 text-sm text-center text-gray-500">Please fill in all required fields before proceeding.</p>
        <ul class="space-y-1 text-xs text-left text-red-600">
          <li v-for="(error, key) in fieldErrors" :key="key" class="flex items-start gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-3.5 shrink-0">
              <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            {{ error }}
          </li>
        </ul>
      </div>
    </ModalDialog>
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

.drag-ghost {
  opacity: 0.4;
  transform: scale(0.95);
  transition: all 0.2s ease;
}

.drag-chosen {
  transform: rotate(2deg);
  transition: transform 0.2s ease;
}
</style>
