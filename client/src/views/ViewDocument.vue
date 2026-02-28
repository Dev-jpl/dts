<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { RiArrowLeftLine } from "vue-icons-plus/ri";
import { FiClock, FiFileText } from "vue-icons-plus/fi";

import { useTransaction } from "@/composables/useTransaction";
import { useActionVisibility } from "@/composables/useActionVisibility";
import { useToast } from "@/composables/useToast";
import { useDocumentInformation } from "@/composables/useDocumentInformation";

// ── UI Components ─────────────────────────────────────────────────────────────
import HistoryLogs from "@/components/view-document/HistoryLogs.vue";
import DocumentInformation from "@/components/view-document/DocumentInformation.vue";
import GroupButton from "@/components/ui/buttons/GroupButton.vue";
import OfficialNotesPanel from "@/components/view-document/OfficialNotesPanel.vue";

// ── Modals ────────────────────────────────────────────────────────────────────
import ReceiveModal from "@/components/view-document/ReceiveModal.vue";
import ReleaseModal from "@/components/view-document/ReleaseModal.vue";
import ForwardDocModal from "@/components/view-document/ForwardDocModal.vue";
import ReturnToSenderModal from "@/components/view-document/ReturnToSenderModal.vue";
import MarkAsDoneModal from "@/components/view-document/MarkAsDoneModal.vue";
import SubsequentReleaseModal from "@/components/view-document/SubsequentReleaseModal.vue";
import ReplyModal from "@/components/view-document/ReplyModal.vue";
import CloseDocumentModal from "@/components/view-document/CloseDocumentModal.vue";
import ManageRecipientsModal from "@/components/view-document/ManageRecipientsModal.vue";

// ── Setup ─────────────────────────────────────────────────────────────────────
const route = useRoute();
const toast = useToast();
const { documentInformation } = useDocumentInformation();

const {
  fetchTransaction,
  transaction,
  trxLogs,
  loading,
  logsLoading,
  logsError,
  fetchTransactionLogs,
  notes,
  notesLoading,
  fetchNotes,
  postNote,
} = useTransaction();

// Action visibility — all derived from transaction.value (reactive)
const {
  canReceive,
  canRelease,
  canSubsequentRelease,
  canMarkAsDone,
  canForward,
  canReturn,
  canReply,
  canClose,
  canManageRecipients,
} = useActionVisibility(transaction);

// ── Tab ───────────────────────────────────────────────────────────────────────
const activeTab = ref<"History Logs" | "Official Notes">("History Logs");

// ── Modal open state ──────────────────────────────────────────────────────────
const modalReceiveOpen = ref(false);
const modalReleaseOpen = ref(false);
const modalForwardOpen = ref(false);
const modalReturnOpen = ref(false);
const modalDoneOpen = ref(false);
const modalSubsequentReleaseOpen = ref(false);
const modalReplyOpen = ref(false);
const modalCloseOpen = ref(false);
const modalManageRecipientsOpen = ref(false);

const toggleReceiveModal = () => { modalReceiveOpen.value = !modalReceiveOpen.value; };
const toggleReleaseModal = () => { modalReleaseOpen.value = !modalReleaseOpen.value; };
const toggleForwardModal = () => { modalForwardOpen.value = !modalForwardOpen.value; };
const toggleReturnModal = () => { modalReturnOpen.value = !modalReturnOpen.value; };
const toggleDoneModal = () => { modalDoneOpen.value = !modalDoneOpen.value; };
const toggleSubsequentReleaseModal = () => { modalSubsequentReleaseOpen.value = !modalSubsequentReleaseOpen.value; };
const toggleReplyModal = () => { modalReplyOpen.value = !modalReplyOpen.value; };
const toggleCloseModal = () => { modalCloseOpen.value = !modalCloseOpen.value; };
const toggleManageRecipientsModal = () => { modalManageRecipientsOpen.value = !modalManageRecipientsOpen.value; };

// ── Post-action handlers ──────────────────────────────────────────────────────
const refreshLogs = () => fetchTransactionLogs(transaction.value!.transaction_no);

const onDocumentReceived = async () => {
  await refreshLogs();
  toast.success("Document Received", "Receipt has been logged in the transaction history.");
};

const onDocumentReleased = async () => {
  await refreshLogs();
  toast.success("Document Released", "The document has been sent to the recipient(s).");
};

const onDocumentForwarded = async () => {
  await refreshLogs();
  toast.success("Document Forwarded", "The document has been forwarded to the next office.");
};

const onDocumentReturned = async () => {
  await refreshLogs();
  toast.warning("Document Returned", "The document has been sent back to the originating office.");
};

