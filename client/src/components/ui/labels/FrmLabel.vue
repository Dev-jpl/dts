<script setup lang="ts">
const props = defineProps<{
  label: string;
  class?: string;
  isRequired?: boolean;
  isOptional?: boolean
}>();
</script>

<template>
  <div :class="['text-xs font-bold', props.class]">{{ props.label }}  <span v-if="isOptional" class="font-normal text-gray-400">(Optional)</span> <span v-show="isRequired" class="text-xs text-red-500">*</span></div>
</template>
