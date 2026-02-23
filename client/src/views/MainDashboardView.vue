<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import { useExpandableTextArray } from "@/composables/useExpandableTextArray";
import { useAuthStore } from "@/stores/auth";
import { generateUUID } from "@/utils/generateUUID";
import { getUuid } from "pdfjs-dist";
import { ref } from "vue";

const auth = useAuthStore();

const user = {
  name: auth.user?.username || "John Dev",
  email: auth.user?.email || "john@example.com",
  avatarUrl: "", // Add avatar logic if you have one
};

const documentNumber = ref(null);

const announcements = [
  {
    id: generateUUID(),
    title: "Database Migration",
    content:
      "The ICTS-SysADD Developer team will have a scheduled database migration for Trading Post Commodity Volume Watch (TPCVW). The mentioned system will not be accessible during this period. January 1-2, 2024",
    audience_type: "Public",
    date_posted: "July 30, 2025",
    expiration: "1 Year",
    isActive: true,
  },
  {
    id: generateUUID(),
    title: "Systen Maintenance",
    content:
      "Bantay Presyo Monitoring System (BPMS) will have a scheduled maintenance effective December 25-26, 2023",
    audience_type: "Public",
    date_posted: "June 30, 2025",
    expiration: "1 Year",
    isActive: true,
  },
  {
    id: generateUUID(),
    title: "Server Update",
    content:
      "The ICTS Team will be having a server maintenance and update. The following systems (AFMIS, BPMS, TPCVW) will not be available during this period.",
    audience_type: "Public",
    date_posted: "June 30, 2025",
    expiration: "1 Year",
    isActive: true,
  },
];

const disseminatedDocuments = [
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00190",
    documentType: "Obligation Request and Status",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    from: "Systems Application Development Division",
    date_created: "July 30, 2025",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    documentType: "Obligation Request and Status",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    from: "Systems Application Development Division",
    date_created: "July 30, 2025",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00192",
    documentType: "Obligation Request and Status",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    from: "Systems Application Development Division",
    date_created: "July 30, 2025",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00193",
    documentType: "Obligation Request and Status",
    from: "Systems Application Development Division",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    date_created: "July 30, 2025",
  },
];

const { maxLength, isExpanded, toggleExpanded, getDisplayText } =
  useExpandableTextArray();

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
];

