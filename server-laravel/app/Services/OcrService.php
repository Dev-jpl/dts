<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Imagick;

class OcrService
{
    /**
     * Run OCR on a single image file (JPEG/PNG).
     */
    public static function processImage(string $absolutePath): string
    {
        $ocr = new TesseractOCR($absolutePath);
        $ocr->lang('eng');

        return trim($ocr->run());
    }

    /**
     * Run OCR on a PDF by converting each page to an image first.
     */
    public static function processPdf(string $absolutePath): string
    {
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($absolutePath);

        $pages = $imagick->getNumberImages();
        $texts = [];

        for ($i = 0; $i < $pages; $i++) {
            $imagick->setIteratorIndex($i);
            $imagick->setImageFormat('png');

            $tempPath = sys_get_temp_dir() . '/ocr_page_' . uniqid() . '.png';
            $imagick->writeImage($tempPath);

            try {
                $text = self::processImage($tempPath);
                if (!empty($text)) {
                    $texts[] = $text;
                }
            } finally {
                @unlink($tempPath);
            }
        }

        $imagick->clear();
        $imagick->destroy();

        return implode("\n\n--- Page Break ---\n\n", $texts);
    }

    /**
     * Process a file based on its MIME type.
     */
    public static function process(string $absolutePath, string $mimeType): string
    {
        if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
            return self::processImage($absolutePath);
        }

        if ($mimeType === 'application/pdf') {
            return self::processPdf($absolutePath);
        }

        return '';
    }
}
