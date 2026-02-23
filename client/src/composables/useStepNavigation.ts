import type { Step } from "@/types";
import { ref } from "vue";

const currentStep = ref<number>(1);
const meta_data = ref<any>(null);

const steps: Step[] = [
    {
        stepNumber: 1,
        title: "Profile Document",
        description:
            "Enter required document details, designate office recipients, and assign signatories.",
        isDone: false,
    },
    {
        stepNumber: 2,
        title: "Upload Scan",
        description:
            "Upload the scanned copy of the document along with any attachments (Optional).",
        isDone: false,
    },
    {
        stepNumber: 3,
        title: "Document Overview",
        description:
            "Carefully check the document for errors before sending it to recipients.",
        isDone: false,
    },
    {
        stepNumber: 4,
        title: "Print QR",
        description:
            "Attach the QR code to your document, adjusting its position as needed.",
        isDone: false,
    },
];

export function useStepNavigation() {

    function nextStep(stepNumber: number | null = null) {
        currentStep.value = stepNumber !== null ? stepNumber : currentStep.value + 1;
        console.log(`Current Step: ${currentStep.value}`);
    }

    function prevStep(stepNumber: number | null = null) {
        currentStep.value = stepNumber !== null ? stepNumber : currentStep.value - 1;
        console.log(`Current Step: ${currentStep.value}`);
    }

    function setStep(newStep: number) {
        console.log(`Setting step to: ${newStep}`);

        currentStep.value = newStep;
    }

    return {
        currentStep,
        meta_data,
        steps,
        nextStep,
        prevStep,
        setStep,
    };
}