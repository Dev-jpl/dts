<script setup lang="ts">
import { defineProps, computed } from "vue";

const props = defineProps<{
    btnText: string;
    isActive: boolean;
    action: () => void;
    className?: string;
    backgroundClass?: string;
}>();

// Compute button classes
const buttonClass = computed(() => {
    const background = props.backgroundClass || (props.isActive ? "bg-gray-200" : "bg-white hover:bg-gray-200");
    const base = `px-2.5 text-gray-900 py-2 hover:cursor-pointer ${background} flex transition font-medium text-xs`;
    return props.className ? `${props.className} ${base}` : base;
});
</script>

<template>
    <button type="button" @click="action" :class="buttonClass">
        <slot></slot>
        <span class="hidden sm:block">
            {{ btnText }}
        </span>
    </button>
</template>
