<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
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

    protected $primaryKey = 'document_no';
    public $incrementing = false;
    protected $keyType = 'string';

    // One-to-One
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    // One-to-Many
    public function transactions()
    {
        return $this->hasMany(DocumentTransaction::class, 'document_no', 'document_no');
    }

    public function recipients()
    {
        return $this->hasMany(DocumentRecipient::class, 'document_no', 'document_no');
    }

    public function signatories()
    {
        return $this->hasMany(DocumentSignatory::class, 'document_no', 'document_no');
    }

    public function attachments()
    {
        return $this->hasMany(DocumentAttachment::class, 'document_no', 'document_no');
    }

    public function logs()
    {
        return $this->hasMany(DocumentLog::class, 'document_no', 'document_no');
    }

    public function comments()
    {
        return $this->hasMany(DocumentComment::class, 'document_no', 'document_no');
    }

    // Many-to-Many
    public function offices()
    {
        return $this->belongsToMany(OfficeLibrary::class, 'document_recipients', 'document_no', 'office_id');
    }
}
