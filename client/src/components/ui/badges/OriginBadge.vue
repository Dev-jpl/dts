<script setup lang="ts">
const props = defineProps<{
  originType: string;
}>();

const originBg = (originType: string): string => {
  switch (originType) {
    case "Internal":
      return "bg-lime-600";
    case "External":
      return "bg-amber-600";
    case "Email":
      return "bg-sky-500";

    default:
      return "bg-lime-200";
  }
};
</script>
<template>
  <span
    :class="originBg(props.originType)"
    class="px-2 shadow-2xl py-1 text-white uppercase text-[9px] font-black rounded-full"
    >{{ originType }}
  </span>
</template>
