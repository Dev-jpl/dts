<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeFile extends Model
{
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';
    const STATUS_SKIPPED    = 'skipped';

    protected $table = 'office_files';

    protected $fillable = [
        'folder_id',
        'office_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'enhanced_path',
        'thumbnail_path',
        'enhancement_status',
        'enhancement_error',
        'searchable_pdf_path',
        'ocr_text',
        'ocr_status',
        'ocr_error',
        'embedding',
        'uploaded_by_id',
        'uploaded_by_name',
    ];

    protected $hidden = [
        'embedding',
    ];

    public function folder()
    {
        return $this->belongsTo(FileFolder::class, 'folder_id');
    }

    public function office()
    {
        return $this->belongsTo(OfficeLibrary::class, 'office_id', 'id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function scopeForOffice($query, string $officeId)
    {
        return $query->where('office_id', $officeId);
    }

    /**
     * Keyword-only search (full-text + filename).
     */
    public function scopeKeywordSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->whereRaw("to_tsvector('simple', COALESCE(ocr_text, '')) @@ plainto_tsquery('simple', ?)", [$keyword])
              ->orWhere('original_name', 'ilike', "%{$keyword}%");
        });
    }

    /**
     * Backward-compatible alias.
     */
    public function scopeSearch($query, string $keyword)
    {
        return $this->scopeKeywordSearch($query, $keyword);
    }

    /**
     * Semantic-only search using pgvector cosine distance.
     */
    public function scopeSemanticSearch($query, array $vector, float $threshold = 0.7)
    {
        $vectorStr = '[' . implode(',', $vector) . ']';

        return $query->whereNotNull('embedding')
            ->whereRaw("embedding <=> ?::vector < ?", [$vectorStr, $threshold])
            ->orderByRaw("embedding <=> ?::vector", [$vectorStr]);
    }

    /**
     * Hybrid search: keyword matches first (ranked best), then semantic matches.
     * Uses a UNION approach: keyword hits get distance=0, semantic-only get actual distance.
     */
    public function scopeHybridSearch($query, string $keyword, array $vector, float $threshold = 0.7)
    {
        $vectorStr = '[' . implode(',', $vector) . ']';

        return $query->where(function ($q) use ($keyword, $vectorStr, $threshold) {
            // Keyword match (full-text OR filename)
            $q->where(function ($kw) use ($keyword) {
                $kw->whereRaw("to_tsvector('simple', COALESCE(ocr_text, '')) @@ plainto_tsquery('simple', ?)", [$keyword])
                   ->orWhere('original_name', 'ilike', "%{$keyword}%");
            })
            // OR semantic match
            ->orWhere(function ($sem) use ($vectorStr, $threshold) {
                $sem->whereNotNull('embedding')
                    ->whereRaw("embedding <=> ?::vector < ?", [$vectorStr, $threshold]);
            });
        })
        ->selectRaw("*, CASE
            WHEN to_tsvector('simple', COALESCE(ocr_text, '')) @@ plainto_tsquery('simple', ?) OR original_name ILIKE ?
            THEN 0
            ELSE (embedding <=> ?::vector)
        END as search_rank", [$keyword, "%{$keyword}%", $vectorStr])
        ->orderBy('search_rank', 'asc');
    }

    /**
     * Returns the best available file path for preview (enhanced if available, else original).
     */
    public function getPreviewPath(): string
    {
        return $this->enhanced_path ?? $this->file_path;
    }
}
