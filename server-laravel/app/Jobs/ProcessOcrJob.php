<?php

namespace App\Jobs;

use App\Models\OfficeFile;
use App\Services\OcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        protected int $officeFileId,
    ) {}

    public function handle(): void
    {
        $file = OfficeFile::find($this->officeFileId);

        if (!$file || !in_array($file->ocr_status, [OfficeFile::STATUS_PENDING, OfficeFile::STATUS_PROCESSING])) {
            return;
        }

        $file->update(['ocr_status' => OfficeFile::STATUS_PROCESSING]);

        $absolutePath = Storage::disk('local')->path($file->file_path);

        if (!file_exists($absolutePath)) {
            $file->update([
                'ocr_status' => OfficeFile::STATUS_FAILED,
                'ocr_error'  => 'File not found on disk.',
            ]);
            return;
        }

        try {
            $text = OcrService::process($absolutePath, $file->mime_type);

            if (empty($text)) {
                $file->update([
                    'ocr_status' => OfficeFile::STATUS_SKIPPED,
                    'ocr_text'   => null,
                    'ocr_error'  => 'No text could be extracted.',
                ]);
                return;
            }

            $file->update([
                'ocr_text'   => $text,
                'ocr_status' => OfficeFile::STATUS_COMPLETED,
                'ocr_error'  => null,
            ]);

            Log::info("OCR completed for office_file #{$this->officeFileId}: " . strlen($text) . " chars extracted.");
        } catch (\Throwable $e) {
            Log::error("OCR failed for office_file #{$this->officeFileId}: " . $e->getMessage());

            $file->update([
                'ocr_status' => OfficeFile::STATUS_FAILED,
                'ocr_error'  => $e->getMessage(),
            ]);
        }
    }
}
