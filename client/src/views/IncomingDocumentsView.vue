<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import { useExpandableTextArray } from "@/composables/useExpandableTextArray";
import { generateUUID } from "@/utils/generateUUID";
import { ref } from "vue";

const incomingDocuments = [
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
];

const { isExpanded, toggleExpanded, getDisplayText } =
  useExpandableTextArray(250);

const tabs = [
  { tabLabel: "For Action" },
  { tabLabel: "Processed" },
  { tabLabel: "Overdue" },
  { tabLabel: "All" },
];

import { watch, onMounted } from 'vue'

// const tabs = [
//   { tabLabel: 'Tab A' },
//   { tabLabel: 'Tab B' },
//   { tabLabel: 'Tab C' }
// ]

const activeTab = ref(tabs[0].tabLabel)
const tabRefs = ref([])
const indicatorStyle = ref({})

onMounted(() => {
  updateIndicator()
})

watch(activeTab, () => {
  updateIndicator()
})

function updateIndicator() {
  const index = tabs.findIndex(t => t.tabLabel === activeTab.value)
  const el = tabRefs.value[index]
  if (el) {
    indicatorStyle.value = {
      left: el.offsetLeft + 'px',
      width: el.offsetWidth + 'px'
    }
  }
}


</script>

<template>
  <ScrollableContainer padding="0" px="50px" background="white" class="bg-white">
    <!-- <div class="grid w-full grid-cols-5 gap-5">
      <div
        class="flex h-[100px] justify-between w-full col-span-2 p-4 transition-all duration-200 bg-white rounded-lg hover:shadow-md"
      >
        <div>
          <h3 class="text-xs font-medium">Total Incoming</h3>
          <div class="mt-4 text-lg font-bold">1,540</div>
        </div>
        <div class="px-5 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100">
          <h3 class="text-xs font-medium text-gray-400">Today</h3>
          <div class="mt-4 text-lg font-bold text-lime-500">1,540</div>
        </div>
      </div>
      <div
        class="w-full p-4 transition-all duration-200 bg-teal-700 rounded-lg hover:shadow-md"
      >
        <h3 class="text-xs font-medium text-gray-300">For Action</h3>
        <div class="mt-4 text-lg font-bold text-white">1,540</div>
      </div>
      <div
        class="w-full p-4 transition-all duration-200 bg-white rounded-lg hover:shadow-md"
      >
        <h3 class="text-xs font-medium">Over Due</h3>
        <div class="mt-4 text-lg font-bold">1,540</div>
      </div>
      <div
        class="w-full p-4 transition-all duration-200 bg-white rounded-lg hover:shadow-md"
      >
        <h3 class="text-xs font-medium">Processed</h3>
        <div class="mt-4 text-lg font-bold">1,540</div>
      </div>
    </div> -->

    <!-- Card Incoming -->
    <div class="w-full">

      <!-- header -->
      <div class="flex flex-col items-start justify-start w-full border-b border-gray-200 sm:items-end sm:flex-row sm:justify-between">
        <div class="p-4">
          <h1 class="font-bold text-gray-600">Incoming Documents</h1>
        </div>

        <!-- Tab Start -->
        <div class="relative flex divide-gray-200">
          <!-- Tabs -->
          <div v-for="tab in tabs" :key="tab.tabLabel" @click="activeTab = tab.tabLabel"
            class="px-3 mb-2 sm:w-[100px] py-2 text-xs text-center cursor-pointer"
            :class="activeTab === tab.tabLabel ? 'text-teal-700 font-bold' : 'text-gray-500'" ref="tabRefs"> {{
              tab.tabLabel }}
          </div>
          <!-- Sliding Indicator -->
          <div class="absolute bottom-0 h-1 transition-all duration-300 bg-amber-500 rounded-t-md"
            :style="indicatorStyle">
          </div>
        </div>
        <!-- Tab End -->
      </div>
      <!-- header End-->

      <!-- <hr class="text-gray-300 border-1" /> -->
      <!-- table -->
      <div>
        <!-- Search Input -->

      </div>
      <table class="w-full">
        <thead>
          <tr class="sticky bg-gray-100">
            <td width="15%" class="px-4 py-2 text-xs font-semibold">
              From
            </td>
            <td width="67%" class="px-4 py-2 text-xs font-semibold">
              Subject
            </td>
            <td width="10%" class="px-4 py-2 text-xs font-semibold text-right">
              Sent
            </td>
          </tr>
        </thead>
      </table>
      <div class="h-[calc(100svh-200px)] overflow-auto">
        <table class="w-full">
          <tbody class="divide-y divide-gray-200">
            <tr v-for="incomingDoc in incomingDocuments" class="hover:bg-gray-100 hover:cursor-pointer" @click="
              $router.push({
                name: 'view-document',
                params: { doc_no: incomingDoc.documentNumber },
              })
              ">
              <td width="15%" class="p-3 text-xs font-light align-top">
                <div>
                  {{ incomingDoc.from }}
                </div>
              </td>
              <td width="67%" class="p-3 align-top">
                <div class="text-xs font-normal">
                  <span class="font-bold"> {{ incomingDoc.documentType }}</span>
                  -
                  {{ getDisplayText(incomingDoc.id, incomingDoc.subject) }}
                  <!-- <span
                    v-if="incomingDoc.subject.length > maxLength"
                    class="ml-1 text-xs text-teal-700 cursor-pointer hover:underline"
                    @click="toggleExpanded(incomingDoc.id)"
                  >
                    {{ isExpanded(incomingDoc.id) ? "See less" : "See more" }}
                  </span> -->
                </div>
              </td>
              <td width="10%" class="p-3 text-xs font-light text-right align-top">
                {{ incomingDoc.date_sent }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ScrollableContainer>
</template>