const outgoingDocuments = [
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    current_destination: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    current_destination: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    current_destination: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    current_destination: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
  {
    id: generateUUID(),
    documentNumber: "DA-CO-ICTS-ORS20240906-00191",
    subject:
      "PAL-TO/ORS amounting to P40,564.00, to obligate payment of plane tickets for the following SysADD Personnel, Mla-CDO-Mla, July 21-25, 2025, to attend and participate in the Harmonization and Updates on the Human Resource Operations and Management in the Department of Agriculture: 1. Mark Harris Jamilan 2. Teresita Cruz 3. Charmaine Ellyn Resco; and 4. John Patrick Lachica",
    documentType: "Obligation Request and Status",
    current_destination: "Systems Application Development Division",
    date_sent: "10:00 AM",
  },
];
</script>
<template>
  <ScrollableContainer>
    <!-- Header Stats Card -->
    <div
      class="grid grid-cols-1 sm:grid-cols-12 gap-5 p-4  sm:h-[150px] w-full rounded-xl bg-gradient-to-r from-teal-900 to-green-700">
      <!-- Welcome Text and Receive Input -->
      <div class="flex flex-col justify-between col-span-1 sm:col-span-4">
        <h1 class="flex text-2xl font-bold text-lime-500">
          WELCOME! <span class="ml-2 text-white"> {{ user.name }} </span>
        </h1>

        <div class="hidden sm:block">
          <h1 class="mb-2 text-gray-300">Receive a document</h1>
          <input v-model="documentNumber" type="text" placeholder="Enter Document No"
            class="w-full p-2 text-xs transition bg-white border border-gray-200 rounded-full shadow-inner focus:outline-none focus:ring-2 focus:ring-lime-600" />
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 col-span-1 gap-4 sm:col-span-8 sm:grid-cols-4">
        <!-- Received Stats -->
        <div class="flex flex-col justify-between p-4 shadow-2xl bg-white/20 rounded-xl">
          <!-- Icon / Text -->
          <div class="font-light text-gray-100 ">
            Overdue
          </div>
          <!-- Count -->
          <div class="text-2xl font-bold text-white">5,000</div>
        </div>
        <!-- Released Stats -->
        <div class="flex flex-col justify-between p-4 shadow-2xl bg-white/20 rounded-xl">
          <!-- Icon / Text -->
          <div class="font-light text-gray-100 ">Today's Incoming</div>
          <!-- Count -->
          <div class="text-2xl font-bold text-white">5,000</div>
        </div>
        <!-- My Documents -->
        <div class="flex flex-col justify-between p-4 shadow-2xl bg-white/20 rounded-xl">
          <!-- Icon / Text -->
          <div class="font-light text-gray-100 ">Processing</div>
          <!-- Count -->
          <div class="text-2xl font-bold text-white">5,000</div>
        </div>
        <!-- Archived -->
        <div class="flex flex-col justify-between p-4 shadow-2xl bg-white/20 rounded-xl">
          <!-- Icon / Text -->
          <div class="font-light text-gray-100 ">Archived</div>
          <!-- Count -->
          <div class="text-2xl font-bold text-white">5,000</div>
        </div>
      </div>
    </div>

    <div class="grid w-full grid-cols-12 gap-5">
      <div class="flex flex-col col-span-12 sm:col-span-9">
        <!-- Card Incoming -->
        <div class="w-full mt-5 bg-white shadow-lg rounded-xl overflow-clip">
          <!-- header -->
          <div class="flex justify-between w-full p-4 shadow items-baseline-last text-lime-600">
            <h1 class="font-bold">Recent Incoming</h1>
            <h1 class="font-bold">{{ incomingDocuments?.length || 0 }}</h1>
          </div>
          <!-- <hr class="text-gray-300 border-1" /> -->
          <!-- table -->
          <table class="w-full">
            <thead>
              <tr class="sticky bg-gray-100">
                <td width="15%" class="p-2 text-xs font-semibold">
                  From
                </td>
                <td width="67%" class="p-2 text-xs font-semibold">
                  Subject
                </td>
                <td width="10%" class="p-2 text-xs font-semibold text-right">
                  Sent
                </td>
              </tr>
            </thead>
          </table>
          <div class="h-[350px] overflow-auto">
            <table class="w-full">
              <!-- <thead>
                <tr class="sticky bg-gray-100">
                  <td
                    width="25%"
                    class="p-2 text-xs font-semibold"
                  >
                    Document Number
                  </td>
                  <td
                    width="35%"
                    class="p-2 text-xs font-semibold"
                  >
                    Subject
                  </td>
                  <td
                    width="35%"
                    class="p-2 text-xs font-semibold"
                  >
                    From
                  </td>
                </tr>
              </thead> -->
              <tbody class="divide-y divide-gray-200">
                <tr v-for="incomingDoc in incomingDocuments" class="hover:bg-gray-100 hover:cursor-pointer" @click="
                  $router.push({
                    name: 'view-document',
                    params: { trxNo: incomingDoc.documentNumber },
                  })
                  ">
                  <td width="15%" class="p-3 text-xs font-light align-top">
                    <div>
                      {{ incomingDoc.from }}
                    </div>
                  </td>
                  <td width="67%" class="p-3 align-top">
                    <div class="text-xs font-normal">
                      <span class="font-bold">
                        {{ incomingDoc.documentType }}</span>
                      -
                      {{ getDisplayText(incomingDoc.id, incomingDoc.subject) }}
                      <span v-if="incomingDoc.subject.length > maxLength"
                        class="ml-1 text-xs text-teal-700 cursor-pointer hover:underline"
                        @click="toggleExpanded(incomingDoc.id)">
                        {{
                          isExpanded(incomingDoc.id) ? "See less" : "See more"
                        }}
                      </span>
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

        <!-- Card Outgoing -->
        <div class="w-full mt-5 bg-white shadow-lg rounded-xl">
          <!-- header -->
          <div class="flex justify-between w-full p-4 items-baseline-last text-amber-600">
            <h1 class="font-bold">Recent Outgoing</h1>
            <h1 class="font-bold">{{ outgoingDocuments?.length || 0 }}</h1>
          </div>
          <!-- table -->
          <table class="w-full">
            <thead>
              <tr class="bg-gray-100">
                <td width="57%" class="p-2 text-xs font-semibold">
                  Subject
                </td>
                <td width="15%" class="p-2 text-xs font-semibold">
                  Current Destination
                </td>
                <td width="10%" class="p-2 text-xs font-semibold text-right">
                  Sent
                </td>
              </tr>
            </thead>
          </table>
          <div class="h-[350px] overflow-auto">
            <table class="w-full">
              <tbody class="divide-y divide-gray-200">
                <tr v-for="outgoingDoc in outgoingDocuments" class="hover:bg-gray-100 hover:cursor-pointer">
                  <td width="57%" class="p-3 align-top">
                    <div class="text-xs font-normal">
                      <span class="font-bold">
                        {{ outgoingDoc.documentType }}</span>
                      -
                      {{ getDisplayText(outgoingDoc.id, outgoingDoc.subject) }}
                      <span v-if="outgoingDoc.subject.length > maxLength"
                        class="text-xs text-teal-700 cursor-pointer hover:underline"
                        @click="toggleExpanded(outgoingDoc.id)">
                        {{
                          isExpanded(outgoingDoc.id) ? "See less" : "See more"
                        }}
                      </span>
                    </div>
                  </td>
                  <td width="15%" class="p-3 text-xs font-light align-top">
                    <div>
                      {{ outgoingDoc.current_destination }}
                    </div>
                  </td>

                  <td width="10%" class="p-3 text-xs font-light text-right align-top">
                    {{ outgoingDoc.date_sent }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex flex-col col-span-12 sm:mt-5 sm:col-span-3">
        <!-- For Dissemination Card -->
        <div class="p-2 bg-gray-100 border border-gray-300 rounded-xl">
          <!-- header -->
          <div class="flex items-center justify-between p-2 text-sm">
            <h1 class="font-bold">Latest Disseminated Documents</h1>
          </div>
          <div class="h-[410px] rounded-lg overflow-auto">
            <ul class="space-y-2 divide-y divide-gray-300">
              <li v-for="doc in disseminatedDocuments" class="p-2">
                <!-- <h1 class="text-sm font-semibold">{{ doc.documentNumber }}</h1> -->
                <p class="mb-3 text-xs font-light">
                  <span class="font-medium">From: </span> {{ doc.from }}
                </p>
                <h1 class="text-xs font-semibold">{{ doc.documentType }}</h1>
                <p class="text-xs font-light">
                  <span class="font-medium"> Subject:</span>
                  {{ getDisplayText(doc.id, doc.subject) }}
                  <span v-if="doc.subject.length > maxLength"
                    class="ml-1 text-xs text-teal-700 cursor-pointer hover:underline" @click="toggleExpanded(doc.id)">
                    {{ isExpanded(doc.id) ? "See less" : "See more" }}
                  </span>
                </p>
                <div class="flex items-center justify-between pt-2">
                  <span class="text-xs text-gray-400">{{
                    doc.date_created
                    }}</span>
                  <button class="px-2 py-1 text-[10px] text-gray-400 border rounded-lg">
                    View
                  </button>
                </div>
              </li>
              <li class="flex p-2">
                <a href="#" class="mx-auto text-xs text-center text-gray-400">
                  See All
                </a>
              </li>
            </ul>
          </div>
        </div>

        <!-- Announcements Card -->
        <div class="p-2 mt-4 bg-gray-300 rounded-xl">
          <!-- header -->
          <div class="flex items-center justify-between p-2 text-sm">
            <h1 class="font-bold">Announcements</h1>
            <h1 class="font-light text-gray-400">All</h1>
          </div>
          <div class="h-[300px] rounded-lg overflow-auto">
            <ul class="space-y-2 rounded-lg">
              <li v-for="post in announcements" class="p-2 bg-gray-100 rounded-md shadow-md">
                <h1 class="text-xs font-semibold">{{ post.title }}</h1>
                <p class="mt-2 text-xs font-light text-gray-600">
                  {{ post.content }}
                </p>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </ScrollableContainer>
</template>
