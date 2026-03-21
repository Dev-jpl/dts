<script setup lang="ts">
import { ref, computed, watch, type CSSProperties } from "vue";
import { useRouter } from "vue-router";
import BaseButton from "../ui/buttons/BaseButton.vue";
import FrmLabel from "../ui/labels/FrmLabel.vue";
import Toggle from "../ui/switches/Toggle.vue";
import { HiCog } from "vue-icons-plus/hi2";
import ReplyHeader from "./ReplyHeader.vue";
import { useStepNavigation } from "@/composables/useStepNavigation";
import { useDocumentInformation } from "@/composables/useDocumentInformation";
import { useTransaction } from "@/composables/useTransaction";
import { usePrintSettings } from "@/composables/usePrintingSettings";
import type { Recipient } from "@/types";

const { setStep, meta_data } = useStepNavigation();
const router = useRouter();
const { documentInformation, resetDocumentInfo } = useDocumentInformation();
const { releaseDocument } = useTransaction();

const {
  pageSize,
  orientation,
  snapEnabled,
  showGrid,
  documentDimensions,
  pageScale,
  pageScalePercentage,
  showSettings,
  togglePrintSettings,
  plusSize,
  minusSize,
} = usePrintSettings();

const documentWidth = computed(() => documentDimensions.value.width);
const documentHeight = computed(() => documentDimensions.value.height);

// ─── QR code from meta_data (base64 SVG) ─────────────────────────────────────

const qrCodeSrc = computed(() =>
  meta_data.value?.qr_code
    ? `data:image/svg+xml;base64,${meta_data.value.qr_code}`
    : ''
);

const documentNumber = computed(() => meta_data.value?.document_no ?? '');

// ─── QR draggable position ────────────────────────────────────────────────────

const qrPosition = ref<{ x: number; y: number }>({ x: 170, y: 10 });
const qrSize = ref<{ width: number; height: number }>({ width: 20, height: 20 });

const documentStyle = computed(() => ({
  width: `${documentWidth.value}mm`,
  height: `${documentHeight.value}mm`,
  border: "1px solid black",
  position: "relative",
} as CSSProperties));

const qrStyle = computed(() => ({
  width: `${qrSize.value.width}mm`,
  height: `${qrSize.value.height}mm`,
  position: "absolute",
  top: `${qrPosition.value.y}mm`,
  left: `${qrPosition.value.x}mm`,
} as CSSProperties));

const isDragging = ref(false);

const onDragStart = (event: DragEvent) => {
  isDragging.value = true;
  const img = new Image();
  img.src = "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg'></svg>";
  event.dataTransfer?.setDragImage(img, 0, 0);
  event.dataTransfer?.setData("text/plain", JSON.stringify(qrPosition.value));
};

const pxToMm = (px: number) => px * 0.264583;

const onDragEnd = (event: DragEvent) => {
  isDragging.value = false;
  const documentRect = (event.target as HTMLElement).parentElement?.getBoundingClientRect();
  if (documentRect) {
    let newX = pxToMm(event.clientX - documentRect.left);
    let newY = pxToMm(event.clientY - documentRect.top);
    if (snapEnabled.value) {
      const snapped = snapToGrid(newX, newY);
      newX = snapped.x;
      newY = snapped.y;
      selectedGrid.value = { x: newX, y: newY };
    } else {
      selectedGrid.value = null;
    }
    qrPosition.value = {
      x: clamp(newX, 0, documentWidth.value - qrSize.value.width),
      y: clamp(newY, 0, documentHeight.value - qrSize.value.height),
    };
  }
};

const clamp = (value: number, min: number, max: number) =>
  Math.max(min, Math.min(max, value));

const snapToGrid = (x: number, y: number) => {
  const gridSize = 20;
  return {
    x: Math.round(x / gridSize) * gridSize,
    y: Math.round(y / gridSize) * gridSize,
  };
};

const mouse = ref({ x: 0, y: 0 });

window.addEventListener("dragover", (e) => {
  mouse.value = { x: e.clientX, y: e.clientY };
});

const floatingQrStyle = computed(() => ({
  top: `${mouse.value.y}px`,
  left: `${mouse.value.x}px`,
}));

