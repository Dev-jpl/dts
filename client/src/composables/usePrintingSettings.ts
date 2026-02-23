import { ref, computed } from "vue";

export function usePrintSettings() {
    const pageSize = ref<"A4" | "Letter" | "Legal">("A4");
    const orientation = ref<"Portrait" | "Landscape">("Portrait");
    const pageScalePercentage = ref(100)
    const pageScale = ref('size-[' + pageScalePercentage + '%]')

    const snapEnabled = ref(true);
    const showGrid = ref(true);

    const documentDimensions = computed(() => {
        switch (pageSize.value) {
            case "A4": return orientation.value === "Portrait" ? { width: 210, height: 297 } : { width: 297, height: 210 };
            case "Letter": return orientation.value === "Portrait" ? { width: 216, height: 279 } : { width: 279, height: 216 };
            case "Legal": return orientation.value === "Portrait" ? { width: 216, height: 356 } : { width: 356, height: 216 };
            default: return { width: 210, height: 297 };
        }
    });



    const showSettings = ref(true);
    const togglePrintSettings = () => {
        showSettings.value = !showSettings.value
    }

    const plusSize = () => {
        pageScalePercentage.value = pageScalePercentage.value + 1

        console.log(pageScalePercentage.value);

    }
    const minusSize = () => {
        pageScalePercentage.value = pageScalePercentage.value - 1
    }

    return {
        pageSize,
        orientation,
        snapEnabled,
        showGrid,
        documentDimensions,
        showSettings,
        togglePrintSettings,
        pageScale,
        pageScalePercentage,
        plusSize,
        minusSize
    };
}
