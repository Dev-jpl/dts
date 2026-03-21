<?php

namespace App\Jobs;

use App\Models\OfficeFile;
use App\Services\EmbeddingService;
use App\Services\FileEnhancementService;
use App\Services\OcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct(
        protected int $officeFileId,
    ) {}

    public function handle(): void
    {
        $file = OfficeFile::find($this->officeFileId);

        if (!$file) {
            return;
        }

        $absolutePath = Storage::disk('local')->path($file->file_path);

        if (!file_exists($absolutePath)) {
            $file->update([
                'enhancement_status' => OfficeFile::STATUS_FAILED,
                'enhancement_error'  => 'File not found on disk.',
                'ocr_status'         => OfficeFile::STATUS_FAILED,
                'ocr_error'          => 'File not found on disk.',
            ]);
            return;
        }

        $enhancedPath = null;

        // ── Stage 1: Enhancement ──────────────────────────────────────────
        $enhancedPath = $this->runEnhancement($file, $absolutePath);

        // Use enhanced version for subsequent stages, fall back to original
        $workingPath = $enhancedPath ?? $absolutePath;

        // ── Stage 2: Thumbnail ────────────────────────────────────────────
        $this->runThumbnail($file, $workingPath);

        // ── Stage 3: OCR ──────────────────────────────────────────────────
        $ocrText = $this->runOcr($file, $workingPath, $absolutePath);

        // ── Stage 4: Searchable PDF ───────────────────────────────────────
        if ($file->mime_type === 'application/pdf' && !empty($ocrText) && $enhancedPath) {
            $this->runSearchablePdf($file, $enhancedPath);
        }

        // ── Stage 5: Embedding ────────────────────────────────────────────
        if (!empty($ocrText)) {
            $this->runEmbedding($file, $ocrText);
        }
    }

    private function runEnhancement(OfficeFile $file, string $absolutePath): ?string
    {
        $file->update(['enhancement_status' => OfficeFile::STATUS_PROCESSING]);

        try {
            $ext = pathinfo($file->file_name, PATHINFO_EXTENSION);
            $enhancedName = Str::uuid()->toString() . '.' . $ext;
            $folderSegment = $file->folder_id ?? 'root';
            $storagePath = "files/{$file->office_id}/{$folderSegment}/enhanced/{$enhancedName}";
            $enhancedAbsolute = Storage::disk('local')->path($storagePath);

            FileEnhancementService::enhance($absolutePath, $enhancedAbsolute, $file->mime_type);

            $file->update([
                'enhanced_path'      => $storagePath,
                'enhancement_status' => OfficeFile::STATUS_COMPLETED,
                'enhancement_error'  => null,
            ]);

            Log::info("Enhancement completed for office_file #{$this->officeFileId}");
            return $enhancedAbsolute;
        } catch (\Throwable $e) {
            Log::warning("Enhancement failed for office_file #{$this->officeFileId}: " . $e->getMessage());

            $file->update([
                'enhancement_status' => OfficeFile::STATUS_FAILED,
                'enhancement_error'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function runThumbnail(OfficeFile $file, string $workingPath): void
    {
        try {
            $thumbnailName = Str::uuid()->toString() . '.jpg';
            $folderSegment = $file->folder_id ?? 'root';
            $storagePath = "files/{$file->office_id}/{$folderSegment}/thumbnails/{$thumbnailName}";
            $thumbnailAbsolute = Storage::disk('local')->path($storagePath);

            FileEnhancementService::generateThumbnail($workingPath, $thumbnailAbsolute, $file->mime_type);

            $file->update(['thumbnail_path' => $storagePath]);

            Log::info("Thumbnail generated for office_file #{$this->officeFileId}");
        } catch (\Throwable $e) {
            Log::warning("Thumbnail generation failed for office_file #{$this->officeFileId}: " . $e->getMessage());
        }
    }

    private function runOcr(OfficeFile $file, string $workingPath, string $originalPath): ?string
    {
        $file->update(['ocr_status' => OfficeFile::STATUS_PROCESSING]);

        try {
            // Try OCR on enhanced version first
            $text = OcrService::process($workingPath, $file->mime_type);

            // If enhanced version yielded nothing but we have original, try original
            if (empty($text) && $workingPath !== $originalPath) {
                $text = OcrService::process($originalPath, $file->mime_type);
            }

            if (empty($text)) {
                $file->update([
                    'ocr_status' => OfficeFile::STATUS_SKIPPED,
                    'ocr_text'   => null,
                    'ocr_error'  => 'No text could be extracted.',
                ]);
                return null;
            }

            $file->update([
                'ocr_text'   => $text,
                'ocr_status' => OfficeFile::STATUS_COMPLETED,
                'ocr_error'  => null,
            ]);

            Log::info("OCR completed for office_file #{$this->officeFileId}: " . strlen($text) . " chars extracted.");
            return $text;
        } catch (\Throwable $e) {
            Log::error("OCR failed for office_file #{$this->officeFileId}: " . $e->getMessage());

            $file->update([
                'ocr_status' => OfficeFile::STATUS_FAILED,
                'ocr_error'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function runSearchablePdf(OfficeFile $file, string $enhancedAbsolutePath): void
    {
        try {
            $searchableName = Str::uuid()->toString() . '.pdf';
            $folderSegment = $file->folder_id ?? 'root';
            $storagePath = "files/{$file->office_id}/{$folderSegment}/searchable/{$searchableName}";
            $searchableAbsolute = Storage::disk('local')->path($storagePath);

            FileEnhancementService::createSearchablePdf($enhancedAbsolutePath, $searchableAbsolute);

            $file->update(['searchable_pdf_path' => $storagePath]);

            Log::info("Searchable PDF created for office_file #{$this->officeFileId}");
        } catch (\Throwable $e) {
            Log::warning("Searchable PDF creation failed for office_file #{$this->officeFileId}: " . $e->getMessage());
        }
    }

    private function runEmbedding(OfficeFile $file, string $ocrText): void
    {
        try {
            if (!EmbeddingService::isAvailable()) {
                Log::info("Embedding service not available, skipping for office_file #{$this->officeFileId}");
                return;
            }

            $vector = EmbeddingService::embed($ocrText);

            if ($vector) {
                $vectorStr = '[' . implode(',', $vector) . ']';
                $file->update(['embedding' => $vectorStr]);
                Log::info("Embedding generated for office_file #{$this->officeFileId}");
            }
        } catch (\Throwable $e) {
            Log::warning("Embedding failed for office_file #{$this->officeFileId}: " . $e->getMessage());
        }
    }
}
