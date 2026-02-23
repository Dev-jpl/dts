<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from "vue";
import { vAutofocus } from "@/directives/vAutofocus";

const props = defineProps<{
  items: Array<{
    item_id: string | number;
    item_title: string;
    item_desc?: string;
    return_value?: any; // Optional additional info
  }>;
  modelValue?: any | number | null;
  placeholder?: string;
  hasDesc?: boolean;
  isLoading?: boolean;
  isMultiple?: boolean; // If true, allows multiple selections
}>();

console.log(props.items);


const emit = defineEmits(["update:modelValue"]);

const showOptions = ref(false);
const search = ref("");
const dropdownRef = ref<HTMLElement | null>(null);

const filteredItems = computed(() =>
  props.items.filter(
    (item) =>
      item.item_title.toLowerCase().includes(search.value.toLowerCase()) ||
      (item.item_desc &&
        item.item_desc.toLowerCase().includes(search.value.toLowerCase()))
  )
);

function handleSelect(item: any) {
  const newRecipient = item.return_value;

  if (props.isMultiple) {
    const updated = Array.isArray(props.modelValue)
      ? [...props.modelValue]
      : [];

    const exists = updated.some(
      (r) => r.office_code === newRecipient.office_code
    );

    const filtered = updated.filter(
      (r) => r.office_code !== newRecipient.office_code
    );

    emit("update:modelValue", exists ? filtered : [...updated, newRecipient]);
  } else {
    emit("update:modelValue", [item.return_value]);
    showOptions.value = false;
    search.value = "";
  }
}

function onInputClick() {
  showOptions.value = showOptions.value ? false : true;
  search.value = "";
}

function closeDropdown() {
  showOptions.value = false;
}

const selectedItem = computed(
  () => props.items?.find((i) => i.item_id === props.modelValue) ?? null
);

const isSelected = (item: any) => {
  if (props.isMultiple && Array.isArray(props.modelValue)) {
    return props.modelValue.some(
      (v) => v.office_code === item.return_value.office_code
    );
  }
  return props.modelValue === item.item_id;
};

const selectedItems = computed(() => {
  if (!props.isMultiple || !Array.isArray(props.modelValue)) return [];
  return props.items.filter((i) => props.modelValue.includes(i.item_id));
});

const dropDirection = ref<"down" | "up">("down");

const updateDropdownDirection = () => {
  nextTick(() => {
    const el = dropdownRef.value;
    if (!el) return;

    const rect = el.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    dropDirection.value = spaceBelow < 200 && spaceAbove > 200 ? "up" : "down";
  });
};

onMounted(() => {
  updateDropdownDirection();
  window.addEventListener("resize", updateDropdownDirection);
});

onBeforeUnmount(() => {
  console.log(dropDirection);

  window.removeEventListener("resize", updateDropdownDirection);
});


const uniqueId = `trigger-${Math.random().toString(36).substr(2, 9)}`;
const trigger = ref<HTMLElement | null>(null);
// const triggerRef = ref<HTMLInputElement | null>(null);

const dropdownStyle = computed(() => {
  if (!trigger.value) return {};
  const rect = trigger.value.getBoundingClientRect();
  return {
    position: "absolute",
    top: `${rect.bottom}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`,
  };
});
</script>

<template>
  <div v-outside-click="closeDropdown" class="relative w-full" ref="dropdownRef">
    <!-- Main input: not editable, shows selected item -->

    <input :id="uniqueId" ref="trigger" type="text" :value="selectedItem ? selectedItem.item_title : ''"
      :placeholder="props.placeholder ?? 'Select Item'"
      class="w-full p-2 text-xs bg-white border border-gray-300 rounded-lg hover:cursor-pointer focus:outline-none focus:ring-0"
      readonly @click="onInputClick" autocomplete="off" />
    <!-- Caret Icon -->
    <span class="absolute inset-y-0 flex items-center pointer-events-none right-3">
      <svg v-if="!showOptions" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
        viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
        stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
      </svg>
    </span>
    <div>
      <!-- Dropdown with search input and options -->
      <template v-if="showOptions && props.isLoading">
        <div
          class="absolute z-10 w-full px-4 py-3 mt-1 text-xs text-gray-500 bg-white border border-gray-200 rounded-lg shadow">
          <div class="flex items-center justify-center">
            <div role="status">
              <svg aria-hidden="true"
                class="inline w-4 h-4 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600"
                viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                  fill="currentColor" />
                <path
                  d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                  fill="currentFill" />
              </svg>
              <span class="sr-only">Loading...</span>
            </div>
            Loading...
          </div>
        </div>
      </template>
      <template v-else>
        <teleport to="body">
          <!-- <div v-if="showOptions" :class="['absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow',dropDirection === 'up' ? 'bottom-full mb-1' : 'mt-1',]" tabindex="0"> -->
          <div v-if="showOptions"
            :class="['absolute z-[2000] w-full bg-white border border-gray-200 rounded-lg shadow', dropDirection === 'up' ? 'bottom-full mb-1' : 'mt-1',]"
            :style="dropdownStyle">
            <input type="text" v-model="search" placeholder="Search..."
              class="w-full p-3 text-xs border-b border-gray-200 focus:outline-none focus:ring-0" v-autofocus />
            <template v-if="isLoading">
              <span class="block text-xs text-gray-500">Loading...</span>
            </template>
            <template v-else>
              <ul class="overflow-y-auto bg-white divide-gray-200 max-h-60 ivide-y">
                <li v-for="item in filteredItems" :key="item.item_id" @mousedown.prevent="handleSelect(item)" :class="[
                  'px-2 grid grid-cols-12 py-3 text-xs cursor-pointer',
                  isSelected(item) ? 'bg-gray-100' : 'hover:bg-gray-100',
                ]">
                  <div v-show="isMultiple" class="flex items-start justify-center col-span-1">
                    <svg v-if="isSelected(item)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                      fill="currentColor" class="size-6 text-lime-600">
                      <path fill-rule="evenodd"
                        d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                        clip-rule="evenodd" />
                    </svg>
                    <div v-else class="mt-1 bg-gray-200 rounded-full ring-2 ring-gray-300 size-4"></div>
                  </div>
                  <div class="col-span-10 pl-2">
                    <div class="text-xs sentence">{{ item.item_title }}</div>
                    <div v-if="hasDesc" class="text-[10px] text-gray-500">
                      {{ item.item_desc }}
                    </div>
                  </div>
                </li>
              </ul>
            </template>
          </div>
        </teleport>
      </template>
    </div>
  </div>
</template>
