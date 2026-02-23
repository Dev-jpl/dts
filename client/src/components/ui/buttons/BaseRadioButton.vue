<script setup lang="ts">
const props = defineProps<{
  label: string;
  modelValue?: any;
  value: any; // value for this radio option
  name?: string ; // optional name for grouping
}>();
const inputId = `radio-${props.value}`;
const emit = defineEmits(["update:modelValue"]);
</script>

<template>
  <div class="flex items-center ps-3 hover:bg-gray-100 dark:hover:bg-gray-600">
    <input
      type="radio"
      :id="inputId"
      :checked="props.modelValue === props.value"
      :value="props.value"
      @change="emit('update:modelValue', props.value)"
      :name="props.name || 'radio-group'"
      class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
    />
    <label
      :for="inputId"
      class="w-full py-2.5 text-xs font-medium text-gray-900 ms-2 dark:text-gray-300"
      >{{ props.label }}</label
    >
  </div>
</template>
