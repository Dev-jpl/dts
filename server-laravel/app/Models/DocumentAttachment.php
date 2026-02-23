<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAttachment extends Model
{
    protected $fillable = [
        'document_no',
        'transaction_no',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'attachment_type',
        'office_id',
        'office_name',
        'created_by_id',
        'created_by_name',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_no', 'document_no');
    }

    public function transaction()
    {
        return $this->belongsTo(DocumentTransaction::class, 'transaction_no', 'transaction_no');
    }
}
