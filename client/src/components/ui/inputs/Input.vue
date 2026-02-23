<script setup lang="ts">
const props = defineProps<{
  modelValue?: any;
  placeholder?: string;
  readonly?: boolean;
  autocomlete?: string;
  noFocus?: boolean; // <-- Add this prop
}>();

const emit = defineEmits(["update:modelValue"]);
</script>

<template>
  <div class="relative">
    <input
      type="text"
      :readonly="props.readonly"
      :value="props.modelValue"
      :placeholder="props.placeholder ?? ''"
      :autocomplete="props.autocomlete ?? 'off'"
      :class="[
        'w-full p-2 text-xs bg-white border border-gray-300 rounded-lg',
        props.noFocus ? 'focus:border-transparent focus:ring-0' : '',
      ]"
      @input="(e) => {
  const target = e.target as HTMLInputElement | null;
  if (target) emit('update:modelValue', target.value);
}"
    />
  </div>
</template>
