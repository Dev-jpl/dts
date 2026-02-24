<script setup lang="ts">
import HistoryLogs from "@/components/view-document/HistoryLogs.vue";
import { useDocumentInformation } from "@/composables/useDocumentInformation";
import { computed, onMounted, ref } from "vue";
import { RiArrowLeftLine } from "vue-icons-plus/ri";
import { FiClock, FiMessageCircle } from "vue-icons-plus/fi";
import { useActionVisibility } from '@/composables/useActionVisibility'
import { useToast } from '@/composables/useToast'

const { documentInformation, updateDocumentInfo } = useDocumentInformation();

const formattedDate = computed(() =>
  new Date(documentInformation.date).toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }),
);

import { useRoute, useRouter } from "vue-router";
import DocumentComments from "@/components/view-document/DocumentComments.vue";
import DocumentInformation from "@/components/view-document/DocumentInformation.vue";
import BaseButton from "@/components/ui/buttons/BaseButton.vue";
import GroupButton from "@/components/ui/buttons/GroupButton.vue";
import { useTransaction } from "@/composables/useTransaction";

onMounted(async () => {
  const transaction_no = route.params.trxNo as string;
  await fetchTransaction(transaction_no);
  documentInformation.documentNumber = transaction.value?.document_no || "N/A";
});

const activeTab = ref<"History Logs" | "Comments">("History Logs");

// ── Modals ───────────────────────────────────────────────────────
import ReceiveModal from '@/components/view-document/ReceiveModal.vue'
import ReleaseModal from '@/components/view-document/ReleaseModal.vue'
import ForwardDocModal from '@/components/view-document/ForwardDocModal.vue'
import ReturnToSenderModal from '@/components/view-document/ReturnToSenderModal.vue'
import ModalDialog from '@/components/ui/modals/ModalDialog.vue'

// ── Toast ────────────────────────────────────────────────────────
const toast = useToast()

// ── Transaction state ────────────────────────────────────────────
const {
  fetchTransaction,
  transaction,
  trxLogs,
  loading,
  logsError,
  logsLoading,
  fetchTransactionLogs,
  comments,
  commentsLoading,
  postComment,
} = useTransaction()

// ── Action visibility (reactive, based on logs + current user) ───
const {
  canReceive,
  canRelease,
  canForward,
  canReturn,
  canReply,
  canArchive,
} = useActionVisibility(transaction)

// ── Modal toggles (keep as-is) ───────────────────────────────────
const modalReceiveOpen = ref(false)
const modalReleaseOpen = ref(false)
const modalForwardOpen = ref(false)
const modalReturnOpen = ref(false)
const modalReplyOpen = ref(false)
const modalArchiveOpen = ref(false)

const toggleReceiveModal = () => { modalReceiveOpen.value = !modalReceiveOpen.value }
const toggleReleaseModal = () => { modalReleaseOpen.value = !modalReleaseOpen.value }
const toggleForwardModal = () => { modalForwardOpen.value = !modalForwardOpen.value }
const toggleReturnModal = () => { modalReturnOpen.value = !modalReturnOpen.value }
const toggleReplyModal = () => { modalReplyOpen.value = !modalReplyOpen.value }
const toggleArchiveModal = () => { modalArchiveOpen.value = !modalArchiveOpen.value }

// ── Post-action handlers (refresh logs + toast) ──────────────────
const onDocumentReceived = async () => {
  await fetchTransactionLogs(transaction.value!.transaction_no)
  toast.success('Document Received', 'Receipt has been logged in the transaction history.')
}

const onDocumentReleased = async () => {
  await fetchTransactionLogs(transaction.value!.transaction_no)
  toast.success('Document Released', 'The document has been sent to the recipient(s).')
}

const onDocumentForwarded = async () => {
  await fetchTransactionLogs(transaction.value!.transaction_no)
  toast.success('Document Forwarded', 'The document has been forwarded to the next office.')
}

const onDocumentReturned = async () => {
  await fetchTransactionLogs(transaction.value!.transaction_no)
  toast.warning('Document Returned', 'The document has been sent back to the originating office.')
}

const onDocumentArchived = async () => {
  await fetchTransaction(transaction.value!.transaction_no) // full refresh to update status
  await fetchTransactionLogs(transaction.value!.transaction_no)
  toast.success('Document Archived', 'The document has been closed and stored for records.')
}

// ── Reply routing (unchanged) ────────────────────────────────────
const router = useRouter()
const replyDocument = () => {
  router.push({ name: 'profile-document-reply', params: { documentNo: documentInformation.documentNumber } })
  modalReplyOpen.value = false
}

