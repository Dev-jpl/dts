<script setup lang="ts">
const props = defineProps<{
  label?: string;
  placeholder?: string;
  modelValue?: any;
}>();

const emit = defineEmits(["update:modelValue"]);
</script>

<template>
  <div class="relative">
    <span class="text-sm bg-gray-100">{{ props.label }}</span>
    <input
      type="text"
      :value="props.modelValue"
      :placeholder="props.placeholder ?? ''"
      class="w-full p-2 text-xs bg-white border border-gray-300 rounded-lg"
      @input="(e) => {
    const target = e.target as HTMLInputElement | null;
    if (target) emit('update:modelValue', target.value);
  }"
    />
  </div>
</template>
