<script setup lang="ts">
const props = defineProps<{
  historyLogs: Array<{
    date: string
    logs: Array<any>
  }>
  isLoading: boolean
  hasError: boolean
}>()
function generateDescriptiveAction(log: any): string {
  const { activity, transactingOffice, remarks } = log;

  const formatDestinations = (list: any[]) =>
    list
      .map((target) => `${target.receivingOffice} (${target.department})`)
      .join(", ");

  switch (activity) {
    case "Received":
      return `Document received by ${transactingOffice.office} (${transactingOffice.service}) for processing`;

    case "Released":
      if (transactingOffice?.routedTo?.length) {
        return transactingOffice.multipleRecipients
          ? `Document released to multiple offices: ${formatDestinations(
            transactingOffice.routedTo
          )}`
          : `Document released to ${formatDestinations(
            transactingOffice.routedTo
          )}`;
      }
      return "Document released to next office";

    case "Profiled":
      if (transactingOffice?.assignedRecipients?.length) {
        return transactingOffice.routing == "Multiple"
          ? `Document profiled and assigned to multiple recipients: ${formatDestinations(
            transactingOffice.assignedRecipients
          )}`
          : `Document profiled and assigned to ${formatDestinations(
            transactingOffice.assignedRecipients
          )}`;
      }
      return "Document created and profiled for initial routing";

    case "Forward":
      if (transactingOffice?.routedTo?.length) {
        return transactingOffice.multipleRecipients
          ? `Document forwarded to multiple offices: ${formatDestinations(
            transactingOffice.routedTo
          )}`
          : `Document forwarded to ${formatDestinations(
            transactingOffice.routedTo
          )}`;
      }
      return "Document forwarded internally";

    case "Archived":
      if (transactingOffice?.archivedFrom) {
        const { originatingOffice, activityType, date } =
          transactingOffice.archivedFrom;
        return `Document archived by ${transactingOffice.office} (${transactingOffice.service
          }) from ${originatingOffice} after ${activityType.toLowerCase()} on ${date}`;
      }
      return `Document archived by ${transactingOffice.office} (${transactingOffice.service})`;

    default:
      return remarks || "No descriptive action available";
  }
}
</script>
<template>
  <div>
    <div v-if="props.isLoading">Loading logs...</div>
    <div v-else-if="props.hasError">Error: {{ props.hasError }}</div>
    <div v-else>
      <template v-for="(group, index) in historyLogs" :key="index">
        <!-- Heading -->
        <div class="my-2 ps-2 first:mt-0">
          <h3 class="text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">
            {{ group.date }}
          </h3>
        </div>

        <!-- Logs -->
        <div v-for="(log, logIndex) in group.logs" :key="logIndex" class="flex gap-x-3">
          <!-- Icon -->
          <div
            class="relative last:after:hidden after:absolute after:top-7 after:bottom-0 after:start-3.5 after:w-px after:-translate-x-[0.5px] after:bg-gray-200 dark:after:bg-neutral-700">
            <div class="relative z-10 flex items-center justify-center size-7">
              <div class="bg-gray-400 rounded-full size-2 dark:bg-neutral-600"></div>
            </div>
          </div>

          <!-- Right Content -->
          <div class="grow pt-0.5 pb-8">
            <div class="flex items-end justify-between">
              <h3 class="flex gap-x-1.5 items-center font-semibold text-gray-800 dark:text-white">
                <!-- Icon -->
                <!-- <svg
                class="shrink-0 size-4"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path
                  d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"
                />
                <polyline points="14 2 14 8 20 8" />
                <line
                  x1="16"
                  y1="13"
                  x2="8"
                  y2="13"
                />
                <line
                  x1="16"
                  y1="17"
                  x2="8"
                  y2="17"
                />
                <line
                  x1="10"
                  y1="9"
                  x2="8"
                  y2="9"
                />
              </svg> -->
                {{ log.activity }}
                <span class="text-[10px] text-gray-700 font-normal">{{
                  log.time
                  }}</span>
              </h3>
            </div>

            <p class="mt-3 text-xs text-gray-600 dark:text-neutral-400">
              {{ generateDescriptiveAction(log) }}
            </p>

            <button type="button"
              class="flex p-1 mt-3 text-xs text-gray-500 border border-transparent rounded-lg -ms-1 gap-x-2 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700">
              <span class="mr-2 bg-gray-300 rounded-full shrink-0 size-4 dark:bg-neutral-600" />
              <div class="">
                <h5 class="flex items-center text-nowrap">
                  {{ log.assignedPresonnel }}
                </h5>
                <div class="flex text-[10px] text-gray-400 text-left">
                  {{ `${log.transactingOffice.office}` }}
                </div>
              </div>
            </button>

            <p v-if="log.remarks"
              class="p-2 mt-1 text-xs text-gray-600 border border-gray-100 rounded-md dark:text-neutral-400 bg-gray-50">
              <span class="text-xs font-medium">Remarks: </span>
              {{ log.remarks }}
            </p>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
