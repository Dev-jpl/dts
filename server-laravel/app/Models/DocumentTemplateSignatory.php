<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateSignatory extends Model
{
    protected $fillable = [
        'template_id',
        'signatory_id',
        'name',
        'position',
        'office',
        'role',
        'sequence',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }
}
