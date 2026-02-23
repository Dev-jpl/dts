<script setup lang="ts">
import { computed } from 'vue'
import type { transaction } from '@/types/transactions'

import OriginBadge from "@/components/ui/badges/OriginBadge.vue"
import StatusBadge from "@/components/ui/badges/StatusBadge.vue"
import FrmLabel from "@/components/ui/labels/FrmLabel.vue"
import HistoryLogs from "@/components/view-document/HistoryLogs.vue"

import { HiClipboardDocument } from "vue-icons-plus/hi2"
import RoutingInfoIcon from '../ui/icons/RoutingInfoIcon.vue'
import FileInfoIcon from '../ui/icons/FileInfoIcon.vue'
import CaretDownIcon from '../ui/icons/CaretDownIcon.vue'
import { useToggle } from '@/utils/toggler'

/**
 * Props
 */
const props = defineProps<{
  transactions: transaction | null
}>()

/**
 * Computed helpers
 */
const document = computed(() => props.transactions?.document ?? null)
const recipients = computed(() => (props.transactions?.recipients ?? []).filter((r: any) => r.recipient_type === "default"))
const recipients_cc = computed(() => (props.transactions?.recipients ?? []).filter((r: any) => r.recipient_type === "cc"))
const recipients_bcc = computed(() => (props.transactions?.recipients ?? []).filter((r: any) => r.recipient_type === "bcc"))
const signatories = computed(() => props.transactions?.signatories ?? [])
const attachments = computed(() => props.transactions?.attachments ?? [])
const logs = computed(() => props.transactions?.logs ?? [])

const formattedDate = computed(() =>
  document.value
    ? new Date(document.value.created_at).toLocaleDateString()
    : '—'
)

const toggleRoutingDetails = useToggle(true);
</script>

