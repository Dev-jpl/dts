<script setup lang="ts">
import FrmLabel from '../ui/labels/FrmLabel.vue';
import LargeModal from '../ui/modals/LargeModal.vue';
import SearchMultiSelectOffice from '../ui/select/SearchMultiSelectOffice.vue';
import { useLibraryStore } from '@/stores/libraries';
import { useAuthStore } from '@/stores/auth';
import { useOfficeLibrary } from '@/composables/useOfficeLibrary';
import { useToggle } from '@/utils/toggler';
import { computed, onMounted, ref, watch } from 'vue';
import Textarea from '../ui/textareas/Textarea.vue';
import BaseButton from '../ui/buttons/BaseButton.vue';
import BaseRadioButton from '../ui/buttons/BaseRadioButton.vue';
import PlusIcon from '../ui/icons/PlusIcon.vue';
import MinusIcon from '../ui/icons/MinusIcon.vue';
import { useTransaction } from '@/composables/useTransaction';
import type { Recipient } from '@/types';
import type { Transaction } from '@/types/transaction';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleReleaseModal: { type: Function, default: null },
    trxNo: { type: String, required: true },
    transaction: { type: Object as () => Transaction, default: null },
});

const emit = defineEmits(['released']);

const authStore = useAuthStore();
const { releaseDocument: callRelease } = useTransaction();
const officeLibrary = useOfficeLibrary();
const { loadOfficeLibrary } = useLibraryStore();

// ─── Form state ─────────────────────────────────────────────────────────────
const remarks = ref('');
const routingType = ref<'Single' | 'Multiple' | 'Sequential'>('Single');
const recipients_default = ref<Recipient[]>([]);
const recipients_cc = ref<Recipient[]>([]);
const recipients_bcc = ref<Recipient[]>([]);
const isSubmitting = ref(false);
const submitError = ref('');

const toggleRecipientCC = useToggle();
const toggleRecipientBCC = useToggle();

// ─── Hydrate from existing transaction ──────────────────────────────────────
watch(() => props.isOpen, (open) => {
    if (open && props.transaction) {
        routingType.value = props.transaction.routing || 'Single';
        remarks.value = '';
        submitError.value = '';

        const allOffices = officeLibrary.office.value ?? [];
        const defaults: Recipient[] = [];
        const cc: Recipient[] = [];
        const bcc: Recipient[] = [];

        for (const r of props.transaction.recipients ?? []) {
            const match = allOffices.find(o => o.return_value.office_code === r.office_id);
            const recipient: Recipient = match
                ? { ...match.return_value, sequence: r.sequence }
                : { office_code: r.office_id, office: r.office_name, region: '', service: '', sequence: r.sequence };

            if (r.recipient_type === 'cc') cc.push(recipient);
            else if (r.recipient_type === 'bcc') bcc.push(recipient);
            else defaults.push(recipient);
        }

        recipients_default.value = defaults;
        recipients_cc.value = cc;
        recipients_bcc.value = bcc;

        if (cc.length > 0) toggleRecipientCC.on();
        if (bcc.length > 0) toggleRecipientBCC.on();
    }
});

