<?php

namespace App\Console\Commands;

use App\Models\OfficeFile;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class BackfillEmbeddings extends Command
{
    protected $signature = 'files:backfill-embeddings {--batch=50 : Batch size for processing}';
    protected $description = 'Generate embeddings for existing files that have OCR text but no embedding vector';

    public function handle(): int
    {
        if (!EmbeddingService::isAvailable()) {
            $this->error('Embedding service is not available. Start it first:');
            $this->line('  cd services/embedding && uvicorn app:app --port 5100');
            return self::FAILURE;
        }

        $batchSize = (int) $this->option('batch');

        $total = OfficeFile::whereNotNull('ocr_text')
            ->where('ocr_text', '!=', '')
            ->whereNull('embedding')
            ->count();

        if ($total === 0) {
            $this->info('No files need embedding backfill.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} files to process.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $failed = 0;

        OfficeFile::whereNotNull('ocr_text')
            ->where('ocr_text', '!=', '')
            ->whereNull('embedding')
            ->orderBy('id')
            ->chunk($batchSize, function ($files) use (&$processed, &$failed, $bar) {
                $texts = $files->pluck('ocr_text')->toArray();
                $vectors = EmbeddingService::embedBatch($texts);

                foreach ($files as $i => $file) {
                    if (!empty($vectors[$i])) {
                        $vectorStr = '[' . implode(',', $vectors[$i]) . ']';
                        $file->update(['embedding' => $vectorStr]);
                        $processed++;
                    } else {
                        $failed++;
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Processed: {$processed}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