<template>
  <div v-if="transactions && document" class="flex flex-co">
    <div class="flex w-full p-4 pb-[10rem] dark:bg-gray-800 dark:text-white">
      <div class="w-full space-y-3">

        <!-- ================= HEADER ================= -->
        <div class="p-5 text-white bg-teal-800 rounded-md">
          <div class="flex flex-col justify-between gap-2 sm:flex-row">
            <div class="font-bold">
              Document Number:
              <span class="inline-flex items-center ml-2 text-lime-200">
                {{ document.document_no }}
                <HiClipboardDocument class="ml-2 size-4" />
              </span>
            </div>

            <div class="flex items-center">
              <span class="text-[10px] text-gray-300 mr-2">Status:</span>
              <StatusBadge :status="document.status" />
            </div>
          </div>

          <hr class="my-4 text-teal-700" />

          <div class="grid gap-4 sm:grid-cols-12">
            <div class="col-span-2">
              <div class="text-[10px] text-gray-300">Date</div>
              <div class="text-xs font-medium">{{ formattedDate }}</div>
            </div>

            <div class="col-span-4">
              <div class="text-[10px] text-gray-300">Document Type</div>
              <div class="text-xs font-medium">
                {{ document.document_type }}
              </div>
            </div>

            <div class="col-span-4">
              <div class="text-[10px] text-gray-300">Action Type</div>
              <div class="text-xs font-medium">
                {{ document.action_type }}
              </div>
            </div>

            <div class="col-span-2">
              <div class="text-[10px] text-gray-300">Origin</div>
              <OriginBadge :origin-type="document.origin_type" />
            </div>
          </div>
        </div>

        <div class="h-[calc(100svh-100px)] overflow-y-auto">
          <!-- //Title -->
          <div :class="!toggleRoutingDetails.isToggled.value ? 'border ' : ''" class="p-2 border-gray-200 rounded-md">
            <div class="flex items-center justify-between">
              <h1 class="flex items-center text-lg font-semibold text-gray-800 ">
                <RoutingInfoIcon class="mr-2 text-gray-500 size-5 hover:text-gray-700" />
                Routing Details
              </h1>
              <button @click="toggleRoutingDetails.toggle()" type="button" class="flex items-center p-2 text-xs">
                <span v-if="toggleRoutingDetails.isToggled.value"
                  class="text-gray-500 hover:underline hover:text-gray-700">
                  Hide details
                </span>
                <span v-else class="text-gray-500 hover:text-gray-700 hover:underline">
                  Show details
                </span>
                <CaretDownIcon :class="toggleRoutingDetails.isToggled.value ? 'rotate-180' : ''"
                  class="ml-1 text-gray-500 size-5 hover:text-gray-700" />
              </button>
            </div>

            <div v-if="toggleRoutingDetails.isToggled.value" class="space-y-2">
              <!-- ================= FROM ================= -->
              <div class="grid grid-cols-12 rounded-md bg-gray-50">
                <div class="col-span-2 p-3">
                  <FrmLabel label="From:" />
                </div>
                <div class="col-span-10 p-3 pl-10 text-xs">
                  {{ document.office_name }}
                </div>
              </div>

              <!-- ================= TO ================= -->
              <div class="grid grid-cols-12 rounded-md bg-gray-50">
                <div class="col-span-2 p-3">
                  <FrmLabel label="To:" />
                </div>

                <div class="col-span-10 p-3 pl-10 text-xs">
                  <ul class="space-y-1 list-disc list-inside">
                    <li v-for="recipient in recipients" :key="recipient.id" class="disk">
                      {{ recipient.office_name }}
                      <span class="text-gray-400 uppercase text-[10px]">
                        ({{ recipient.recipient_type }})
                      </span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- ================= CC ================= -->
              <div class="grid grid-cols-12 rounded-md bg-gray-50">
                <div class="col-span-2 p-3">
                  <FrmLabel label="Cc:" />
                </div>

                <div class="col-span-10 p-3 pl-10 text-xs">
                  <ul class="space-y-1 list-disc list-inside">
                    <li v-for="recipient in recipients_cc" :key="recipient.id" class="disk">
                      {{ recipient.office_name }}
                      <span class="text-gray-400 uppercase text-[10px]">
                        ({{ recipient.recipient_type }})
                      </span>
                    </li>
                  </ul>
                </div>
              </div>
              <!-- ================= BCC ================= -->
              <div class="grid grid-cols-12 rounded-md bg-gray-50">
                <div class="col-span-2 p-3">
                  <FrmLabel label="Bcc:" />
                </div>

                <div class="col-span-10 p-3 pl-10 text-xs">
                  <ul class="space-y-1 list-disc list-inside">
                    <li v-for="recipient in recipients_bcc" :key="recipient.id" class="disk">
                      {{ recipient.office_name }}
                      <span class="text-gray-400 uppercase text-[10px]">
                        ({{ recipient.recipient_type }})
                      </span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between mt-4">
            <h1 class="flex items-center text-lg font-semibold text-gray-800 ">
              <FileInfoIcon class="mr-2 text-gray-500 size-5 hover:text-gray-700" />
              Document Information
            </h1>
            <button type="button">

            </button>
          </div>


          <div class="p-2 space-y-2">
            <!-- ================= SUBJECT ================= -->
            <div class="grid grid-cols-12 rounded-md bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Subject:" />
              </div>
              <div class="col-span-10 p-3 pl-10 text-xs">
                {{ document.subject }}
              </div>
            </div>

            <!-- ================= REMARKS ================= -->
            <div class="grid grid-cols-12 rounded-md bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Remarks:" />
              </div>
              <div class="col-span-10 p-3 pl-10 text-xs">
                {{ document.remarks ?? '—' }}
              </div>
            </div>

            <!-- ================= SIGNATORIES ================= -->
            <div class="grid grid-cols-12 rounded-md bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Signatories:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <ul class="space-y-2">
                  <li v-if="signatories.length === 0" class="text-xs text-gray-400">
                    No signatories
                  </li>
                  <li v-for="signatory in signatories" :key="signatory.id">
                    <div class="text-xs font-medium">
                      {{ signatory.employee_name }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ signatory.office_name }}
                    </div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- ================= ATTACHMENTS ================= -->
            <div class="grid grid-cols-12 rounded-md bg-gray-50">
              <div class="col-span-2 p-3">
                <FrmLabel label="Attachments:" />
              </div>
              <div class="col-span-10 p-3 pl-10">
                <ul class="space-y-1">
                  <li v-if="attachments.length === 0" class="text-xs text-gray-400">
                    No attachments
                  </li>

                  <li v-for="file in attachments" :key="file.id" class="text-xs">
                    {{ file.file_name }}
                    <span class="text-[10px] text-gray-400">
                      ({{ file.attachment_type }})
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- ================= HISTORY ================= -->
          <!-- <HistoryLogs :logs="logs" /> -->
        </div>
      </div>
    </div>
  </div>

  <!-- EMPTY STATE -->
  <div v-else class="p-10 text-center text-gray-400">
    No document selected
  </div>
</template>