const gridSize = 20;
const gridLinesX = computed(() => Math.floor(documentWidth.value / gridSize));
const gridLinesY = computed(() => Math.floor(documentHeight.value / gridSize));

const selectedGrid = ref<{ x: number; y: number } | null>(null);

watch(snapEnabled, (newVal) => {
  showGrid.value = newVal;
});

// ─── Routing helper ───────────────────────────────────────────────────────────

const defaultRecipients = computed(() =>
  documentInformation.recipients.filter(
    (recipient: Recipient) => recipient.recipient_type === "default"
  )
);

const routed_office = computed(() => {
  switch (documentInformation.routing) {
    case "Single": {
      const recipient: Recipient | undefined = defaultRecipients.value[0];
      return {
        id: recipient?.office_code || "",
        office_name: recipient?.office || "",
        office_type: recipient?.recipient_type || "",
      };
    }
    case "Multiple":
      return { id: "", office_name: "", office_type: "" };
    case "Sequential": {
      const recipient: Recipient | undefined = defaultRecipients.value[0];
      return {
        id: recipient?.office_code || "",
        office_name: recipient?.office || "",
        office_type: recipient?.recipient_type || "",
      };
    }
    default:
      return { id: "", office_name: "", office_type: "" };
  }
});

// ─── Print ────────────────────────────────────────────────────────────────────

const printDocument = () => {
  const content = document.getElementById("printable-document");
  if (!content) return;

  const iframe = document.createElement("iframe");
  iframe.style.position = "absolute";
  iframe.style.width = "0";
  iframe.style.height = "0";
  iframe.style.border = "none";

  document.body.appendChild(iframe);

  const iframeDoc = iframe.contentWindow?.document;
  if (!iframeDoc) return;

  iframeDoc.open();
  iframeDoc.write(`
    <html>
      <head>
        <title>Print Document</title>
        <style>
          @page { size: ${pageSize.value} ${orientation.value}; margin: 0; }
          body { margin: 0; font-family: sans-serif; background: white !important; }
        </style>
      </head>
      <body>
        ${content.innerHTML}
      </body>
    </html>
  `);
  iframeDoc.close();

  iframe.onload = () => {
    iframe.contentWindow?.focus();
    iframe.contentWindow?.print();
    setTimeout(() => document.body.removeChild(iframe), 500);
  };
};

// ─── Complete profiling (release + navigate) ──────────────────────────────────

const completeProfiling = async () => {
  const trxNo = meta_data.value?.transaction_no;
  await releaseDocument(trxNo, {
    remarks: documentInformation.remarks,
  });
  resetDocumentInfo();
  setStep(1);
  router.push({
    name: 'view-document',
    params: { trxNo },
  });
};
</script>

