import type { LibrariesState, LibraryItem } from './types';

export const state: LibrariesState = {
    documentTypeLibrary: {
        isLoading: false,
        hasError: false,
        data: [],
    },
    actionLibrary: {
        isLoading: false,
        hasError: false,
        data: [],
    },
    officeLibrary: {
        isLoading: false,
        hasError: false,
        data: [],
    },
    loading: false,
    error: null,
};




// actionLibrary: [
//         {
//             "item_id": "1",
//             "item_title": "Appropriate Action"
//         },
//         {
//             "item_id": "2",
//             "item_title": "Urgent Action"
//         },
//         {
//             "item_id": "3",
//             "item_title": "Dissemination of Information"
//         },
//         {
//             "item_id": "4",
//             "item_title": "Comment/Reaction/Response"
//         },
//         {
//             "item_id": "5",
//             "item_title": "Compliance/Implementation"
//         },
//         {
//             "item_id": "6",
//             "item_title": "Endorsement/Recommendation"
//         },
//         {
//             "item_id": "7",
//             "item_title": "Coding/Deposit/Preparation of Receipt"
//         },
//         {
//             "item_id": "8",
//             "item_title": "Follow Up"
//         },
//         {
//             "item_id": "9",
//             "item_title": "Investigation/Verification and Report"
//         },
//         {
//             "item_id": "10",
//             "item_title": "Your Information"
//         },
//         {
//             "item_id": "11",
//             "item_title": "Draft of Reply"
//         },
//         {
//             "item_id": "12",
//             "item_title": "Approval"
//         }
//     ],