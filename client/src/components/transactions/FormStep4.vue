<script setup lang="ts">
import BaseButton from "../ui/buttons/BaseButton.vue";
import { useStepNavigation } from "@/composables/useStepNavigation";
import { useDocumentInformation } from "@/composables/useDocumentInformation";

const { setStep, meta_data } = useStepNavigation();

const router = useRouter();
const { documentInformation, resetDocumentInfo } = useDocumentInformation()

import { useTransaction } from "@/composables/useTransaction"

const { releaseDocument } = useTransaction()

const printQRCode = async () => {
  // Logic to print QR code
  alert("QR Code printed!");
};

const defaultRecipients = computed(() => {
  return documentInformation.recipients.filter(
    (recipient: Recipient) => recipient.recipient_type === "default"
  )
})


const routed_office = computed(() => {
  switch (documentInformation.routing) {
    case "Single": {
      // Use the first default recipient (or selected one)
      const recipient: Recipient | undefined = defaultRecipients.value[0]
      return {
        id: recipient?.office_code || "",
        office_name: recipient?.office || "",
        office_type: recipient?.recipient_type || ""
      }
    }

    case "Multiple":
      // No specific routed office, backend will handle recipients separately
      return {
        id: "",
        office_name: "",
        office_type: ""
      }

    case "Sequential": {
      // Use the first recipient in sequence (later you can add logic to skip those already marked as "Received")
      const recipient: Recipient | undefined = defaultRecipients.value[0]
      return {
        id: recipient?.office_code || "",
        office_name: recipient?.office || "",
        office_type: recipient?.recipient_type || ""
      }
    }

    default:
      return {
        id: "",
        office_name: "",
        office_type: ""
      }
  }
})




const completeProfiling = async () => {
  await releaseDocument(meta_data.value.transaction_no, {
    remarks: documentInformation.remarks,
    routed_office: routed_office.value, // ✅ unwrap the computed
  })


  alert("QR Code printed!");
  resetDocumentInfo()
  console.log(meta_data.value);
  setStep(1);
  router.push({
    name: 'view-document',
    params: { trxNo: meta_data.value.transaction_no },
  });
}

import { ref, computed, watch, type CSSProperties } from "vue";
import FrmLabel from "../ui/labels/FrmLabel.vue";
import Toggle from "../ui/switches/Toggle.vue";
import { usePrintSettings } from "@/composables/usePrintingSettings";
import { HiCog } from "vue-icons-plus/hi2";
import ReplyHeader from "./ReplyHeader.vue";
import { useRouter } from "vue-router";
import type { Recipient } from "@/types";

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

const qrCodeUrl = ref<string>("path/to/qr-code.png");
const qrPosition = ref<{ x: number; y: number }>({ x: 170, y: 10 }); // Initial position
const qrSize = ref<{ width: number; height: number }>({
  width: 20,
  height: 20,
}); // QR code size
const documentNumber = ref<string>("123456-abcd-0001");

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
  console.log(event);
  isDragging.value = true;
  // Hide default ghost image
  const img = new Image();
  img.src = "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg'></svg>";
  event.dataTransfer?.setDragImage(img, 0, 0);

  event.dataTransfer?.setData("text/plain", JSON.stringify(qrPosition.value));
};

const pxToMm = (px: number) => px * 0.264583; // 1px ≈ 0.264583mm

