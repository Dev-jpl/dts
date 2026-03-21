<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateRecipient extends Model
{
    protected $fillable = [
        'template_id',
        'office_id',
        'office_name',
        'recipient_type',
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
