<script setup lang="ts">
const props = defineProps<{
  status: string;
}>();

const statusBg = (status: string): string => {
  switch (status) {
    case "Processing":
      return "bg-gray-400";

    case "Completed":
      return "bg-lime-600";

    default:
      return "bg-gray-400";
  }
};
</script>
<template>
  <span :class="statusBg(props.status)"
    class="px-2 border shadow-2xl py-1 text-white uppercase text-[9px] font-black rounded-full">
    {{ props.status }}
  </span>
</template>
