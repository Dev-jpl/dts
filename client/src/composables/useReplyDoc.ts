// composables/useReplyDocument.ts
import { computed } from "vue";
import { useRoute } from "vue-router";

export function useReplyDocument() {
    const route = useRoute();

    // Extract the document number from the route param
    const documentNo = computed(() => route.params.documentNo as string | undefined);

    // Check if current route is a reply route
    const isReply = computed(() => route.name === "profile-document-reply");

    return {
        documentNo,
        isReply,
    };
}