// ─── Mutual exclusivity + own-office exclusion ──────────────────────────────
const availableOffices = computed(() =>
    (officeLibrary.office.value ?? []).filter(
        (o) => o.return_value.office_code !== authStore.user?.office_id
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

// ─── Remove helpers ─────────────────────────────────────────────────────────
const removeFromDefault = (r: Recipient) => {
    recipients_default.value = recipients_default.value.filter(x => x.office_code !== r.office_code);
};
const removeFromCC = (r: Recipient) => {
    recipients_cc.value = recipients_cc.value.filter(x => x.office_code !== r.office_code);
};
const removeFromBCC = (r: Recipient) => {
    recipients_bcc.value = recipients_bcc.value.filter(x => x.office_code !== r.office_code);
};

// ─── Build flat recipients array ────────────────────────────────────────────
function buildRecipients() {
    const all: any[] = [];
    recipients_default.value.forEach((r, i) => {
        all.push({ office_code: r.office_code, office: r.office, recipient_type: 'default', sequence: i + 1 });
    });
    recipients_cc.value.forEach((r, i) => {
        all.push({ office_code: r.office_code, office: r.office, recipient_type: 'cc', sequence: i + 1 });
    });
    recipients_bcc.value.forEach((r, i) => {
        all.push({ office_code: r.office_code, office: r.office, recipient_type: 'bcc', sequence: i + 1 });
    });
    return all;
}

// ─── Release ────────────────────────────────────────────────────────────────
const releaseDocument = async () => {
    submitError.value = '';

    if (recipients_default.value.length === 0) {
        submitError.value = 'At least one recipient (Send To) is required.';
        return;
    }

    isSubmitting.value = true;
    try {
        await callRelease(props.trxNo, {
            remarks: remarks.value || null,
            routing: routingType.value,
            recipients: buildRecipients(),
        });
        props.toggleReleaseModal?.();
        emit('released');
    } catch (e: any) {
        submitError.value = e.response?.data?.message ?? e.message ?? 'Release failed.';
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(async () => {
    loadOfficeLibrary();
    await officeLibrary.fetchOffices();
});
</script>

<template>
    <LargeModal title="Release Document" :isOpen="props.isOpen" @close="props.toggleReleaseModal"
        @confirm="releaseDocument">
        <div>
            <form @submit.prevent="releaseDocument">
                <div class="max-h-[calc(100svh-15rem)] px-4 overflow-auto space-y-4">

                    <!-- Remarks -->
                    <div class="mt-4">
                        <div class="flex items-center mb-2">
                            <FrmLabel :label="'Remarks'" />
                            <span class="text-[10px] italic ml-1 text-gray-500">(Optional)</span>
                        </div>
                        <Textarea v-model="remarks" />
                    </div>

                    <!-- Routing Type -->
                    <div>
                        <FrmLabel :label="'Routing Type'" class="mb-2" />
                        <ul
                            class="flex text-sm font-medium text-gray-900 bg-white border border-gray-200 divide-x divide-gray-300 rounded-lg">
                            <li class="w-full">
                                <BaseRadioButton v-model="routingType" :name="'release-routing'" label="Single"
                                    :value="'Single'" />
                            </li>
                            <li class="w-full">
                                <BaseRadioButton v-model="routingType" :name="'release-routing'" label="Multiple"
                                    :value="'Multiple'" />
                            </li>
                            <li class="w-full">
                                <BaseRadioButton v-model="routingType" :name="'release-routing'" label="Sequential"
                                    :value="'Sequential'" />
                            </li>
                        </ul>
                    </div>

                    <!-- Send To (default recipients) -->
                    <div>
                        <FrmLabel :label="'Send To'" class="mb-2" />
                        <SearchMultiSelectOffice v-model="recipients_default" :items="officesForDefault" :placeholder="recipients_default.length
                            ? recipients_default.length + ' recipients selected'
                            : 'Select Recipient'" :isLoading="officeLibrary.isLoading.value" :has-desc="true"
                            :is-multiple="routingType === 'Multiple' || routingType === 'Sequential'" />
                        <ul v-if="recipients_default.length"
                            class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                            <li v-for="recipient in recipients_default" :key="recipient.office_code"
                                class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                                <div class="col-span-10">
                                    <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{
                                        recipient.service }}</div>
                                    {{ recipient.office }}
                                </div>
                                <div class="flex items-center justify-end col-span-2">
                                    <button type="button" @click="removeFromDefault(recipient)"
                                        class="p-2 text-red-500 rounded-full hover:bg-gray-100">
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

                    <!-- CC Recipients -->
                    <div class="p-2 border border-gray-200 rounded-md">
                        <div class="flex items-center justify-between">
                            <FrmLabel :label="'Cc'" :isOptional="true" />
                            <button type="button" @click="toggleRecipientCC.toggle()">
                                <PlusIcon class="size-4" v-if="!toggleRecipientCC.isToggled.value" />
                                <MinusIcon class="size-4" v-else />
                            </button>
                        </div>
                        <div v-if="toggleRecipientCC.isToggled.value" class="mt-3">
                            <SearchMultiSelectOffice v-model="recipients_cc" :items="officesForCC"
                                placeholder="Select CC Recipients" :isMultiple="true"
                                :isLoading="officeLibrary.isLoading.value" />
                            <ul
                                class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                                <li v-if="recipients_cc.length === 0">
                                    <div class="p-3 text-xs text-center text-gray-600 bg-white">No recipients
                                        selected.</div>
                                </li>
                                <li v-else v-for="recipient in recipients_cc" :key="recipient.office_code"
                                    class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                                    <div class="col-span-10">
                                        <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{
                                            recipient.service }}</div>
                                        {{ recipient.office }}
                                    </div>
                                    <div class="flex items-center justify-end col-span-2">
                                        <button type="button" @click="removeFromCC(recipient)"
                                            class="p-2 text-red-500 rounded-full hover:bg-gray-100">
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

                    <!-- BCC Recipients -->
                    <div class="p-2 border border-gray-200 rounded-md">
                        <div class="flex items-center justify-between">
                            <FrmLabel :label="'Bcc'" :isOptional="true" />
                            <button type="button" @click="toggleRecipientBCC.toggle()">
                                <PlusIcon class="size-4" v-if="!toggleRecipientBCC.isToggled.value" />
                                <MinusIcon class="size-4" v-else />
                            </button>
                        </div>
                        <div v-if="toggleRecipientBCC.isToggled.value" class="mt-3">
                            <SearchMultiSelectOffice v-model="recipients_bcc" :items="officesForBCC"
                                placeholder="Select BCC Recipients" :isMultiple="true"
                                :isLoading="officeLibrary.isLoading.value" />
                            <ul
                                class="mt-2 border border-gray-300 divide-y divide-gray-200 rounded-lg overflow-clip">
                                <li v-if="recipients_bcc.length === 0">
                                    <div class="p-3 text-xs text-center text-gray-600 bg-white">No recipients
                                        selected.</div>
                                </li>
                                <li v-else v-for="recipient in recipients_bcc" :key="recipient.office_code"
                                    class="grid items-center grid-cols-12 p-3 text-xs text-gray-600 bg-white">
                                    <div class="col-span-10">
                                        <div style="font-size: 10px" class="text-gray-400">{{ recipient.region }} - {{
                                            recipient.service }}</div>
                                        {{ recipient.office }}
                                    </div>
                                    <div class="flex items-center justify-end col-span-2">
                                        <button type="button" @click="removeFromBCC(recipient)"
                                            class="p-2 text-red-500 rounded-full hover:bg-gray-100">
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
            </form>
        </div>
        <template #footer>
            <div class="space-y-2">
                <p v-if="submitError" class="text-xs text-red-600">{{ submitError }}</p>
                <div class="flex justify-end gap-2">
                    <BaseButton textColorClass="text-gray-700" backgroundClass="bg-gray-200 hover:bg-gray-300"
                        :action="toggleReleaseModal">
                        Cancel
                    </BaseButton>
                    <BaseButton :action="releaseDocument" :disabled="isSubmitting">
                        {{ isSubmitting ? 'Releasing...' : 'Release Document' }}
                    </BaseButton>
                </div>
            </div>
        </template>
    </LargeModal>
</template>
