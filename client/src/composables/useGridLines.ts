import { computed, ref, watch } from "vue";

const showGrid = ref(true);
const snapEnabled = ref(true);

export function useGridLines(documentWidth = 210, documentHeight = 297) {
    const gridSize = 20;
    const gridLinesX = computed(() => Math.floor(documentWidth / gridSize));
    const gridLinesY = computed(() => Math.floor(documentHeight / gridSize));
    const showGrid = ref(true);

    const documentStyle = computed(() => ({
        width: `${documentWidth}mm`,
        height: `${documentHeight}mm`,
        border: "1px solid black",
        position: "relative",
    }));

    return {
        gridSize,
        gridLinesX,
        gridLinesY,
        showGrid,
        documentStyle,
    };
}
