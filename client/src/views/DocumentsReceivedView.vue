<script setup lang="ts">
import ScrollableContainer from "@/components/ScrollableContainer.vue";
import { useExpandableTextArray } from "@/composables/useExpandableTextArray";
import { useReceivedDocuments } from "@/composables/useReceivedDocument";
import { ref, watch, onMounted } from "vue";

// ── API Integration ────────────────────────────────────────────────────────────
const { transactions, isLoading, error, isEmpty, fetchReceivedDocuments } = useReceivedDocuments();

// ── Tabs ───────────────────────────────────────────────────────────────────────
const tabs = [
    { tabLabel: "All" },
    { tabLabel: "For Action" },
    { tabLabel: "Processed" },
];

const activeTab = ref(tabs[0].tabLabel);
const tabRefs = ref<HTMLElement[]>([]);
const indicatorStyle = ref({});

function updateIndicator() {
    const index = tabs.findIndex(t => t.tabLabel === activeTab.value);
    const el = tabRefs.value[index];
    if (el) {
        indicatorStyle.value = {
            left: el.offsetLeft + "px",
            width: el.offsetWidth + "px",
        };
    }
}

watch(activeTab, () => {
    updateIndicator();
    fetchReceivedDocuments();
});

onMounted(() => {
    updateIndicator();
    fetchReceivedDocuments();
});

// ── Expandable text ────────────────────────────────────────────────────────────
const { isExpanded, toggleExpanded, getDisplayText } = useExpandableTextArray(250);

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatDate(dateStr: string) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}
</script>

<template>
    <ScrollableContainer padding="0" px="50px" background="white" class="bg-white">

        <div class="w-full">

            <!-- ── Header ──────────────────────────────────────────────────── -->
            <div class="flex items-end justify-between w-full border-b border-gray-200">
                <div class="p-4">
                    <h1 class="font-bold text-gray-600">Received Documents</h1>
                </div>

                <!-- Tabs -->
                <!-- <div class="relative flex pr-4 divide-gray-200">
                    <div v-for="(tab, index) in tabs" :key="tab.tabLabel"
                        :ref="el => { if (el) tabRefs[index] = el as HTMLElement }" @click="activeTab = tab.tabLabel"
                        class="px-3 mb-2 w-[100px] py-2 text-xs text-center cursor-pointer" :class="activeTab === tab.tabLabel
                            ? 'text-teal-700 font-bold'
                            : 'text-gray-500'">
                        {{ tab.tabLabel }}
                    </div>
                   
                    <div class="absolute bottom-0 h-1 transition-all duration-300 bg-amber-500 rounded-t-md"
                        :style="indicatorStyle" />
                </div> -->
            </div>
            <!-- Header End -->

            <!-- ── Table Header ─────────────────────────────────────────────── -->
            <table class="w-full">
                <thead>
                    <tr class="sticky bg-gray-100">
                        <td width="20%" class="px-4 py-2 text-xs font-semibold">
                            From
                        </td>
                        <td width="60%" class="px-4 py-2 text-xs font-semibold">
                            Subject
                        </td>
                        <td width="10%" class="px-4 py-2 text-xs font-semibold text-right">
                            Received
                        </td>
                    </tr>
                </thead>
            </table>

            <!-- ── Table Body ───────────────────────────────────────────────── -->
            <div class="h-[calc(100svh-200px)] overflow-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-200">

                        <!-- Loading state -->
                        <tr v-if="isLoading">
                            <td colspan="3" class="px-4 py-10 text-xs text-center text-gray-400">
                                Loading documents...
                            </td>
                        </tr>

                        <!-- Error state -->
                        <tr v-else-if="error">
                            <td colspan="3" class="px-4 py-10 text-xs text-center text-red-400">
                                {{ error }}
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-else-if="isEmpty">
                            <td colspan="3" class="px-4 py-10 text-xs text-center text-gray-400">
                                No received documents.
                            </td>
                        </tr>

                        <!-- Rows -->
                        <tr v-else v-for="trx in transactions" :key="trx.transaction_no"
                            class="hover:bg-gray-100 hover:cursor-pointer" @click="$router.push({
                                name: 'view-document',
                                params: { trxNo: trx.transaction_no },
                            })">
                            <!-- From (sender office) -->
                            <td width="20%" class="p-3 text-xs font-light align-top">
                                <div class="font-medium text-gray-700">{{ trx.office_name }}</div>
                                <div class="text-gray-400">{{ trx.created_by_name }}</div>
                            </td>

                            <!-- Subject -->
                            <td width="60%" class="p-3 align-top">
                                <div class="text-xs font-normal">
                                    <span class="font-bold">{{ trx.document_type }}</span>
                                    -
                                    {{ getDisplayText(trx.transaction_no, trx.subject) }}
                                    <span v-if="trx.subject.length > 250"
                                        class="ml-1 text-xs text-teal-700 cursor-pointer hover:underline"
                                        @click.stop="toggleExpanded(trx.transaction_no)">
                                        {{ isExpanded(trx.transaction_no) ? "See less" : "See more" }}
                                    </span>
                                </div>
                            </td>

                            <!-- Date -->
                            <td width="10%" class="p-3 text-xs font-light text-right align-top">
                                {{ formatDate(trx.created_at) }}
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </div>

    </ScrollableContainer>
</template>