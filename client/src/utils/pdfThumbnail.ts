import * as pdfjsLib from 'pdfjs-dist'

// Set worker globally (optional if set elsewhere)
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://unpkg.com/pdfjs-dist@5.3.93/build/pdf.worker.min.js'

/**
 * Convert first page of PDF to image thumbnail
 */
export async function generatePdfThumbnail(
    pdfUrl: string,
    scale = 0.4
): Promise<string> {
    const loadingTask = pdfjsLib.getDocument(pdfUrl)
    const pdf = await loadingTask.promise
    const page = await pdf.getPage(1)

    const viewport = page.getViewport({ scale })
    const canvas = document.createElement('canvas')
    const context = canvas.getContext('2d')!

    canvas.width = viewport.width
    canvas.height = viewport.height

    await page.render({ canvasContext: context, viewport }).promise

    return canvas.toDataURL('image/png') // Base64 image
}