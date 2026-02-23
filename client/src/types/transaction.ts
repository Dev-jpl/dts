export interface Document {
    document_no: string
    document_type: string
    action_type: string
    origin_type: string
    subject: string
    remarks?: string
    status: 'Draft' | 'Processing' | 'Archived'
    office_id: string
    office_name: string
    created_by_id: string
    created_by_name: string
    isActive: boolean
    created_at: string
    updated_at: string
}

export interface DocumentRecipient {
    id: number
    document_no: string
    transaction_no: string
    recipient_type: 'default' | 'cc' | 'bcc'
    office_id: string
    office_name: string
    created_by_id: string
    created_by_name: string
    isActive: boolean
    created_at: string
    updated_at: string
}

export interface DocumentSignatory {
    id: number
    document_no: string
    transaction_no: string
    employee_name: string
    office_name: string
    office_id: string
    created_at: string
    updated_at: string
}

// export interface DocumentAttachment {
//     id: number
//     document_no: string
//     transaction_no: string
//     file_name: string
//     attachment_type: string
//     office_id: string
//     office_name: string
//     created_at: string
//     updated_at: string
// }

export interface DocumentAttachment {
    id: number
    document_no: string
    transaction_no: string
    file_name: string
    file_path: string       // ← add
    mime_type: string       // ← add
    file_size: number       // ← add
    attachment_type: 'main' | 'attachment'
    office_id: string
    office_name: string
    created_by_id: string
    created_by_name: string
    created_at: string
    updated_at: string
}

export interface Transaction {
    transaction_no: string
    transaction_type: string
    document_no: string
    document_type: string
    action_type: string
    origin_type: string
    subject: string
    remarks?: string
    status: 'Draft' | 'Processing' | 'Archived'
    office_id: string
    office_name: string
    created_by_id: string
    created_by_name: string
    isActive: boolean
    created_at: string
    updated_at: string

    // Relations
    document: Document
    recipients: DocumentRecipient[]
    signatories: DocumentSignatory[]
    attachments: DocumentAttachment[]
    logs: DocumentLog[]
}

export interface DocumentLog {
    id: number
    document_no: string
    transaction_no: string
    office_id: string
    office_name: string
    status: 'Profiled' | 'Received' | 'Released' | 'Archived' | 'Returned To Sender' | 'Forwarded'
    action_taken: string
    activity: string
    remarks?: string
    assigned_personnel_id: string
    assigned_personnel_name: string
    created_at: string
    updated_at: string
}
