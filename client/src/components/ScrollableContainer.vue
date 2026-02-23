<script setup lang="ts">
const props = defineProps<{
  px?: string;
  rem?: string;
  padding?: string;
  background?: string;
}>();
</script>
<template>
  <div :class="`h-[calc(100svh-${(props.rem) || '50'}px)] p-${padding || '5'} bg-${background || 'bg-gradient-to-b from-gray-100 to-gray-200'
    }`" class="flex flex-col items-center h-[calc(100svh-50px)] shadow-lg overflow-auto">
    <slot></slot>
  </div>
</template>
