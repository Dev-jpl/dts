import type { Document } from "./types";

export const state: { document: Document } = {
  document: {
    date: new Date(),
    documentType: "",
    action: "",
    originType: "",
    subject: "",
    remarks: "",
    signatories: "",
    isDone: false
  }
};