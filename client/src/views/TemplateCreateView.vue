<script setup lang="ts">
import { reactive, ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { useTemplates, type TemplateRecipient, type TemplateSignatory } from "@/composables/useTemplates";
import { useAuthStore } from "@/stores/auth";
import { useLibraryStore } from "@/stores/libraries";
import { useOfficeLibrary } from "@/composables/useOfficeLibrary";
import { useSignatoriesLibrary } from "@/composables/useSignatoryLibrary";
import { useToggle } from "@/utils/toggler";
import BaseButton from "@/components/ui/buttons/BaseButton.vue";
import FrmLabel from "@/components/ui/labels/FrmLabel.vue";
import Input from "@/components/ui/inputs/FrmInput.vue";
import Textarea from "@/components/ui/textareas/Textarea.vue";
import BaseRadioButton from "@/components/ui/buttons/BaseRadioButton.vue";
import SearchSelect from "@/components/ui/select/SearchSelect.vue";
import SearchMultiSelectOffice from "@/components/ui/select/SearchMultiSelectOffice.vue";
import SingleArrowIcon from "@/components/ui/icons/SingleArrowIcon.vue";
import MultipleArrowIcon from "@/components/ui/icons/MultipleArrowIcon.vue";
import SequencialArrowIcon from "@/components/ui/icons/SequencialArrowIcon.vue";
import SequenceArrowIcon from "@/components/ui/icons/SequenceArrowIcon.vue";
import PlusIcon from "@/components/ui/icons/PlusIcon.vue";
import MinusIcon from "@/components/ui/icons/MinusIcon.vue";
import ModalDialog from "@/components/ui/modals/ModalDialog.vue";
import draggable from "vuedraggable";
import type { Recipient } from "@/types";

const router = useRouter();
const authStore = useAuthStore();
const { createTemplate } = useTemplates();

const {
  documentTypeLibrary,
  actionLibrary,
  loadDocumentTypeLibrary,
  loadActionLibrary,
  loadOfficeLibrary,
} = useLibraryStore();

const officeLibrary = useOfficeLibrary();
const signatoryLibrary = useSignatoriesLibrary();

const isLoading = ref(false);
const error = ref("");
const userRole = computed(() => authStore.user?.role ?? "regular");

// ─── Form state ──────────────────────────────────────────────────────────────

const form = reactive({
  name: "",
  description: "",
  scope: "personal" as "personal" | "office" | "system",
  urgency_level: "High" as "Urgent" | "High" | "Normal" | "Routine",
  remarks_template: "",
  sender: "",
  sender_position: "",
  sender_office: "",
  sender_email: "",
});

// These hold the full SearchSelect return_value objects
const selectedDocType = ref<{ id: string; code: string; type: string } | null>(null);
const selectedAction = ref<{ id: string; action: string } | null>(null);
const originType = ref("");
const routingType = ref<"" | "Single" | "Multiple" | "Sequential">("");

// ─── Signatories state ───────────────────────────────────────────────────────

interface SignatoryItem {
  id: string;
  name: string;
  position: string;
  office: string;
  office_id: string;
}

const signatories = ref<SignatoryItem[]>([]);

const removeSignatory = (id: string) => {
  signatories.value = signatories.value.filter((s) => s.id !== id);
};

// Clear sender fields when origin type changes
watch(
  () => originType.value,
  () => {
    form.sender = "";
    form.sender_position = "";
    form.sender_office = "";
    form.sender_email = "";
  }
);

// ─── Scope options ───────────────────────────────────────────────────────────

const scopeOptions = computed(() => {
  const opts = [{ value: "personal", label: "Personal — only me" }];
  if (["superior", "admin"].includes(userRole.value)) {
    opts.push({ value: "office", label: "Office — all office members" });
  }
  if (userRole.value === "admin") {
    opts.push({ value: "system", label: "System — all users" });
  }
  return opts;
});

// ─── Recipient local state ───────────────────────────────────────────────────

const recipients_default = ref<Recipient[]>([]);
const recipients_cc = ref<Recipient[]>([]);
const recipients_bcc = ref<Recipient[]>([]);

// Reset all recipient lists when routing type changes
watch(
  () => routingType.value,
  () => {
    recipients_default.value = [];
    recipients_cc.value = [];
    recipients_bcc.value = [];
  }
);

// Assign sequence numbers for Sequential routing
watch(
  () => recipients_default.value,
  () => {
    if (routingType.value === "Sequential") {
      recipients_default.value.forEach((r, i) => {
        r.sequence = i + 1;
      });
    }
  }
);

const updateSequence = () => {
  if (routingType.value === "Sequential") {
    recipients_default.value.forEach((r, i) => {
      r.sequence = i + 1;
    });
  }
};

// ─── Mutual exclusivity ─────────────────────────────────────────────────────

const availableOffices = computed(() =>
  (officeLibrary.office.value ?? []).filter(
    (o) => o.return_value.office_code !== authStore.user?.office_id
  )
);

const defaultOfficeCodes = computed(() => new Set(recipients_default.value.map((r) => r.office_code)));
const ccOfficeCodes = computed(() => new Set(recipients_cc.value.map((r) => r.office_code)));
const bccOfficeCodes = computed(() => new Set(recipients_bcc.value.map((r) => r.office_code)));

const officesForDefault = computed(() =>
  availableOffices.value.filter(
    (o) => !ccOfficeCodes.value.has(o.return_value.office_code) && !bccOfficeCodes.value.has(o.return_value.office_code)
  )
);
const officesForCC = computed(() =>
  availableOffices.value.filter(
    (o) => !defaultOfficeCodes.value.has(o.return_value.office_code) && !bccOfficeCodes.value.has(o.return_value.office_code)
  )
);
const officesForBCC = computed(() =>
  availableOffices.value.filter(
    (o) => !defaultOfficeCodes.value.has(o.return_value.office_code) && !ccOfficeCodes.value.has(o.return_value.office_code)
  )
);

// ─── Remove helpers ──────────────────────────────────────────────────────────

const removeFromDefault = (r: Recipient) => {
  recipients_default.value = recipients_default.value.filter((x) => x.office_code !== r.office_code);
};
const removeFromCC = (r: Recipient) => {
  recipients_cc.value = recipients_cc.value.filter((x) => x.office_code !== r.office_code);
};
const removeFromBCC = (r: Recipient) => {
  recipients_bcc.value = recipients_bcc.value.filter((x) => x.office_code !== r.office_code);
};

// ─── CC/BCC toggles ─────────────────────────────────────────────────────────

const toggleRecipientCC = useToggle();
const toggleRecipientBCC = useToggle();

// ─── Validation ──────────────────────────────────────────────────────────────

const fieldErrors = ref<Record<string, string>>({});
const showValidationModal = ref(false);
const showSaveModal = ref(false);
const showErrorModal = ref(false);

function validateForm() {
  fieldErrors.value = {};
  if (!form.name.trim()) {
    fieldErrors.value.name = "Template name is required.";
  }

  if (Object.keys(fieldErrors.value).length > 0) {
    showValidationModal.value = true;
    return false;
  }

  return true;
}

function closeValidationModal() {
  showValidationModal.value = false;
}

function openSaveModal() {
  if (!validateForm()) return;
  showSaveModal.value = true;
}

function closeSaveModal() {
  showSaveModal.value = false;
}

function closeErrorModal() {
  showErrorModal.value = false;
  error.value = "";
}

async function confirmSave() {
  showSaveModal.value = false;
  await submit();
}

// ─── Submit ──────────────────────────────────────────────────────────────────

function buildRecipients(): Omit<TemplateRecipient, "id" | "template_id">[] {
  const defaults = recipients_default.value.map((r, idx) => ({
    office_id: r.office_code,
    office_name: r.office,
    recipient_type: "default" as const,
    sequence: idx + 1,
  }));
  const cc = recipients_cc.value.map((r, idx) => ({
    office_id: r.office_code,
    office_name: r.office,
    recipient_type: "cc" as const,
    sequence: idx + 1,
  }));
  const bcc = recipients_bcc.value.map((r, idx) => ({
    office_id: r.office_code,
    office_name: r.office,
    recipient_type: "bcc" as const,
    sequence: idx + 1,
  }));
  return [...defaults, ...cc, ...bcc];
}

function buildSignatories(): Omit<TemplateSignatory, "id" | "template_id">[] {
  return signatories.value.map((s, idx) => ({
    signatory_id: s.id,
    name: s.name,
    position: s.position,
    office: s.office,
    role: null,
    sequence: idx + 1,
  }));
}

async function submit() {
  if (!validateForm()) return;

  isLoading.value = true;
  error.value = "";
  try {
    await createTemplate({
      name: form.name.trim(),
      description: form.description.trim() || null,
      scope: form.scope,
      document_type: selectedDocType.value?.type || null,
      action_type: selectedAction.value?.action || null,
      routing_type: routingType.value || null,
      urgency_level: form.urgency_level,
      origin_type: originType.value || null,
      sender: originType.value === "External" ? form.sender.trim() || null : null,
      sender_position: originType.value === "External" ? form.sender_position.trim() || null : null,
      sender_office: originType.value === "External" ? form.sender_office.trim() || null : null,
      sender_email: originType.value === "Email" ? form.sender_email.trim() || null : null,
      remarks_template: form.remarks_template.trim() || null,
      recipients: buildRecipients(),
      signatories: buildSignatories(),
    });
    router.push({ name: "my-templates" });
  } catch (e: any) {
    error.value = e.response?.data?.message ?? e.message ?? "Something went wrong. Please try again.";
    showErrorModal.value = true;
  } finally {
    isLoading.value = false;
  }
}

watch(
  () => form.name,
  (value) => {
    if (value?.trim()) {
      delete fieldErrors.value.name;
    }
  }
);

onMounted(async () => {
  loadDocumentTypeLibrary();
  loadActionLibrary();
  loadOfficeLibrary();
  await Promise.all([
    officeLibrary.fetchOffices(),
    signatoryLibrary.fetchSignatories(),
  ]);
});
</script>

<template>
  <div class="flex-1 w-full h-full bg-gray-50">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 p-2">
      <div class="flex items-center gap-2">
        <button type="button" @click="router.push({ name: 'my-templates' })"
          class="p-1 text-gray-400 rounded hover:text-gray-600 hover:bg-gray-100">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
            <path fill-rule="evenodd"
              d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z"
              clip-rule="evenodd" />
          </svg>
        </button>
        <h1 class="text-sm font-semibold text-gray-800">Create Template</h1>
      </div>
      <div class="flex gap-2 ml-auto shrink-0">
        <BaseButton :btn-text="'Cancel'" :action="() => router.push({ name: 'my-templates' })"
          :className="'flex items-center'" />
        <BaseButton :btn-text="'Create Template'" :action="() => openSaveModal()" :disabled="isLoading"
          :className="'flex items-center'">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-2 size-4">
            <path fill-rule="evenodd"
              d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
              clip-rule="evenodd" />
          </svg>
        </BaseButton>
      </div>
    </div>
    <hr class="text-gray-200" />

    <!-- Two-column layout -->
    <div class="grid sm:grid-cols-12 h-[calc(100svh-6.3rem)] max-h-[calc(100svh+6rem)] overflow-auto divide-x divide-gray-200">

      <!-- Left column: Template details + Document defaults -->
      <div class="flex flex-col justify-start h-full p-6 overflow-y-auto sm:col-span-7">
        <h1 class="mb-4 text-lg font-semibold text-gray-800">Template Details</h1>

        <div class="grid gap-3 sm:grid-cols-12">
          <!-- Name -->
          <div class="sm:col-span-8">
            <FrmLabel :label="'Template Name'" class="mb-2" :isRequired="true" />
            <div :class="{ 'rounded-lg ring-2 ring-red-400': fieldErrors.name }">
              <Input v-model="form.name" placeholder="e.g. Standard Budget Request" />
            </div>
            <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-500">{{ fieldErrors.name }}</p>
          </div>

          <!-- Scope -->
          <div class="sm:col-span-4">
            <FrmLabel :label="'Scope'" class="mb-2" />
            <select v-model="form.scope"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
              <option v-for="opt in scopeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
        </div>

        <div class="mt-3 mb-2">
          <FrmLabel :label="'Description'" class="mb-2" />
          <Textarea v-model="form.description" :rows="'2'" placeholder="Optional — when to use this template" />
        </div>

        <hr class="my-4 text-gray-200" />
        <h1 class="mb-4 text-lg font-semibold text-gray-800">Document Defaults</h1>

        <div class="grid gap-3 sm:grid-cols-12">
          <!-- Document Type -->
          <div class="sm:col-span-6">
            <FrmLabel :label="'Document Type'" class="mb-2" />
            <SearchSelect v-model="selectedDocType" :items="documentTypeLibrary.data ?? []"
              placeholder="Select Document Type" :isLoading="documentTypeLibrary.isLoading" />
          </div>

          <!-- Action Taken -->
          <div class="sm:col-span-6">
            <FrmLabel :label="'Action Taken'" class="mb-2" />
            <SearchSelect v-model="selectedAction" :items="actionLibrary.data ?? []"
              placeholder="Select Action Type" :isLoading="actionLibrary.isLoading" />
          </div>
        </div>

        <!-- Origin Type -->
        <div class="mt-3 mb-2">
          <div class="flex items-center justify-between mb-2">
            <FrmLabel :label="'Origin Type'" />
            <button v-if="originType" type="button" @click="originType = ''"
              class="text-xs text-gray-400 hover:text-gray-600">Clear</button>
          </div>
          <ul
            class="flex text-sm font-medium text-gray-900 bg-white border border-gray-200 divide-x divide-gray-300 rounded-lg">
            <li class="w-full">
              <BaseRadioButton v-model="originType" label="Internal" :value="'Internal'" />
            </li>
            <li class="w-full">
              <BaseRadioButton v-model="originType" label="External" :value="'External'" />
            </li>
            <li class="w-full">
              <BaseRadioButton v-model="originType" label="Email" :value="'Email'" />
            </li>
          </ul>
        </div>

        <!-- Sender Fields for External -->
        <div v-if="originType === 'External'" class="p-4 mt-3 mb-2 border border-gray-200 rounded-lg bg-gray-50">
          <h3 class="mb-3 text-sm font-medium text-gray-700">External Sender Details</h3>
          <div class="grid gap-3 sm:grid-cols-12">
            <div class="sm:col-span-12">
              <FrmLabel :label="'Sender Name'" class="mb-2" />
              <Input v-model="form.sender" placeholder="e.g. John Doe" />
            </div>
            <div class="sm:col-span-6">
              <FrmLabel :label="'Position'" class="mb-2" />
              <Input v-model="form.sender_position" placeholder="e.g. Director" />
            </div>
            <div class="sm:col-span-6">
              <FrmLabel :label="'Office/Organization'" class="mb-2" />
              <Input v-model="form.sender_office" placeholder="e.g. Department of Education" />
            </div>
          </div>
        </div>

        <!-- Sender Email for Email origin -->
        <div v-if="originType === 'Email'" class="p-4 mt-3 mb-2 border border-gray-200 rounded-lg bg-gray-50">
          <h3 class="mb-3 text-sm font-medium text-gray-700">Email Sender</h3>
          <div class="sm:col-span-12">
            <FrmLabel :label="'Sender Email'" class="mb-2" />
            <Input v-model="form.sender_email" placeholder="e.g. sender@example.com" type="email" />
          </div>
        </div>

        <!-- Urgency Level -->
        <div class="mt-3 mb-2">
          <FrmLabel :label="'Default Urgency Level'" class="mb-2" />
          <select v-model="form.urgency_level"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            <option value="Urgent">Urgent (1 day)</option>
            <option value="High">High (3 days)</option>
            <option value="Normal">Normal (5 days)</option>
            <option value="Routine">Routine (7 days)</option>
          </select>
        </div>

        <!-- Remarks Template -->
        <div class="mt-3 mb-4">
          <FrmLabel :label="'Remarks Template'" class="mb-2" />
          <Textarea v-model="form.remarks_template" :rows="'3'" placeholder="Default remarks text (optional)" />
        </div>

        <!-- Signatories -->
        <hr class="my-4 text-gray-200" />
        <h1 class="mb-4 text-lg font-semibold text-gray-800">Signatories</h1>
        <div class="mb-4">
          <SearchSelect v-model="signatories" :items="signatoryLibrary.data.value ?? []"
            placeholder="Select Signatories" :isMultiple="true" :isLoading="signatoryLibrary.isLoading.value" />

          <div v-if="signatories.length === 0">
            <div class="p-3 mt-2 text-xs text-center text-gray-600 bg-white border border-gray-200 rounded">
              No signatory selected.
            </div>
          </div>
          <div v-else
            class="flex flex-col mt-2 text-xs text-gray-600 bg-white border border-gray-200 divide-y divide-gray-200 rounded-md">
            <div v-for="signatory in signatories" :key="signatory.id"
              class="flex items-center justify-between px-4 py-3">
              <div>
                <div class="font-bold">{{ signatory.name }}</div>
                <div class="text-xs">{{ signatory.position }} - {{ signatory.office }}</div>
              </div>
              <div>
                <button type="button" @click="removeSignatory(signatory.id)"
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
        </div>
      </div>

      <!-- Right column: Routing + Recipients -->
      <div class="flex flex-col justify-start h-full p-6 overflow-y-auto sm:col-span-5">
        <h1 class="mb-4 text-lg font-semibold text-gray-800">Routing Details</h1>

        <!-- Routing Type -->
        <div class="mb-2">
          <div class="flex items-center justify-between mb-2">
            <FrmLabel :label="'Routing Type'" />
            <button v-if="routingType" type="button" @click="routingType = ''"
              class="text-xs text-gray-400 hover:text-gray-600">Clear</button>
          </div>
          <ul
            class="flex text-sm font-medium text-gray-900 bg-white border border-gray-200 divide-x divide-gray-300 rounded-lg"
            :class="routingType ? 'rounded-b-none border-b-0' : ''">
            <li class="w-full">
              <BaseRadioButton v-model="routingType" :name="'routing'" label="Single" :value="'Single'" />
            </li>
            <li class="w-full">
              <BaseRadioButton v-model="routingType" :name="'routing'" label="Multiple" :value="'Multiple'" />
            </li>
            <li class="w-full">
              <BaseRadioButton v-model="routingType" :name="'routing'" label="Sequential" :value="'Sequential'" />
            </li>
          </ul>
          <div v-if="routingType" class="p-4 bg-gray-100 border border-gray-200 rounded-lg rounded-t-none">
            <div v-if="routingType === 'Single'" class="flex items-start">
              <SingleArrowIcon class="text-gray-400 size-10" />
              <p class="px-4 text-xs text-gray-500 w-[20rem]">
                Send to one recipient. The template will pre-select a single default recipient.
              </p>
            </div>
            <div v-else-if="routingType === 'Multiple'" class="flex items-start">
              <MultipleArrowIcon class="text-gray-400 size-10" />
              <p class="w-[20rem] px-4 text-xs text-gray-500">
                Send to multiple recipients simultaneously. All recipients receive the document at once.
              </p>
            </div>
            <div v-else-if="routingType === 'Sequential'" class="flex items-start">
              <SequencialArrowIcon class="text-gray-400 size-10" />
              <p class="w-[20rem] px-4 text-xs text-gray-500">
                Send to recipients in a specific order. Each recipient acts before the next receives.
              </p>
            </div>
          </div>
        </div>

        <!-- Send To (default recipients) -->
        <div v-if="routingType" class="mb-2">
          <div class="flex justify-between mb-2">
            <FrmLabel :label="'Send To'" class="mb-1" />
          </div>
          <SearchMultiSelectOffice v-model="recipients_default" :items="officesForDefault" :placeholder="recipients_default.length
            ? recipients_default.length + ' recipients selected'
            : 'Select Recipient'
            " :isLoading="officeLibrary.isLoading.value" :has-desc="true"
            :is-multiple="routingType === 'Multiple' || routingType === 'Sequential'" />

          <!-- Single routing list -->
          <div v-if="routingType === 'Single' && recipients_default.length" class="mb-2">
            <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-for="recipient in recipients_default" :key="recipient.office_code"
                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                <div class="col-span-10">
                  <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{ recipient.service }}
                  </div>
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
        <div v-if="routingType === 'Multiple'" class="mb-2">
          <FrmLabel :label="'Recipient/s (' + recipients_default.length + ')'" class="mb-1" />
          <ul class="border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
            <li v-if="recipients_default.length === 0">
              <div class="p-3 text-xs text-center text-gray-600 bg-white">No recipients selected.</div>
            </li>
            <li v-else v-for="recipient in recipients_default" :key="recipient.office_code"
              class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
              <div class="col-span-10">
                <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{ recipient.service }}
                </div>
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
        <div v-if="routingType === 'Sequential'" class="mb-2">
          <FrmLabel :label="'Recipient/s (' + recipients_default.length + ')'" class="z-[1000] mb-1" />
          <div class="relative mt-4">
            <draggable v-model="recipients_default" item-key="office_code" @end="updateSequence"
              handle=".drag-handle" ghost-class="drag-ghost" chosen-class="drag-chosen">
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
            <ul class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-if="recipients_cc.length === 0">
                <div class="p-3 text-xs text-center text-gray-600 bg-white">No recipients selected.</div>
              </li>
              <li v-else v-for="recipient in recipients_cc" :key="recipient.office_code"
                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                <div class="col-span-10">
                  <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{ recipient.service }}
                  </div>
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
            <ul class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
              <li v-if="recipients_bcc.length === 0">
                <div class="p-3 text-xs text-center text-gray-600 bg-white">No recipients selected.</div>
              </li>
              <li v-else v-for="recipient in recipients_bcc" :key="recipient.office_code"
                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                <div class="col-span-10">
                  <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{ recipient.service }}
                  </div>
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

    <ModalDialog
      :isOpen="showValidationModal"
      title="Incomplete Required Fields"
      @close="closeValidationModal"
      @confirm="closeValidationModal"
    >
      <div class="w-full mt-3">
        <p class="mb-3 text-sm text-center text-gray-500">Please fill in all required fields before saving.</p>
        <ul class="space-y-1 text-xs text-left text-red-600">
          <li v-for="(errorMessage, key) in fieldErrors" :key="key" class="flex items-start gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-3.5 shrink-0">
              <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            {{ errorMessage }}
          </li>
        </ul>
      </div>
    </ModalDialog>

    <ModalDialog
      :isOpen="showSaveModal"
      title="Save Template"
      @close="closeSaveModal"
      @confirm="confirmSave"
    >
      <div class="w-full mt-3">
        <p class="text-sm text-center text-gray-500">Do you want to save this template?</p>
      </div>
    </ModalDialog>

    <ModalDialog
      :isOpen="showErrorModal"
      title="Something Went Wrong"
      @close="closeErrorModal"
      @confirm="closeErrorModal"
    >
      <div class="w-full mt-3">
        <p class="text-sm text-center text-gray-500">{{ error }}</p>
      </div>
    </ModalDialog>
  </div>
</template>

<style scoped>
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
