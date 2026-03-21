<?php

namespace App\Services;

use Imagick;
use ImagickPixel;
use Illuminate\Support\Facades\Log;

class FileEnhancementService
{
    /**
     * Enhance a single image: deskew, grayscale, normalize, sharpen, despeckle.
     */
    public static function enhanceImage(string $inputPath, string $outputPath): void
    {
        $imagick = new Imagick($inputPath);

        $imagick->deskewImage(40);
        $imagick->transformImageColorspace(Imagick::COLORSPACE_GRAY);
        $imagick->normalizeImage();
        $imagick->sharpenImage(0, 1.0);
        $imagick->despeckleImage();

        // Ensure output directory exists
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $imagick->writeImage($outputPath);
        $imagick->clear();
        $imagick->destroy();
    }

    /**
     * Enhance a PDF by extracting pages, enhancing each, and reassembling.
     */
    public static function enhancePdf(string $inputPath, string $outputPath): void
    {
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($inputPath);

        $pages = $imagick->getNumberImages();
        $enhanced = new Imagick();
        $tempFiles = [];

        try {
            for ($i = 0; $i < $pages; $i++) {
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');

                $tempIn = sys_get_temp_dir() . '/enhance_in_' . uniqid() . '.png';
                $tempOut = sys_get_temp_dir() . '/enhance_out_' . uniqid() . '.png';
                $tempFiles[] = $tempIn;
                $tempFiles[] = $tempOut;

                $imagick->writeImage($tempIn);

                self::enhanceImage($tempIn, $tempOut);

                $page = new Imagick($tempOut);
                $page->setImageFormat('pdf');
                $enhanced->addImage($page);
                $page->clear();
                $page->destroy();
            }

            // Ensure output directory exists
            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $enhanced->writeImages($outputPath, true);
        } finally {
            $enhanced->clear();
            $enhanced->destroy();
            $imagick->clear();
            $imagick->destroy();

            foreach ($tempFiles as $tmp) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Generate a thumbnail JPEG from an image or the first page of a PDF.
     */
    public static function generateThumbnail(string $inputPath, string $outputPath, string $mimeType): void
    {
        $imagick = new Imagick();

        if ($mimeType === 'application/pdf') {
            $imagick->setResolution(150, 150);
            $imagick->readImage($inputPath . '[0]'); // first page only
        } else {
            $imagick->readImage($inputPath);
        }

        $imagick->thumbnailImage(200, 200, true);
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(85);

        // Flatten to remove alpha channel (PDFs may have transparency)
        $imagick->setImageBackgroundColor(new ImagickPixel('white'));
        $imagick = $imagick->flattenImages();

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $imagick->writeImage($outputPath);
        $imagick->clear();
        $imagick->destroy();
    }

    /**
     * Create a searchable PDF with invisible text layer using Tesseract PDF output + Ghostscript merge.
     */
    public static function createSearchablePdf(string $enhancedPdfPath, string $outputPath): void
    {
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($enhancedPdfPath);

        $pages = $imagick->getNumberImages();
        $tempFiles = [];
        $pagePdfs = [];

        try {
            for ($i = 0; $i < $pages; $i++) {
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');

                $tempPng = sys_get_temp_dir() . '/searchable_page_' . uniqid() . '.png';
                $tempFiles[] = $tempPng;
                $imagick->writeImage($tempPng);

                // Tesseract PDF output: generates {base}.pdf
                $tempBase = sys_get_temp_dir() . '/searchable_out_' . uniqid();
                $tempPdf = $tempBase . '.pdf';
                $tempFiles[] = $tempPdf;

                $cmd = sprintf(
                    'tesseract %s %s pdf 2>&1',
                    escapeshellarg($tempPng),
                    escapeshellarg($tempBase)
                );
                $output = shell_exec($cmd);

                if (!file_exists($tempPdf)) {
                    throw new \RuntimeException("Tesseract PDF output failed for page {$i}: {$output}");
                }

                $pagePdfs[] = $tempPdf;
            }

            $imagick->clear();
            $imagick->destroy();

            if (count($pagePdfs) === 1) {
                // Single page — just copy
                $dir = dirname($outputPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($pagePdfs[0], $outputPath);
            } else {
                // Merge with Ghostscript
                $gsPath = config('services.ghostscript.path', 'gs');
                $dir = dirname($outputPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $gsCmd = sprintf(
                    '%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s 2>&1',
                    escapeshellarg($gsPath),
                    escapeshellarg($outputPath),
                    implode(' ', array_map('escapeshellarg', $pagePdfs))
                );
                $gsOutput = shell_exec($gsCmd);

                if (!file_exists($outputPath)) {
                    throw new \RuntimeException("Ghostscript merge failed: {$gsOutput}");
                }
            }
        } finally {
            foreach ($tempFiles as $tmp) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Enhance a file based on its MIME type. Returns the enhanced file path.
     */
    public static function enhance(string $inputPath, string $outputPath, string $mimeType): void
    {
        if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
            self::enhanceImage($inputPath, $outputPath);
            return;
        }

        if ($mimeType === 'application/pdf') {
            self::enhancePdf($inputPath, $outputPath);
            return;
        }

        throw new \RuntimeException("Unsupported MIME type for enhancement: {$mimeType}");
    }
}
