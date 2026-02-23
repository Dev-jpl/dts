import type { DocumentInfo, Recipient, UploadedFile } from "@/types";
import { reactive, ref, type Ref } from "vue";
import type { UploadTask } from "./useUploader";
import { formatSize } from "@/utils/formatSize";
import API from "@/api";

const documentInformation = reactive<DocumentInfo>({
    //Default date to current date
    documentNumber: '',
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

export function useDocumentInformation() {
    function updateDocumentInfo(newInfo: Partial<DocumentInfo>) {
        Object.assign(documentInformation, newInfo);
        console.log("Updated Document Information:", documentInformation);
    }

    function resetDocumentInfo() {
        Object.assign(documentInformation, {
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
        documentInformation.recipients.push(recipient);

    }

    function removeRecipient(toRemove: Recipient) {
        documentInformation.recipients = documentInformation.recipients.filter(
            recipient =>
                recipient.office_code !== toRemove.office_code
        )
    }

    function extractResponses(rawResponses: any[]): UploadedFile[] {
        return rawResponses
            .filter(Boolean)
            .map((file) => ({
                name: file.original_name ?? 'untitled',
                size: formatSize(file.size ?? 0),
                size_bytes: file.size ?? 0,
                type: file.mime_type ?? 'application/octet-stream',
                url: file.url ?? '',
                temp_path: file.temp_path ?? '',
            }));
    }


    function setFiles(responses: any[]) {
        documentInformation.files = extractResponses(responses);
    }

    function setAttachments(responses: any[]) {
        documentInformation.attachments = extractResponses(responses);
    }

    function removeSignatory(toRemove: string) {
        documentInformation.signatories = documentInformation.signatories.filter(
            signatory =>
                signatory.id !== toRemove
        )
    }

    // async function submitDocument() {
    //     // Here you would typically send `documentInformation` to your backend API

    //     const response = await API.post('/transactions/create', documentInformation);

    //     console.log("Submitting Document Information:", response.data);
    //     return response;
    // }

    async function submitDocument() {
        const response = await API.post('/transactions/create', documentInformation);
        return response;
    }

    return {
        documentInformation,
        updateDocumentInfo,
        resetDocumentInfo,
        addRecipient,
        removeRecipient,
        extractResponses,
        setFiles,
        setAttachments,
        submitDocument,
        removeSignatory
    };
}