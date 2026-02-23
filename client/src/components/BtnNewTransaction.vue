<template>
  <div class="relative mt-9 mb-7">
    <button
      @click="toggleOptions"
      class="flex justify-center w-full p-3 text-xs bg-teal-800 rounded-lg hover:bg-teal-900 hover:cursor-pointer md:p-3"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        class="size-4 text-lime-300 md:mr-2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 4.5v15m7.5-7.5h-15"
        />
      </svg>

      <span class="hidden text-white md:block"> New Transaction </span>
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        class="hidden text-white size-4 md:ml-auto md:block"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="m19.5 8.25-7.5 7.5-7.5-7.5"
        />
      </svg>
    </button>
    <div v-if="showOptions">
      <div
        class="absolute w-full mt-2 transition-all ease-in-out bg-teal-900 rounded-lg shadow-lg duration-400 overflow-clip"
      >
        <RouterLink
          v-for="option in optionItems"
          class="flex items-center px-4 py-3 text-xs text-white hover:bg-teal-800 hover:cursor-pointer "
          :to="{ name: option.route.name }"
        >
          <span v-html="option.icon"></span>

          <div>{{ option.name }}</div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watchEffect } from "vue";
import { useRoute } from "vue-router";
const router = useRoute();

const showOptions = ref<boolean>(false);

const optionItems = [
  {
    name: "New Document",
    route: { name: "new-document" },
    icon: ` <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="mr-3 size-4 hover:text-lime-500"
          >
            <path
              d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z"
            />
          </svg>`,
  },
  {
    name: "Receive Document",
    route: { name: "received-document" },
    icon: `
    <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="mr-3 size-4 hover:text-lime-500"
          >
            <path
              fill-rule="evenodd"
              d="M9.75 6.75h-3a3 3 0 0 0-3 3v7.5a3 3 0 0 0 3 3h7.5a3 3 0 0 0 3-3v-7.5a3 3 0 0 0-3-3h-3V1.5a.75.75 0 0 0-1.5 0v5.25Zm0 0h1.5v5.69l1.72-1.72a.75.75 0 1 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 1 1 1.06-1.06l1.72 1.72V6.75Z"
              clip-rule="evenodd"
            />
            <path
              d="M7.151 21.75a2.999 2.999 0 0 0 2.599 1.5h7.5a3 3 0 0 0 3-3v-7.5c0-1.11-.603-2.08-1.5-2.599v7.099a4.5 4.5 0 0 1-4.5 4.5H7.151Z"
            />
          </svg>
    `,
  },
  {
    name: "Release Document",
    route: { name: "release-document" },
    icon: `
     <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24 "
            fill="currentColor"
            class="mr-3 size-4 hover:text-lime-500"
          >
            <path
              d="M9.97.97a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1-1.06 1.06l-1.72-1.72v3.44h-1.5V3.31L8.03 5.03a.75.75 0 0 1-1.06-1.06l3-3ZM9.75 6.75v6a.75.75 0 0 0 1.5 0v-6h3a3 3 0 0 1 3 3v7.5a3 3 0 0 1-3 3h-7.5a3 3 0 0 1-3-3v-7.5a3 3 0 0 1 3-3h3Z"
            />
            <path
              d="M7.151 21.75a2.999 2.999 0 0 0 2.599 1.5h7.5a3 3 0 0 0 3-3v-7.5c0-1.11-.603-2.08-1.5-2.599v7.099a4.5 4.5 0 0 1-4.5 4.5H7.151Z"
            />
          </svg>
    `,
  },
];

const toggleOptions = (): void => {
  showOptions.value = !showOptions.value;
};

watchEffect(() => {
  // closes the dropdown or popup on route change
  void router.name; // establishes reactive dependency
  showOptions.value = false;
});
</script>
