import type { DocumentInfo, Recipient, UploadedFile } from "@/types";
import { reactive, ref, type Ref } from "vue";
import type { UploadTask } from "./useUploader";
import { formatSize } from "@/utils/formatSize";

const documentToReleaseDetails = reactive<DocumentInfo>({
    //Default date to current date
    date: new Date(),
    documentType: {
        id: "",
        code: "",
        type: ""
    },
    documentTypeID: "",
    actionTaken: {
        id: 0,
        action: ""
    },
    actionID: "",
    originType: "Internal", // Default originType to "Internal" for consistency
    sender: "",
    sender_position: "",
    sender_office: "",
    sender_email: "",
    subject: "",
    remarks: "",
    signatories: [],
    recipients: [],
    files: [], // documents uploaded - main document
    attachments: [], //documents uploaded - secondary document
    isSendToMany: false,
    isBindDocument: false,
    bindedDocuments: [],
    isDone: false,
});

export function useDocumentToReleaseDetails() {
    function updateDocumentInfo(newInfo: Partial<DocumentInfo>) {
        Object.assign(documentToReleaseDetails, newInfo);
        console.log("Updated Document Information:", documentToReleaseDetails);
    }

    function resetDocumentInfo() {
        Object.assign(documentToReleaseDetails, {
            date: new Date(),
            documentType: {
                id: "",
                type: ""
            },
            actionTaken: {
                id: "",
                action: ""
            },
            originType: "Internal",
            subject: "",
            sender: "",
            sender_position: "",
            sender_office: "",
            remarks: "",
            recipients: [],
            files: [],
            attachments: [],
            signatories: [],
            isDone: false,
        });
        console.log("Document Information reset to default values.");
    }

    function addRecipient(recipient: any) {
        documentToReleaseDetails.recipients.push(recipient);

    }

    function removeRecipient(toRemove: Recipient) {
        documentToReleaseDetails.recipients = documentToReleaseDetails.recipients.filter(
            recipient =>
                recipient.office_code !== toRemove.office_code
        )
    }

    function extractResponses(rawResponses: any[]): UploadedFile[] {

        console.log(rawResponses);

        return rawResponses.map((res) => {
            const file = res[0]?.attributes ?? res[0]; // Handle nested data

            console.log('file', file);
            console.log('file.original_name', file.original_name);

            return {
                name: file.original_name || "untitled",
                size: formatSize((file.size ?? 0) * 1024),
                type: file.mime || file.ext || "application/octet-stream",
                url: file.url ? `http://localhost:1337${file.url}` : "",
                uploaded_at: file.createdAt || file.updatedAt || null,
                document_id: file.documentId || null,
                id: file.id ?? undefined,
                width: file.width ?? undefined,
                height: file.height ?? undefined,
            };
        });
    }

    function setFiles(responses: any[]) {
        documentToReleaseDetails.files = extractResponses(responses);
    }

    function setAttachments(responses: any[]) {
        documentToReleaseDetails.attachments = extractResponses(responses);
    }

    return {
        documentToReleaseDetails,
        updateDocumentInfo,
        resetDocumentInfo,
        addRecipient,
        removeRecipient,
        extractResponses,
        setFiles,
        setAttachments
    };
}