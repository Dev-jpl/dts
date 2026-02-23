<script setup>
import { useRoute, useRouter } from "vue-router";
import { computed, ref } from "vue";
import { onClickOutside } from "@vueuse/core";
import { vOnClickOutside } from "@vueuse/components";
import { HiOutlineHome } from "vue-icons-plus/hi2";

const route = useRoute();
const router = useRouter();

const target = ref < HTMLElement > "target";
const activeDropdown = ref(null);

function toggleDropdown(index) {
  activeDropdown.value = activeDropdown.value === index ? null : index;
}
function closeDropdown() {
  activeDropdown.value = null;
}

const breadcrumbs = computed(() => {
  const trail = [];
  const routeName = route.name;
  const allRoutes = router.getRoutes();
  const currentRoute = allRoutes.find((r) => r.name === routeName);
  if (!currentRoute) return trail;

  const visited = new Set();
  let current = currentRoute;

  while (current) {
    const meta = current.meta || {};
    const bc = meta.breadcrumb || {};
    const label = meta.label || current.name;

    if (visited.has(current.name)) break;
    visited.add(current.name);

    let dropdownItems = [];

    if (bc.group && !bc.isGroupRoot) {
      const groupRoutes = allRoutes
        .filter((r) => {
          const rMeta = r.meta?.breadcrumb;
          return (
            rMeta?.group === bc.group &&
            r.name !== current.name &&
            !rMeta?.isGroupRoot
          );
        })
        .sort((a, b) => {
          const labelA = a.meta.label.toLowerCase();
          const labelB = b.meta.label.toLowerCase();
          return labelA.localeCompare(labelB);
        });

      trail.unshift({
        label: meta.label,
        path: current.path,
        disabled: false,
        dropdown: groupRoutes.length > 0,
        children: groupRoutes.map((r) => ({
          label: r.meta.label,
          path: r.path,
        })),
      });
    } else {
      trail.unshift({
        label,
        path: current.path,
        disabled: !current.component,
        dropdown: false,
        children: [],
      });
    }

    // Include parent even if it lacks component
    current = bc.parent ? allRoutes.find((r) => r.name === bc.parent) : null;
  }

  trail.unshift({
    label: "Home",
    path: "/dashboard",
    disabled: false,
    dropdown: false,
    children: [],
  });

  return trail;
});

// const breadcrumbs = computed(() => getBreadcrumbTrail());

function navigateTo(path) {
  router.push(path);
}
</script>

<template>
  <nav
    class="justify-between hidden text-gray-700 sm:flex dark:bg-gray-800 dark:border-gray-700"
    aria-label="Breadcrumb"
  >
    <ol
      class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse"
    >
      <li
        v-for="(crumb, index) in breadcrumbs"
        :key="index"
        class="relative flex items-center"
      >
        <template v-if="index > 0">
          <svg
            class="w-3 h-3 mx-2 text-gray-400 rtl:rotate-180"
            viewBox="0 0 6 10"
            fill="none"
          >
            <path
              d="m1 9 4-4-4-4"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </template>

        <!-- Dropdown -->
        <div
          v-if="crumb.dropdown"
          class="relative group"
        >
          <button
            @click="toggleDropdown(index)"
            class="flex items-center px-3 py-2 text-xs font-light text-gray-700 transition-all duration-200 border border-gray-100 rounded-full focus:outline-white focus:outline-2 focus:bg-white hover:bg-white hover:border hover:border-gray-200 dark:text-gray-400 dark:hover:text-white"
          >
            {{ crumb.label }}
            <svg
              class="w-2.5 h-2.5 ml-2"
              viewBox="0 0 10 6"
              fill="none"
            >
              <path
                d="m1 1 4 4 4-4"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
          <div
            v-outside-click="closeDropdown"
            v-show="activeDropdown === index"
            class="absolute left-0 z-10 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg w-44 top-full dark:bg-gray-700"
          >
            <ul
              class="text-sm text-gray-700 divide-y divide-gray-100 dark:text-gray-200"
            >
              <li
                v-for="child in crumb.children"
                :key="child.path"
              >
                <button
                  @click="navigateTo(child.path)"
                  class="block w-full px-4 py-2 text-xs font-light text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  {{ child.label }}
                </button>
              </li>
            </ul>
          </div>
        </div>

        <!-- Disabled -->
        <span
          v-else-if="crumb.disabled"
          class="ml-2 text-xs font-light text-gray-600"
        >
          {{ crumb.label }}
        </span>

        <!-- Normal -->
        <router-link
          v-else
          :to="crumb.path"
          class="ml-2 text-xs font-light text-gray-700 hover:text-teal-700 dark:text-gray-400 dark:hover:text-white"
        >
          {{ crumb.label }}
        </router-link>
      </li>
    </ol>
  </nav>
</template>
