<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTransaction extends Model
{
    protected $fillable = [
        'transaction_no',
        'transaction_type',
        'parent_transaction_no',
        'routing',
        'document_no',
        'document_type',
        'action_type',
        'origin_type',
        'subject',
        'remarks',
        'status',
        'office_id',
        'office_name',
        'created_by_id',
        'created_by_name',
        'isActive'
    ];

    protected $primaryKey = 'transaction_no';
    public $incrementing = false;
    protected $keyType = 'string';

    // One-to-One
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_no', 'document_no');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    // One-to-Many
    public function recipients()
    {
        return $this->hasMany(DocumentRecipient::class, 'transaction_no', 'transaction_no');
    }

    public function signatories()
    {
        return $this->hasMany(DocumentSignatory::class, 'transaction_no', 'transaction_no');
    }

    public function attachments()
    {
        return $this->hasMany(DocumentAttachment::class, 'transaction_no', 'transaction_no');
    }

    public function logs()
    {
        return $this->hasMany(DocumentTransactionLog::class, 'transaction_no', 'transaction_no');
    }

    public function comments()
    {
        return $this->hasMany(DocumentComment::class, 'transaction_no', 'transaction_no');
    }
}
