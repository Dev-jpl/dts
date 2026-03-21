import { ref } from 'vue';
import api from '@/api';
import type { OfficeFile } from '@/types/files';

function isTerminal(status: string): boolean {
    return status === 'completed' || status === 'failed' || status === 'skipped';
}

export function useOcr() {
    const polling = ref(false);

    async function pollOcrStatus(
        fileId: number,
        onUpdate?: (file: OfficeFile) => void,
        intervalMs = 3000,
        maxAttempts = 100,
    ): Promise<OfficeFile | null> {
        polling.value = true;
        let attempts = 0;

        return new Promise((resolve) => {
            const timer = setInterval(async () => {
                attempts++;

                try {
                    const res = await api.get(`/files/${fileId}`);
                    const file: OfficeFile = res.data.data;

                    if (onUpdate) onUpdate(file);

                    // Done when both enhancement and OCR are terminal
                    if (isTerminal(file.enhancement_status) && isTerminal(file.ocr_status)) {
                        clearInterval(timer);
                        polling.value = false;
                        resolve(file);
                        return;
                    }
                } catch {
                    // ignore poll errors, will retry
                }

                if (attempts >= maxAttempts) {
                    clearInterval(timer);
                    polling.value = false;
                    resolve(null);
                }
            }, intervalMs);
        });
    }

    return { polling, pollOcrStatus };
}
