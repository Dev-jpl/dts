<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Comment\Doc;

class DocumentRecipient extends Model
{
    protected $fillable = [
        'document_no',
        'transaction_no',
        'recipient_type',
        'sequence',
        'office_id',
        'office_name',
        'created_by_id',
        'created_by_name',
        'isActive'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_no', 'document_no');
    }

    public function transaction()
    {
        return $this->belongsTo(DocumentTransaction::class, 'transaction_no', 'transaction_no');
    }

    public function office()
    {
        return $this->belongsTo(OfficeLibrary::class, 'office_id', 'id');
    }
}
