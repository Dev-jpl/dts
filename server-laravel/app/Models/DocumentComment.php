<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentComment extends Model
{
    protected $fillable = [
        'document_no',
        'transaction_no',
        'office_id',
        'office_name',
        'comment',
        'assigned_personnel_id',
        'assigned_personnel_name'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_no', 'document_no');
    }

    public function transaction()
    {
        return $this->belongsTo(DocumentTransaction::class, 'transaction_no', 'transaction_no');
    }

    public function personnel()
    {
        return $this->belongsTo(User::class, 'assigned_personnel_id', 'id');
    }
}
