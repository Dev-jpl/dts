export interface LibraryItem {
    item_id: string | number;
    item_title: string;
    item_desc?: string;
}

export interface LibraryModule {
    isLoading: boolean;
    hasError: boolean;
    data: LibraryItem[];
}

export interface LibrariesState {
    documentTypeLibrary: LibraryModule;
    actionLibrary: LibraryModule;
    officeLibrary: LibraryModule;
    loading: boolean;
    error: string | null;
}