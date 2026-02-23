export interface Document {
  date: Date;
  documentType: string;
  action: string;
  originType: string;
  subject: string;
  remarks: string;
  signatories: string;
  isDone: boolean;
}