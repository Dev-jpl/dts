<script setup>
import BaseButton from "@/components/ui/buttons/BaseButton.vue";
import BaseButtonRounded from "../ui/buttons/BaseButtonRounded.vue";
import { generateUUID } from "@/utils/generateUUID";
import { truncate } from "@/utils/truncateText";


const props = defineProps({
    title: { type: String, default: "Modal Title" },
    isOpen: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "confirm"]);
function closeModal() {
    emit("close");
}
function confirmAction() {
    emit("confirm");
}


const recentDocuments = [
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


import { useRouter } from 'vue-router'

const router = useRouter()

const viewDocument = (documentNumber) => {
    router.push({
        name: 'view-document',
        params: { doc_no: documentNumber },
    })
    closeModal()
}

</script>
<template>
    <!-- Backdrop transition -->
    <Transition enter-active-class="transition-opacity duration-200 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-20" leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-20" leave-to-class="opacity-0">
        <div v-if="isOpen" class="fixed inset-0 z-[900] bg-black opacity-25"></div>
    </Transition>

    <!-- Modal transition -->

    <Transition enter-active-class="transition duration-300 ease-out transform"
        enter-from-class="-translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in transform" leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-4 opacity-0">
        <div @click="closeModal" v-if="isOpen" class="fixed inset-0 z-[1000] flex items-start justify-center">
            <div class="w-full max-w-xl mt-[5rem] bg-white rounded-lg shadow-xl dark:bg-gray-800" @click.stop>
                <!-- Header -->
                <div class="flex flex-col items-center justify-end p-4 pb-3 border-gray-200 dark:border-gray-700">
                    <h1 class="mb-2 font-bold text-md">Track Document</h1>
                    <form class="w-full mx-auto">
                        <label for="default-search"
                            class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 flex items-center pointer-events-none start-0 ps-3">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input type="search" id="default-search"
                                class="block w-full p-2.5 text-sm text-gray-900 border border-gray-300 rounded-full ps-10 bg-gray-50 focus:ring-teal-500 focus:border-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-teal-500 dark:focus:border-teal-500"
                                placeholder="Search by document number or subject" required />
                            <!-- <button type="submit"
                                class="text-white absolute end-2.5 bottom-2.5 bg-teal-700 hover:bg-teal-800 focus:ring-4 focus:outline-none focus:ring-teal-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-teal-600 dark:hover:bg-teal-700 dark:focus:ring-teal-800">
                                Search
                            </button> -->

                            <BaseButtonRounded
                                className="absolute end-[5px] bottom-[5px] bg-teal-600 hover:bg-teal-700 text-white px-2 py-1 text-sm">
                                Search
                            </BaseButtonRounded>
                        </div>
                    </form>
                </div>

                <!-- Body -->
                <div class="p-4 text-gray-700 dark:text-gray-300">
                    <h5 class="text-sm font-medium">Recent</h5>
                    <div class="max-h-[calc(100svh-20rem)] overflow-auto border border-gray-100 rounded-md">
                        <ul class="divide-y divide-gray-200">
                            <li @click="viewDocument(doc.documentNumber)" v-for="doc in recentDocuments" :key="doc.id"
                                class="p-2 pb-3 mb-4 hover:bg-gray-50">
                                <!-- Document No -->
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-bold">
                                        {{ doc.documentNumber }}
                                    </div>
                                    <span
                                        class="flex items-center px-2 py-0.5 text-[10px] font-medium text-gray-800 bg-gray-200 rounded-full">
                                        <!-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="mr-1 size-3">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg> -->
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="mr-1 text-gray-500 size-3">
                                            <path fill-rule="evenodd"
                                                d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z"
                                                clip-rule="evenodd" />
                                        </svg>

                                        {{ doc.date_sent }}
                                    </span>
                                </div>

                                <!-- Document Type -->
                                <div class="text-xs font-medium ">
                                    {{ doc.documentType }}
                                </div>

                                <!-- Subject -->
                                <div class="text-xs text-gray-500">
                                    {{ truncate(doc.subject, 120) }}
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

</template>
