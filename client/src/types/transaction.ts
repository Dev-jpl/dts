export interface Document {
    document_no: string
    document_type: string
    action_type: string
    origin_type: string
    subject: string
    remarks?: string
    status: 'Draft' | 'Active' | 'Returned' | 'Completed' | 'Closed'
    office_id: string
    office_name: string
    created_by_id: string
    created_by_name: string
    allow_copy: boolean
    qr_code?: string | null
    isActive: boolean
    created_at: string
    updated_at: string
}

export interface DocumentRecipient {
    id: number
    document_no: string
    transaction_no: string
    recipient_type: 'default' | 'cc' | 'bcc'
    sequence?: number
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

export interface DocumentAttachment {
    id: number
    document_no: string
    transaction_no: string
    file_name: string
    file_path: string
    mime_type: string
    file_size: number
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
    status: 'Draft' | 'Processing' | 'Returned' | 'Completed'
    routing: 'Single' | 'Multiple' | 'Sequential'
    urgency_level: 'Urgent' | 'High' | 'Normal' | 'Routine'
    due_date?: string | null
    parent_transaction_no?: string | null
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
    status:
        | 'Profiled'
        | 'Released'
        | 'Received'
        | 'Forwarded'
        | 'Returned To Sender'
        | 'Done'
        | 'Closed'
        | 'Routing Halted'
        | 'Document Revised'
        | 'Recipient Added'
        | 'Recipient Removed'
        | 'Recipients Reordered'
    action_taken: string
    activity: string
    reason?: string | null
    remarks?: string | null
    assigned_personnel_id: string
    assigned_personnel_name: string
    created_at: string
    updated_at: string
}

export interface DocumentNote {
    id: number
    document_no: string
    transaction_no: string
    note: string
    office_id: string
    office_name: string
    created_by_id: string
    created_by_name: string
    created_at: string
    updated_at: string
}