const onMarkedAsDone = async () => {
  await refreshLogs();
  toast.success("Marked as Done", "Your office has completed the required action.");
};

const onSubsequentReleased = async () => {
  await refreshLogs();
  toast.success("Document Re-released", "The document has been re-released to the selected office.");
};

const onDocumentReplied = async () => {
  await refreshLogs();
  toast.success("Reply Sent", "Your reply document has been created and released.");
};

const onDocumentClosed = async () => {
  await fetchTransaction(transaction.value!.transaction_no);
  await refreshLogs();
  toast.success("Document Closed", "The document has been closed.");
};

const onRecipientsUpdated = async () => {
  await fetchTransaction(transaction.value!.transaction_no);
  await refreshLogs();
  toast.success("Recipients Updated", "The recipient list has been updated.");
};

// ── Notes helper ──────────────────────────────────────────────────────────────
const docNo = computed(() => transaction.value?.document_no ?? "");

const handlePostNote = async (docNo: string, payload: { transaction_no: string; note: string }) => {
  await postNote(docNo, payload);
};

// Switch to notes tab and load if not loaded yet
const switchToNotes = () => {
  activeTab.value = "Official Notes";
  if (docNo.value && !notes.value.length) {
    fetchNotes(docNo.value);
  }
};

// ── Mount ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
  const trxNo = route.params.trxNo as string;
  await fetchTransaction(trxNo);
  documentInformation.documentNumber = transaction.value?.document_no ?? "N/A";
});
</script>

