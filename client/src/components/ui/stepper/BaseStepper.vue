<script setup lang="ts">
import type { Step } from "@/types/step";
import { useStepNavigation } from "@/composables/useStepNavigation";
const { currentStep, steps, nextStep, prevStep, setStep } = useStepNavigation();
</script>

<template>
  <div class="grid grid-cols-4 md:divide-y md:grid-cols-1 md:grid-flow-row">
    <button
      type="button"
      v-for="step in steps"
      :key="step.title"
      :class="[
        'relative hover:cursor-pointer items-start w-full text-left',
        step.stepNumber === currentStep
          ? 'bg-gray-50 border-b-4 border-r-0 sm:border-b-1 border-b-lime-600 sm:border-b-gray-200 sm:border-r-4 sm:border-r-lime-600'
          : 'bg-gray-100',
      ]"
    >
      <div class="flex items-start p-2 sm:p-4">
        <div
          class="flex items-center justify-center mx-auto rounded-full h-7 min-w-7 sm:h-10 sm:mx-0 sm:min-w-10 sm:mr-3"
          :class="step.isDone ? 'bg-lime-600' : 'bg-gray-400'"
        >
          <template v-if="step.isDone">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-5 h-5 text-white"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </template>
          <template v-else>
            <span class="text-[10px] sm:text-sm font-semibold text-white">{{
              step.stepNumber
            }}</span>
          </template>
        </div>
        <div class="text-left">
          <h3 class="hidden text-xs font-semibold text-gray-900 sm:block">
            {{ step.title }}
          </h3>
          <p
            :class="step.isDone ? 'text-gray-600' : 'text-gray-400'"
            class="hidden text-xs font-light lg:block"
          >
            {{ step.description }}
          </p>
        </div>
      </div>
      <!-- <div
        v-if="step.stepNumber === props.currentStep"
        class="absolute w-full h-1 -bottom-0 bg-lime-400"
      ></div> -->
    </button>
  </div>
</template>
