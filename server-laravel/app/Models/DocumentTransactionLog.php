<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTransactionLog extends Model
{
    use HasFactory;

    protected $table = 'document_transaction_logs';

    protected $fillable = [
        'document_no',
        'transaction_no',
        'office_id',
        'office_name',
        'routed_office_id',
        'routed_office_name',
        'status',
        'action_taken',
        'activity',
        'remarks',
        'assigned_personnel_id',
        'assigned_personnel_name',
    ];

    // Relationships
    public function office()
    {
        return $this->belongsTo(OfficeLibrary::class, 'office_id');
    }

    public function routedOffice()
    {
        return $this->belongsTo(OfficeLibrary::class, 'routed_office_id');
    }

    public function personnel()
    {
        return $this->belongsTo(User::class, 'assigned_personnel_id', 'id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_no', 'document_no');
    }

    public function transaction()
    {
        return $this->belongsTo(DocumentTransaction::class, 'transaction_no', 'transaction_no');
    }
}
