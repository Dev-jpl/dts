export interface Recipient {
    region: string;
    service: string;
    office: string;
    office_code: string;
    sequence?: number;
    recipient_type?: string;
}

export interface Signatory {
    id: string;
    name: string;
    position: string;
    office: string;
    office_id: string;
}

export interface UploadedFile {
    name: string;
    size: string;
    size_bytes: number;
    type: string;
    url: string;
    temp_path: string;
    uploaded_at?: string | null;
    document_id?: string | null;
    id?: number;
    width?: number | null;
    height?: number | null;
}

interface BindedDocuments {
    documentNumber: string;
}

interface DocumnentType {
    id: string
    code: string,
    type: string
}

interface ActionTaken {
    id: number
    action: string
}


export interface DocumentInfo {
    documentNumber?: string
    date: Date
    documentType: DocumnentType
    documentTypeID: string
    actionTaken: ActionTaken
    actionID: string
    originType: "Internal" | "External" | "Email"
    //If originType is "External"
    sender: string | null;
    sender_position: string | null;
    sender_office: string | null;
    //If originType is "Email"
    sender_email: string | null;
    subject: string | null;
    remarks: string | null;
    signatories: Signatory[]
    routing: "Single" | "Multiple" | "Sequential"
    recipients: any[]
    files: UploadedFile[]
    attachments: UploadedFile[]
    isSendToMany: boolean
    isBindDocument: boolean
    bindedDocuments: BindedDocuments[]
    isDone: boolean
}