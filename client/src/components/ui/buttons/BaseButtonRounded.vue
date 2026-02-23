<script setup lang="ts">
import { defineProps } from "vue";

import { computed } from "vue";

const props = defineProps<{
    btnText: string;
    action: () => void;
    className?: string;
    backgroundClass?: string;
}>();

const backgroundClass = "bg-teal-700 hover:bg-teal-800";

const baseClass = `px-4 py-2 rounded-full hover:cursor-pointer ${props.backgroundClass || backgroundClass} text-white  transition font-medium text-xs`;

const buttonClass = computed(() =>
    props.className ? `${props.className} ${baseClass}` : baseClass,
);
</script>

<template>
    <button type="button" @click="props.action" :class="buttonClass">
        <slot></slot>
        <span class="hidden sm:block">
            {{ props.btnText }}
        </span>
    </button>
</template>