// ── Mount ────────────────────────────────────────────────────────
const route = useRoute()
onMounted(async () => {
  const transaction_no = route.params.trxNo as string
  await fetchTransaction(transaction_no)
  documentInformation.documentNumber = transaction.value?.document_no || 'N/A'
})
</script>
<template>

  <div class="flex-1 w-full h-[calc(100svh-50px)] bg-white">

    <div class="flex items-center h-[50px] justify-between p-2 border-t border-gray-200 bg-gray-50">
      <div class="flex items-center">
        <button @click="$router.back()" type="button"
          class="flex items-center p-2 text-xs text-gray-500 rounded-lg hover:bg-gray-100">
          <RiArrowLeftLine class="mr-2 size-5" />

          Back
        </button>

        <!-- Actions Bar -->
        <div v-if="transaction"
          class="flex ml-4 border border-gray-300 divide-x divide-gray-300 rounded-md overflow-clip">

          <!-- ── RECEIVE ── -->
          <div v-if="canReceive">
            <ReceiveModal :isOpen="modalReceiveOpen" :toggleReceiveModal="toggleReceiveModal"
              :trxNo="transaction.transaction_no" @received="onDocumentReceived" />
            <GroupButton :isActive="modalReceiveOpen" @click="toggleReceiveModal" variant="primary">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
              </svg>
              <span>Receive</span>
            </GroupButton>
          </div>

          <!-- ── RELEASE ── -->
          <div v-if="canRelease">
            <ReleaseModal :isOpen="modalReleaseOpen" :toggleReleaseModal="toggleReleaseModal"
              @released="onDocumentReleased" />
            <GroupButton :isActive="modalReleaseOpen" @click="toggleReleaseModal" variant="primary">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
              </svg>
              <span>Release</span>
            </GroupButton>
          </div>

          <!-- ── FORWARD ── -->
          <div v-if="canForward">
            <ForwardDocModal :isOpen="modalForwardOpen" :toggleForwardModal="toggleForwardModal"
              @forwarded="onDocumentForwarded" />
            <GroupButton :isActive="modalForwardOpen" @click="toggleForwardModal" variant="primary"
              class="flex items-center text-xs">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
              </svg>
              <span>Forward</span>
            </GroupButton>
          </div>

          <!-- ── RETURN TO SENDER ── -->
          <div v-if="canReturn">
            <ReturnToSenderModal :isOpen="modalReturnOpen" :toggleReturnModal="toggleReturnModal"
              @returned="onDocumentReturned" />
            <GroupButton :isActive="modalReturnOpen" @click="toggleReturnModal" variant="primary"
              class="flex items-center text-xs">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m15 15-6 6m0 0-6-6m6 6V9a6 6 0 0 1 12 0v3" />
              </svg>
              <span>Return to Sender</span>
            </GroupButton>
          </div>

          <!-- ── REPLY ── -->
          <div v-if="canReply">
            <ModalDialog title="Reply to Document" :isOpen="modalReplyOpen" @close="toggleReplyModal"
              @confirm="replyDocument">
              Are you sure you want to reply to this document?
            </ModalDialog>
            <GroupButton :isActive="modalReplyOpen" @click="toggleReplyModal" variant="primary"
              class="flex items-center text-xs">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M7.49 12 3.74 8.248m0 0 3.75-3.75m-3.75 3.75h16.5V19.5" />
              </svg>
              <span>Reply</span>
            </GroupButton>
          </div>

          <!-- ── ARCHIVE ── -->
          <div v-if="canArchive">
            <ModalDialog title="Archive Document" :isOpen="modalArchiveOpen" @close="toggleArchiveModal"
              @confirm="onDocumentArchived">
              Are you sure you want to archive this document? This action cannot be undone.
            </ModalDialog>
            <GroupButton :isActive="modalArchiveOpen" @click="toggleArchiveModal" variant="primary"
              class="flex items-center text-xs">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 size-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
              </svg>
              <span>Archive</span>
            </GroupButton>
          </div>

        </div>
      </div>

      <!-- Pagination -->
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
    <template v-if="loading">
      <div class="p-10 text-center text-gray-400">
        Loading...
      </div>
    </template>
    <template v-else>
      <div v-if="transaction" class="grid w-full grid-cols-12 divide-x divide-gray-200">
        <!-- Document Information Panel Start-->
        <DocumentInformation class="col-span-8" :transactions="transaction" />
        <!-- Document Information Panel End-->

        <!-- Document History and Comments Panel -->
        <div class="flex-col hidden col-span-4 bg-white sm:flex">
          <!-- Tab Div Start -->
          <div
            class="grid border-b w-full text-gray-800 h-[50px] border-gray-100 divide-gray-100 items-center mx-auto grid-cols-2 text-xs text-center">
            <button type="button" @click="activeTab = 'History Logs'"
              class="flex items-end w-full h-full pt-4 border-b-4 border-r border-r-gray-200" :class="activeTab === 'History Logs'
                ? ' border-amber-500 '
                : ' border-gray-200 text-gray-400'
                ">
              <h2 class="flex mx-auto mb-2 font-medium">
                <FiClock class="w-4 h-4 mr-2" />
                History Logs
              </h2>
            </button>
            <button type="button" @click="activeTab = 'Comments'" class="flex items-end w-full h-full px-4 border-b-4"
              :class="activeTab === 'Comments'
                ? ' border-amber-500'
                : 'border-gray-200 text-gray-400'
                ">
              <h3 class="flex mx-auto mb-2 font-medium w-fit">
                <FiMessageCircle class="w-4 h-4 mr-2" />
                Comments ({{ comments.length }})
              </h3>
            </button>
          </div>
          <!-- Tab Div End -->
          <div class="relative flex-1">
            <div class="absolute inset-0 flex-col hidden w-full h-full sm:flex">
              <div v-show="activeTab == 'History Logs'" class="h-[calc(100svh-150px)] overflow-y-auto w-full">
                <HistoryLogs :historyLogs="trxLogs" :isLoading="logsLoading" :hasError="logsError" class="px-4 mt-8" />
              </div>
              <div v-show="activeTab == 'Comments'" class="h-full">
                <DocumentComments :comments="comments" :trxNo="transaction?.transaction_no ?? ''" :onPost="postComment"
                  class="px-4 mt-8" />
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- EMPTY STATE -->
      <div v-else class="p-10 text-center text-gray-400">
        No document found.
      </div>
    </template>
  </div>
</template>
