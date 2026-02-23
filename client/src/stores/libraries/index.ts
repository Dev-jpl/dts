import { defineStore } from 'pinia';
import { state } from './state';
import { actions } from './actions';
import { getters } from './getters';
import type { LibrariesState } from './types';
import { fetchOffices } from '@/services/libraries/officeService';
import { fetchActions } from '@/services/libraries/actionService';
import { fetchDocumentTypes } from '@/services/libraries/documentTypeService';

export const useLibraryStore = defineStore('library', {
    state: (): LibrariesState => ({ ...state }),
    actions: {
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

        async loadActionLibrary() {
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


        async loadOfficeLibrary() {
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
    },
    getters,
});