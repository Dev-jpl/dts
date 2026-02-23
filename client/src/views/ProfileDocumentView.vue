<script setup lang="ts">
import FormStep1 from "@/components/transactions/FormStep1.vue";
import FormStep2 from "@/components/transactions/FormStep2.vue";
import FormStep3 from "@/components/transactions/FormStep3.vue";
import FormStep4 from "@/components/transactions/FormStep4.vue";
import BaseButton from "@/components/ui/buttons/BaseButton.vue";
import BaseStepper from "@/components/ui/stepper/BaseStepper.vue";
import type { Step, DocumentInfo } from "@/types";
import { onMounted, ref } from "vue";
import { useStepNavigation } from "@/composables/useStepNavigation";
import { useReplyDocument } from "@/composables/useReplyDoc";
const { currentStep, steps } = useStepNavigation();


const { documentNo, isReply } = useReplyDocument();
</script>
<template>
  <div class="flex flex-col h-full bg-gray-100 border border-gray-200 shadow-lg md:grid md:grid-cols-12">
    <div class="border-gray-200 md:h-full sm:col-span-2 sm:border-r">
      <BaseStepper class="divide-x divide-gray-200" />
    </div>
    <div class="h-full sm:col-span-10">
      <!-- Profiling of documents -->
      <div v-show="currentStep === 1">
        <FormStep1 />
      </div>

      <!-- Uploading of scanned documents -->
      <div v-show="currentStep === 2">
        <FormStep2 />
      </div>

      <!-- Summary of document -->
      <div v-show="currentStep === 3">
        <FormStep3 />
      </div>

      <!-- attachment of qr code -->
      <div v-show="currentStep === 4">
        <FormStep4 />
      </div>
    </div>
  </div>
</template>
