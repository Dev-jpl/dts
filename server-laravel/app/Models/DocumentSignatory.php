<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSignatory extends Model
{
    protected $fillable = [
        'id',
        'document_no',
        'transaction_no',
        'office_id',
        'office_name',
        'employee_id',
        'employee_name'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_no', 'document_no');
    }

    public function transaction()
    {
        return $this->belongsTo(DocumentTransaction::class, 'transaction_no', 'transaction_no');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }
}
