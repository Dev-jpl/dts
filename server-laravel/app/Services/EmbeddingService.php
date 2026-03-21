<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    /**
     * Get embedding vector for text from the local Python service.
     * Returns null if service unavailable or text empty.
     */
    public static function embed(string $text): ?array
    {
        $text = trim($text);
        if (empty($text)) {
            return null;
        }

        try {
            $url = config('services.embedding.url', 'http://localhost:5100');
            $response = Http::timeout(30)->post("{$url}/embed", [
                'text' => $text,
            ]);

            if ($response->successful()) {
                $vector = $response->json('vector');
                if (is_array($vector) && count($vector) > 0) {
                    return $vector;
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::debug("EmbeddingService::embed failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Batch embed multiple texts. Returns array of vectors (or nulls).
     */
    public static function embedBatch(array $texts): array
    {
        $texts = array_map('trim', $texts);
        $nonEmpty = array_filter($texts, fn($t) => !empty($t));

        if (empty($nonEmpty)) {
            return array_fill(0, count($texts), null);
        }

        try {
            $url = config('services.embedding.url', 'http://localhost:5100');
            $response = Http::timeout(60)->post("{$url}/embed", [
                'texts' => array_values($nonEmpty),
            ]);

            if (!$response->successful()) {
                return array_fill(0, count($texts), null);
            }

            $vectors = $response->json('vectors');
            if (!is_array($vectors)) {
                return array_fill(0, count($texts), null);
            }

            // Map vectors back to original positions
            $result = [];
            $vectorIndex = 0;
            foreach ($texts as $text) {
                if (!empty(trim($text))) {
                    $result[] = $vectors[$vectorIndex] ?? null;
                    $vectorIndex++;
                } else {
                    $result[] = null;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            Log::debug("EmbeddingService::embedBatch failed: " . $e->getMessage());
            return array_fill(0, count($texts), null);
        }
    }

    /**
     * Check if embedding service is available.
     */
    public static function isAvailable(): bool
    {
        try {
            $url = config('services.embedding.url', 'http://localhost:5100');
            $response = Http::timeout(5)->get("{$url}/health");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