<template>
  <div class="flex-1 w-full h-[calc(100svh-50px)] bg-white">

    <!-- ── Top Bar: Back + Actions + Pagination ── -->
    <div class="flex items-center h-[50px] justify-between p-2 border-t border-gray-200 bg-gray-50">
      <div class="flex items-center">

        <button @click="$router.back()" type="button"
          class="flex items-center p-2 text-xs text-gray-500 rounded-lg hover:bg-gray-100">
          <RiArrowLeftLine class="mr-2 size-5" />
          Back
        </button>

        <!-- Action buttons bar -->
        <div v-if="transaction"
          class="flex ml-4 border border-gray-300 divide-x divide-gray-300 rounded-md overflow-clip">

          <!-- RECEIVE -->
          <div v-if="canReceive">
            <ReceiveModal :isOpen="modalReceiveOpen" :toggleReceiveModal="toggleReceiveModal"
              :trxNo="transaction.transaction_no" @received="onDocumentReceived" />
            <GroupButton :isActive="modalReceiveOpen" :action="toggleReceiveModal" btnText="Receive">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
              </svg>
            </GroupButton>
          </div>

          <!-- RELEASE -->
          <div v-if="canRelease">
            <ReleaseModal :isOpen="modalReleaseOpen" :toggleReleaseModal="toggleReleaseModal"
              @released="onDocumentReleased" />
            <GroupButton :isActive="modalReleaseOpen" :action="toggleReleaseModal" btnText="Release">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
              </svg>
            </GroupButton>
          </div>

          <!-- SUBSEQUENT RELEASE -->
          <div v-if="canSubsequentRelease">
            <SubsequentReleaseModal :isOpen="modalSubsequentReleaseOpen" :toggleModal="toggleSubsequentReleaseModal"
              :trxNo="transaction.transaction_no" :transaction="transaction" @released="onSubsequentReleased" />
            <GroupButton :isActive="modalSubsequentReleaseOpen" :action="toggleSubsequentReleaseModal"
              btnText="Re-Release">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
              </svg>
            </GroupButton>
          </div>

          <!-- MARK AS DONE -->
          <div v-if="canMarkAsDone">
            <MarkAsDoneModal :isOpen="modalDoneOpen" :toggleModal="toggleDoneModal"
              :trxNo="transaction.transaction_no" @done="onMarkedAsDone" />
            <GroupButton :isActive="modalDoneOpen" :action="toggleDoneModal" btnText="Mark as Done">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
            </GroupButton>
          </div>

          <!-- FORWARD -->
          <div v-if="canForward">
            <ForwardDocModal :isOpen="modalForwardOpen" :toggleForwardModal="toggleForwardModal"
              :trxNo="transaction.transaction_no" :transaction="transaction" @forwarded="onDocumentForwarded" />
            <GroupButton :isActive="modalForwardOpen" :action="toggleForwardModal" btnText="Forward">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
              </svg>
            </GroupButton>
          </div>

          <!-- RETURN TO SENDER -->
          <div v-if="canReturn">
            <ReturnToSenderModal :isOpen="modalReturnOpen" :toggleReturnModal="toggleReturnModal"
              :trxNo="transaction.transaction_no" @returned="onDocumentReturned" />
            <GroupButton :isActive="modalReturnOpen" :action="toggleReturnModal" btnText="Return to Sender">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m15 15-6 6m0 0-6-6m6 6V9a6 6 0 0 1 12 0v3" />
              </svg>
            </GroupButton>
          </div>

          <!-- REPLY -->
          <div v-if="canReply">
            <ReplyModal :isOpen="modalReplyOpen" :toggleModal="toggleReplyModal"
              :trxNo="transaction.transaction_no" @replied="onDocumentReplied" />
            <GroupButton :isActive="modalReplyOpen" :action="toggleReplyModal" btnText="Reply">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M7.49 12 3.74 8.248m0 0 3.75-3.75m-3.75 3.75h16.5V19.5" />
              </svg>
            </GroupButton>
          </div>

          <!-- CLOSE -->
          <div v-if="canClose">
            <CloseDocumentModal :isOpen="modalCloseOpen" :toggleModal="toggleCloseModal"
              :docNo="transaction.document_no" @closed="onDocumentClosed" />
            <GroupButton :isActive="modalCloseOpen" :action="toggleCloseModal" btnText="Close">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
              </svg>
            </GroupButton>
          </div>

          <!-- MANAGE RECIPIENTS -->
          <div v-if="canManageRecipients">
            <ManageRecipientsModal :isOpen="modalManageRecipientsOpen" :toggleModal="toggleManageRecipientsModal"
              :trxNo="transaction.transaction_no" :transaction="transaction" @updated="onRecipientsUpdated" />
            <GroupButton :isActive="modalManageRecipientsOpen" :action="toggleManageRecipientsModal"
              btnText="Recipients">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
              </svg>
            </GroupButton>
          </div>

        </div>
      </div>

      <!-- Pagination placeholder -->
      <div v-if="transaction" class="flex gap-2 ml-auto">
        <div class="flex items-center space-x-5">
          <span class="text-xs">1 of 200</span>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="hidden transition-all duration-100 rotate-180 size-4 sm:ml-auto sm:block">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="hidden transition-all duration-100 size-4 sm:ml-auto sm:block">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
        </div>
      </div>
    </div>

    <hr class="text-gray-200" />

    <!-- ── Content ── -->
    <template v-if="loading">
      <div class="p-10 text-center text-gray-400">Loading...</div>
    </template>
    <template v-else>
      <div v-if="transaction" class="grid w-full grid-cols-12 divide-x divide-gray-200">

        <!-- Document Information Panel -->
        <DocumentInformation class="col-span-8" :transactions="transaction" />

        <!-- History + Notes Panel -->
        <div class="flex-col hidden col-span-4 bg-white sm:flex">

          <!-- Tabs -->
          <div
            class="grid border-b w-full text-gray-800 h-[50px] border-gray-100 divide-gray-100 items-center mx-auto grid-cols-2 text-xs text-center">
            <button type="button" @click="activeTab = 'History Logs'"
              class="flex items-end w-full h-full pt-4 border-b-4 border-r border-r-gray-200" :class="activeTab === 'History Logs'
                ? 'border-amber-500'
                : 'border-gray-200 text-gray-400'">
              <h2 class="flex mx-auto mb-2 font-medium">
                <FiClock class="w-4 h-4 mr-2" />
                History Logs
              </h2>
            </button>
            <button type="button" @click="switchToNotes" class="flex items-end w-full h-full px-4 border-b-4"
              :class="activeTab === 'Official Notes'
                ? 'border-amber-500'
                : 'border-gray-200 text-gray-400'">
              <h3 class="flex mx-auto mb-2 font-medium w-fit">
                <FiFileText class="w-4 h-4 mr-2" />
                Official Notes
              </h3>
            </button>
          </div>

          <!-- Tab content -->
          <div class="relative flex-1">
            <div class="absolute inset-0 flex-col hidden w-full h-full sm:flex">
              <div v-show="activeTab === 'History Logs'"
                class="h-[calc(100svh-150px)] overflow-y-auto w-full">
                <HistoryLogs :historyLogs="trxLogs" :isLoading="logsLoading" :hasError="!!logsError" class="px-4 mt-8" />
              </div>
              <div v-show="activeTab === 'Official Notes'" class="h-[calc(100svh-150px)]">
                <OfficialNotesPanel :notes="notes" :loading="notesLoading" :docNo="docNo"
                  :trxNo="transaction.transaction_no" :onPost="handlePostNote" />
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="p-10 text-center text-gray-400">No document found.</div>
    </template>

  </div>
</template>
