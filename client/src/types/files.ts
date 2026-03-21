export interface FileFolder {
    id: number;
    office_id: string;
    name: string;
    description: string | null;
    parent_id: number | null;
    created_by_id: string;
    created_by_name: string;
    created_at: string;
    updated_at: string;
    files_count?: number;
    children?: FileFolder[];
}

export interface OfficeFile {
    id: number;
    folder_id: number | null;
    office_id: string;
    file_name: string;
    original_name: string;
    file_path: string;
    mime_type: string;
    file_size: number;
    enhancement_status: 'pending' | 'processing' | 'completed' | 'failed' | 'skipped';
    enhancement_error: string | null;
    ocr_text: string | null;
    ocr_status: 'pending' | 'processing' | 'completed' | 'failed' | 'skipped';
    ocr_error: string | null;
    has_enhanced: boolean;
    has_thumbnail: boolean;
    has_searchable_pdf: boolean;
    uploaded_by_id: string;
    uploaded_by_name: string;
    created_at: string;
    updated_at: string;
    folder?: { id: number; name: string } | null;
    attachment_count?: number;
    search_rank?: number;
}

export interface FoldersResponse {
    folders: FileFolder[];
    root_file_count: number;
}
