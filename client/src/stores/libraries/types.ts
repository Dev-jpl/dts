export interface LibraryItem {
    item_id: string | number;
    item_title: string;
    item_desc?: string;
    return_value?: any;
}

export interface ActionReturnValue {
    id: number;
    action: string;
    type: 'FA' | 'FI';
    reply_is_terminal: boolean;
    requires_proof: boolean;
    proof_description: string | null;
    default_urgency_level: string | null;
}

export interface ActionLibraryItem {
    item_id: number;
    item_title: string;
    item_desc: string;
    return_value: ActionReturnValue;
}

export interface LibraryModule<T = LibraryItem> {
    isLoading: boolean;
    hasError: boolean;
    data: T[];
}

export interface LibrariesState {
    documentTypeLibrary: LibraryModule;
    actionLibrary: LibraryModule<ActionLibraryItem>;
    officeLibrary: LibraryModule;
    loading: boolean;
    error: string | null;
}