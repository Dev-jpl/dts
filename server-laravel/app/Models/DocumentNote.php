<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentNote extends Model
{
    protected $table = 'document_notes';

    protected $fillable = [
        'document_no',
        'transaction_no',
        'note',
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

    public function office()
    {
        return $this->belongsTo(OfficeLibrary::class, 'office_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }
}
