import { ref } from "vue";


export function useExpandableTextArray(maxLength = 140) {
    // const maxLength = 140;
    const expandedStates = ref<Record<string, boolean>>({});

    const isExpanded = (id: string) => expandedStates.value[id] === true;

    const toggleExpanded = (id: string) => {
        expandedStates.value[id] = !expandedStates.value[id];
    };

    const getDisplayText = (
        id: string,
        text: string
    ) => {
        const isOpen = isExpanded(id);
        return isOpen || text.length <= maxLength
            ? text
            : text.slice(0, maxLength) + "…";
    };

    return {
        maxLength,
        isExpanded,
        toggleExpanded,
        getDisplayText,
    };
}
