<script setup lang="ts">
import { defineProps, computed } from "vue";

const props = withDefaults(defineProps<{
  btnText?: string;
  action?: () => void;
  className?: string;
  backgroundClass?: string;
  textColorClass?: string;
  disabled?: boolean;
}>(), {
  btnText: '',
  action: undefined,
  disabled: false,
});

// Defaults
const defaultBackground = "bg-teal-700 hover:bg-teal-800";
const defaultTextColor = "text-white";

// Computed classes
const buttonClass = computed(() => {
  const bg = props.backgroundClass || defaultBackground;
  const text = props.textColorClass || defaultTextColor;
  const base = `px-2.5 py-2 rounded-md hover:cursor-pointer ${text} transition font-medium text-xs ${bg}`;
  return props.className ? `${props.className} ${base}` : base;
});
</script>

<template>
  <button type="button" @click="action" :class="buttonClass" :disabled="disabled">
    <slot></slot>
    <span v-if="btnText" class="hidden sm:block">
      {{ btnText }}
    </span>
  </button>
</template>
