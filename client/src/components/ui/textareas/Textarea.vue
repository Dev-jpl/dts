<script setup lang="ts">
import { useAttrs, computed } from "vue";

const props = defineProps<{
  modelValue?: string | null;
  label?: string;
  rows?: string;
  placeholder?: string;
}>();

const emit = defineEmits(["update:modelValue"]);
const attrs = useAttrs();

const wordCount = computed(() => {
  const text = props.modelValue ?? "";
  return text.trim().length === 0 ? 0 : text.trim().split(/\s+/).length;
});

const charCount = computed(() => {
  return (props.modelValue ?? "").length;
});
</script>

<template>
  <label for="message" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
    {{ props.label }}
  </label>

  <textarea :value="props.modelValue" @input="(event) => {
    const target = event.target as HTMLTextAreaElement | null;
    if (target) emit('update:modelValue', target.value);
  }" :rows="props.rows ?? 4" v-bind="attrs"
    class="block p-2.5 w-full text-sm text-gray-900 bg-white rounded-lg border border-gray-300 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-lime-500 dark:focus:border-lime-500"
    :placeholder="props.placeholder ?? 'Text here'"></textarea>
  <!-- Live Word Count -->
  <div class="mt-1 text-xs text-right text-gray-500">
    Words: {{ wordCount }} | Characters: {{ charCount }}
  </div>
</template>