const onDragEnd = (event: DragEvent) => {
  isDragging.value = false;

  const documentRect = (
    event.target as HTMLElement
  ).parentElement?.getBoundingClientRect();

  if (documentRect) {
    let newX = pxToMm(event.clientX - documentRect.left);
    let newY = pxToMm(event.clientY - documentRect.top);

    if (snapEnabled.value) {
      const snapped = snapToGrid(newX, newY);
      newX = snapped.x;
      newY = snapped.y;
      selectedGrid.value = { x: newX, y: newY };
    } else {
      selectedGrid.value = null; // Clear snap highlight
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
          @media print {
            @page { margin: 0; }
            body {
              margin: 0;
              font-family: sans-serif;
              background: white !important;
            }
            .A4.Portrait { size: A4 portrait; }
            .A4.Landscape { size: A4 landscape; }
            .Letter.Portrait { size: Letter portrait; }
            .Letter.Landscape { size: Letter landscape; }
            .Legal.Portrait { size: Legal portrait; }
            .Legal.Landscape { size: Legal landscape; }
          }
        </style>
      </head>
      <body class="${pageSize} ${orientation}">
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



</script>

<template>
  <div class="relative flex-1 w-full h-full bg-gray-50">
    <div class="flex items-center justify-between col-span-5 p-2">
      <!-- <h1 class="text-lg font-semibold text-gray-800">Print QR</h1> -->
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
      <!--Start QR Preview  -->
      <div class="relative flex justify-center mx-auto">
        <div id="qr-placer">
          <div id="printable-document" class="mx-auto bg-white shadow-lg document" :class="[pageSize, orientation]"
            :style="documentStyle">
            <img :class="isDragging ? 'scale-[1.03] shadow-lg z-50' : ''"
              class="absolute transition-all duration-150 ease-in-out cursor-grab active:cursor-grabbing"
              src="@/assets/img/qr-code.png" alt="QR Code" :style="qrStyle" draggable="true" @dragstart="onDragStart"
              @dragend="onDragEnd" />

            <div v-if="isDragging" :style="floatingQrStyle" class="fixed z-50 pointer-events-none">
              <div class="flex flex-col items-center">
                <img src="@/assets/img/qr-code.png" alt="QR Code" class="w-[20mm] h-auto shadow-lg rounded" />
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
              <!-- Vertical lines -->
              <div v-for="i in gridLinesX" :key="'x-' + i" :style="{ left: `${i * gridSize}mm` }"
                class="absolute top-0 h-full w-[1px] bg-gray-300/30 hover:bg-gray-400/50 transition-colors duration-150 hover:shadow-sm hover:blur-[0.3px]">
              </div>

              <!-- Horizontal lines -->
              <div v-for="i in gridLinesY" :key="'y-' + i" :style="{ top: `${i * gridSize}mm` }"
                class="absolute left-0 w-full h-[1px] bg-gray-300/30 hover:bg-gray-400/50 transition-colors duration-150 hover:shadow-sm hover:blur-[0.3px]">
              </div>
            </div>
          </div>
        </div>
      </div>
      <!--End QR Preview  -->
    </div>

    <!--Start Printing Settings  -->
    <div
      class="absolute flex flex-col items-center p-3 mb-8 bg-gray-100 border border-b border-gray-300 rounded-lg shadow-2xl top-14 right-2">
      <button @click="togglePrintSettings" type="button" class="flex items-center">
        <HiCog class="mr-1 size-5" />
        <!-- <HiCog8Tooth /> -->
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
            class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option value="A4">A4</option>
            <option value="Letter">Letter</option>
            <option value="Legal">Legal</option>
          </select>
        </div>

        <!-- Orientation Selection -->
        <div>
          <FrmLabel :label="'Orientation'" :class="'mb-2'" />
          <select v-model="orientation"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option value="Portrait">Portrait</option>
            <option value="Landscape">Landscape</option>
          </select>
        </div>
        <Toggle v-model="snapEnabled" :title="'Enable Snapping'" />
        <Toggle :is-disabled="!snapEnabled" v-model="showGrid" :title="'Show Gridlines'" />

        <div>
          <FrmLabel :label="'Preview Size'" :class="'mb-2'" />
          <div class="grid grid-cols-2 gap-2">
            <button type="button" class="p-2 bg-gray-200" @click="plusSize">
              +
            </button>
            <button type="button" class="p-2 bg-gray-200" @click="minusSize">
              -
            </button>
          </div>
        </div>
      </div>
      <div v-show="showSettings" class="flex w-full mt-5">
        <BaseButton :class="'mx-auto'" :btn-text="'Print QR'" :action="printDocument" />
      </div>
    </div>
    <!--End Printing Settings  -->
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

@media print {
  @page {
    margin: 0;
  }

  .A4.Portrait {
    size: A4 portrait;
  }

  .A4.Landscape {
    size: A4 landscape;
  }

  .Letter.Portrait {
    size: Letter portrait;
  }

  .Letter.Landscape {
    size: Letter landscape;
  }

  .Legal.Portrait {
    size: Legal portrait;
  }

  .Legal.Landscape {
    size: Legal landscape;
  }

  body {
    margin: 0;
    background: white !important;
    font-family: sans-serif;
  }

  .no-print {
    display: none !important;
  }
}
</style>
