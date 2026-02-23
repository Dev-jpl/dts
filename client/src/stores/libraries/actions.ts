import { fetchDocumentTypes } from '@/services/libraries/documentTypeService';
// Add other fetch services as needed

import type { LibrariesState } from './types';
import { fetchActions } from '@/services/libraries/actionService';
import { fetchOffices } from '@/services/libraries/officeService';

export const actions: {
    [K: string]: (this: LibrariesState) => Promise<void>;
} = {
    async loadDocumentTypeLibrary() {
        this.documentTypeLibrary.isLoading = true;

        setTimeout(async () => {
            try {
                const types = await fetchDocumentTypes();
                this.documentTypeLibrary.data = types;
            } catch (err) {
                this.documentTypeLibrary.hasError = true;
            } finally {
                this.documentTypeLibrary.isLoading = false;
            }
        }, 2000); // Simulate a delay for loading
    },

    async loadActionLibrary(this: LibrariesState) {
        this.actionLibrary.isLoading = true;

        setTimeout(async () => {
            try {
                const actions = await fetchActions();
                this.actionLibrary.data = actions;
            } catch (err) {
                this.actionLibrary.hasError = true;
            } finally {
                this.actionLibrary.isLoading = false;
            }
        }, 2000); // Simulate a delay for loading
    },


    async loadOfficeLibrary(this: LibrariesState) {
        this.officeLibrary.isLoading = true;

        setTimeout(async () => {
            try {
                const offices = await fetchOffices();
                this.officeLibrary.data = offices;
            } catch (err) {
                this.officeLibrary.hasError = true;
            } finally {
                this.officeLibrary.isLoading = false;
            }
        }, 2000); // Simulate a delay for loading
    },

};

// export const actions = {
//     async loadDocumentTypeLibrary(this: LibrariesState) {
//         this.documentTypeLibrary.isLoading = true;
//         this.documentTypeLibrary.hasError = false;

//         setTimeout(async () => {
//             try {
//                 const types = await fetchDocumentTypes();
//                 this.documentTypeLibrary.data = types;
//             } catch (err) {
//                 this.documentTypeLibrary.hasError = true;
//             } finally {
//                 this.documentTypeLibrary.isLoading = false;
//             }
//             this.documentTypeLibrary.isLoading = false;

//         }, 5000); // Simulate a delay for loading
//     },

//     async loadActionLibrary(this: LibrariesState) {
//         this.actionLibrary.isLoading = true;
//         this.actionLibrary.hasError = false;

//         setTimeout(async () => {
//             try {
//                 const actions = await fetchActions();
//                 this.actionLibrary.data = actions;
//             } catch (err) {
//                 this.actionLibrary.hasError = true;
//             } finally {
//                 this.actionLibrary.isLoading = false;
//             }
//         }, 5000); // Simulate a delay for loading
//     },

//     async fetchOfficeLibrary(this: LibrariesState) {
//         this.officeLibrary.isLoading = true;
//         this.officeLibrary.hasError = false;

//         try {
//             // const offices = await fetchOffices(); ← Add your logic
//             this.officeLibrary.data = []; // Replace with fetched offices
//         } catch (err) {
//             this.officeLibrary.hasError = true;
//         } finally {
//             this.officeLibrary.isLoading = false;
//         }
//     },
// };