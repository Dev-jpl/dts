<script setup lang="ts">
import FrmLabel from "../labels/FrmLabel.vue";

const props = defineProps<{
  title: string;
  modelValue?: boolean;
  isDisabled?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: boolean): void;
}>();
</script>

<template>
  <label
    :class="[
      isDisabled
        ? 'opacity-50 cursor-not-allowed'
        : 'cursor-pointer transition',
    ]"
    class="inline-flex items-center"
  >
    <input
      type="checkbox"
      value=""
      class="sr-only peer"
      :disabled="isDisabled"
      :checked="props.modelValue"
      @change="(e) => {
    const target = e.target as HTMLInputElement | null;
    if(isDisabled) return false
    if (target) emit('update:modelValue', target.checked);
  }"
    />
    <div
      :class="isDisabled ? '' : ''"
      class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-lime-300 dark:peer-focus:ring-lime-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-lime-600 dark:peer-checked:bg-lime-600"
    ></div>
    <FrmLabel
      :class="isDisabled ? 'text-gray-400' : ''"
      class="ml-2"
      :label="props.title"
    />
  </label>
</template>
