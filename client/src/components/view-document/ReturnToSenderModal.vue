<script setup lang="ts">
import { ref } from 'vue';
import ModalDialog from '../ui/modals/ModalDialog.vue';
import Textarea from '../ui/textareas/Textarea.vue';
import LargeModal from '../ui/modals/LargeModal.vue';
import BaseButton from '../ui/buttons/BaseButton.vue';
import FrmLabel from '../ui/labels/FrmLabel.vue';
const props = defineProps({
    isOpen: { type: Boolean, default: false },
    toggleReturnModal: { type: Function, default: null } //This is an function/method
});

const remarks = ref<string>('');
</script>
<template>
    <LargeModal title="Return To Sender" :isOpen="props.isOpen" @close="props.toggleReturnModal"
        @confirm="props.toggleReturnModal">

        <!-- Are you sure you want to return this document? -->
        <div class="p-4">
            <div class="flex">
                <FrmLabel :label="'Remarks'" :isRequired="true" />
            </div>
            <Textarea placeholder="Enter remarks for returning document" v-model="remarks" class="mb-2" />
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton @click="props.toggleReturnModal" backgroundClass="bg-gray-200" textColorClass="text-black">
                    Cancel
                </BaseButton>
                <BaseButton @click="props.toggleReturnModal" class="px-4 py-2 text-white bg-red-600 rounded">
                    Return Document
                </BaseButton>
            </div>
        </template>
    </LargeModal>
</template>