<template>
  <div class="relative flex-1 w-full h-full bg-gray-50">
    <div class="flex items-center justify-between col-span-5 p-2">
      <ReplyHeader />
      <div class="flex gap-2 ml-auto">
        <BaseButton :btn-text="'Back to previous step'" :action="() => setStep(3)"
          :className="'flex items-center ml-auto'">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
            class="mr-2 rotate-180 size-4">
            <path fill-rule="evenodd"
              d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm4.28 10.28a.75.75 0 0 0 0-1.06l-3-3a.75.75 0 1 0-1.06 1.06l1.72 1.72H8.25a.75.75 0 0 0 0 1.5h5.69l-1.72 1.72a.75.75 0 1 0 1.06 1.06l3-3Z"
              clip-rule="evenodd" />
          </svg>
        </BaseButton>
        <BaseButton :btn-text="'Finish Up'" :action="() => completeProfiling()"
          :className="'flex items-center ml-auto'">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-2 size-4">
            <path fill-rule="evenodd"
              d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
              clip-rule="evenodd" />
          </svg>
        </BaseButton>
      </div>
    </div>
    <hr class="text-gray-200" />
    <div class="relative flex flex-1 h-[calc(100svh-100px)] overflow-y-auto p-3 overflow-auto bg-gray-200 max-w-screen">
      <!-- QR Preview -->
      <div class="relative flex justify-center mx-auto">
        <div id="qr-placer">
          <div id="printable-document" class="mx-auto bg-white shadow-lg document" :class="[pageSize, orientation]"
            :style="documentStyle">
            <img v-if="qrCodeSrc" :class="isDragging ? 'scale-[1.03] shadow-lg z-50' : ''"
              class="absolute transition-all duration-150 ease-in-out cursor-grab active:cursor-grabbing"
              :src="qrCodeSrc" alt="QR Code" :style="qrStyle" draggable="true" @dragstart="onDragStart"
              @dragend="onDragEnd" />

            <div v-if="isDragging && qrCodeSrc" :style="floatingQrStyle" class="fixed z-50 pointer-events-none">
              <div class="flex flex-col items-center">
                <img :src="qrCodeSrc" alt="QR Code" class="w-[20mm] h-auto shadow-lg rounded" />
                <span class="px-1 text-xs text-gray-800 rounded bg-white/90">
                  {{ documentNumber }}
                </span>
              </div>
            </div>

            <div v-if="selectedGrid" :style="{
              top: `${selectedGrid.y}mm`,
              left: `${selectedGrid.x}mm`,
              width: `${gridSize}mm`,
              height: `${gridSize}mm`,
            }" class="absolute z-10 rounded-sm pointer-events-none bg-gray-300/20"></div>

            <!-- Grid lines overlay -->
            <div v-if="showGrid" class="absolute inset-0 z-0 pointer-events-none">
              <div v-for="i in gridLinesX" :key="'x-' + i" :style="{ left: `${i * gridSize}mm` }"
                class="absolute top-0 h-full w-[1px] bg-gray-300/30 hover:bg-gray-400/50 transition-colors duration-150">
              </div>
              <div v-for="i in gridLinesY" :key="'y-' + i" :style="{ top: `${i * gridSize}mm` }"
                class="absolute left-0 w-full h-[1px] bg-gray-300/30 hover:bg-gray-400/50 transition-colors duration-150">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Printing Settings Panel -->
    <div
      class="absolute flex flex-col items-center p-3 mb-8 bg-gray-100 border border-b border-gray-300 rounded-lg shadow-2xl top-14 right-2">
      <button @click="togglePrintSettings" type="button" class="flex items-center">
        <HiCog class="mr-1 size-5" />
        <h1 :class="showSettings ? '' : 'hidden'"
          class="text-sm font-semibold text-gray-800 transition-all duration-150">
          Printing Settings
        </h1>
        <span :class="showSettings ? '' : 'hidden'" class="ml-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" :class="showSettings ? '' : 'rotate-90'"
            class="hidden transition-all duration-100 size-4 sm:ml-auto sm:block">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
        </span>
      </button>
      <div v-show="showSettings" class="flex flex-col mt-3 space-y-5 transition-all duration-75 ease-in-out">
        <!-- Size Selection -->
        <div>
          <FrmLabel :label="'Paper Size'" :class="'mb-2'" />
          <select v-model="pageSize"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            <option value="A4">A4</option>
            <option value="Letter">Letter</option>
            <option value="Legal">Legal</option>
          </select>
        </div>

        <!-- Orientation Selection -->
        <div>
          <FrmLabel :label="'Orientation'" :class="'mb-2'" />
          <select v-model="orientation"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            <option value="Portrait">Portrait</option>
            <option value="Landscape">Landscape</option>
          </select>
        </div>
        <Toggle v-model="snapEnabled" :title="'Enable Snapping'" />
        <Toggle :is-disabled="!snapEnabled" v-model="showGrid" :title="'Show Gridlines'" />

        <div>
          <FrmLabel :label="'Preview Size'" :class="'mb-2'" />
          <div class="grid grid-cols-2 gap-2">
            <button type="button" class="p-2 bg-gray-200" @click="plusSize">+</button>
            <button type="button" class="p-2 bg-gray-200" @click="minusSize">-</button>
          </div>
        </div>
      </div>
      <div v-show="showSettings" class="flex w-full mt-5">
        <BaseButton :class="'mx-auto'" :btn-text="'Print QR'" :action="printDocument" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.document {
  display: flex;
  overflow: hidden;
  justify-content: center;
  align-items: center;
}

.grid-line:hover {
  background-color: rgba(156, 163, 175, 0.4);
}
</style>
