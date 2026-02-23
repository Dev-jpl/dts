import type { LibrariesState } from './types';

export const getters = {
    isAnyLoading: (state: LibrariesState): boolean =>
        state.documentTypeLibrary.isLoading ||
        state.actionLibrary.isLoading ||
        state.officeLibrary.isLoading,

    totalDocumentTypes: (state: LibrariesState): number =>
        state.documentTypeLibrary.data.length,

    hasDocumentTypeError: (state: LibrariesState): boolean =>
        state.documentTypeLibrary.hasError,

    availableDocumentTitles: (state: LibrariesState): string[] =>
        state.documentTypeLibrary.data.map((item) => item.item_title),
};