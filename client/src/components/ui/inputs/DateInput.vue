<script setup lang="ts">
const props = defineProps<{ modelValue?: string | number | Date }>();
const emit = defineEmits(["update:modelValue"]);

// Helper to format Date to YYYY-MM-DD
function formatDate(val: string | number | Date | undefined) {
  if (!val) return "";
  if (val instanceof Date) {
    // Format Date object
    return val.toISOString().slice(0, 10);
  }
  return String(val);
}
</script>
<template>
  <div class="relative">
    <input
      type="date"
      class="w-full p-2 text-xs bg-white border border-gray-300 rounded-lg"
      :value="formatDate(props.modelValue)"
      @input="event => {
    const target = event.target as HTMLInputElement | null;
    if (target) emit('update:modelValue', target.value);
  }"
      :placeholder="props.modelValue ? '' : 'Select a date'"
    />
  </div>
</template>
