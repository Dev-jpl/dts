<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import FrmInput from "@/components/ui/inputs/FrmInput.vue";
import FrmLabel from "@/components/ui/labels/FrmLabel.vue";
import { computed, ref } from "vue";
import { IoArrowForward } from "vue-icons-plus/io";
const documentNumber = ref(null);

const receive = () => {
  alert("Document has been received successfully");
};

const recentlyReceived = ref([
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00192",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00193",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00194",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00195",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
  {
    documentNumber: "DA-CO-ICTS-ORS20240906-00196",
    dateTimeStamp: "1hr ago",
    documentType: "Obligation Request And Status",
    origin: "Systems Application Development Division",
    subject: `PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane
                tickets for the following SysADD Personnel, Mla-CDO-Mla, July
                21-25, 2025, to attend and participate in the Harmonization and
                Updates on the Human Resource Operations and Management in the
                Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita
                Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica`,
  },
]);

const maxSubjectLength = 140;
const expandedStates = ref<Record<string, boolean>>({});

const isExpanded = (id: string) => expandedStates.value[id] === true;
const toggleExpanded = (id: string) => {
  expandedStates.value[id] = !expandedStates.value[id];
};

const getSubjectDisplay = (doc: (typeof recentlyReceived.value)[0]) => {
  const isOpen = isExpanded(doc.documentNumber); // ✅ using documentNumber
  return isOpen || doc.subject.length <= maxSubjectLength
    ? doc.subject
    : doc.subject.slice(0, maxSubjectLength) + "…";
};
</script>

<template>
  <ScrollableContainer px="50px">
    <div class="mt-[5rem] w-full max-w-xl">
      <h1 class="mb-2 text-2xl font-bold text-center">Receive Document</h1>
      <p class="text-xs text-center text-gray-400">
        Please place the cursor in the input field, then either scan the QR code
        attached <br />
        to your document or manually enter the document number you wish to
        receive.
      </p>
      <div class="relative w-full mt-10">
        <form @submit.prevent="receive">
          <input
            v-model="documentNumber"
            type="text"
            placeholder="Document No"
            class="w-full px-4 py-3 text-sm transition bg-white border border-gray-200 rounded-lg shadow-inner focus:outline-none focus:ring-2 focus:ring-lime-600"
          />
          <button
            type="submit"
            class="absolute flex items-center gap-1 px-3 py-2 text-xs font-semibold text-white transition -translate-y-1/2 rounded-lg shadow-2xl right-2 top-1/2 group hover:cursor-pointer bg-lime-600 hover:bg-lime-500"
          >
            Receive <IoArrowForward class="transition-all size-4" />
            <!-- <span>Track</span> -->
          </button>
        </form>
      </div>
    </div>

    <!-- Recently -->
    <div class="flex flex-col max-w-xl mt-10 over">
      <FrmLabel
        :label="'Recently Received Documents'"
        class="mb-2"
      />
      <ul class="overflow-auto divide-y divide-gray-300">
        <li
          v-for="doc in recentlyReceived"
          class="p-2"
        >
          <div>
            <!-- Head -->
            <div>
              <div class="flex justify-between">
                <h1 class="text-sm font-bold">{{ doc.documentNumber }}</h1>
                <span class="text-[10px] text-gray-400">{{
                  doc.dateTimeStamp
                }}</span>
              </div>
              <p class="text-xs font-medium">{{ doc.documentType }}</p>
            </div>
            <!-- Content -->
            <div class="mt-2">
              <p class="text-xs font-light">
                <span class="font-medium">From: </span> {{ doc.origin }}
              </p>
              <!-- <p class="text-xs font-light">Subject: {{ doc.subject }}</p> -->
              <p class="text-xs font-light">
                <span class="font-medium"> Subject:</span>
                {{ getSubjectDisplay(doc) }}
                <span
                  v-if="doc.subject.length > maxSubjectLength"
                  class="ml-1 text-teal-700 cursor-pointer hover:underline"
                  @click="toggleExpanded(doc.documentNumber)"
                >
                  {{ isExpanded(doc.documentNumber) ? "See less" : "See more" }}
                </span>
              </p>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </ScrollableContainer>
</template>
