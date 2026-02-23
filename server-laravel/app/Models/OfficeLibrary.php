<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeLibrary extends Model
{
    protected $fillable = [
        'id',
        'office_name',
        'region_code',
        'region_name',
        'province_code',
        'province_name',
        'municipality_code',
        'municipality_name',
        'office_type',
        'superior_office_id',
        'superior_office_name',
        'isActive'
    ];

    protected $primaryKey = 'document_no';
    public $incrementing = false;
    protected $keyType = 'string';

    // One-to-Many
    public function users()
    {
        return $this->hasMany(User::class, 'office_id', 'id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'office_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(DocumentTransaction::class, 'office_id', 'id');
    }

    // Self-Referencing One-to-One
    public function superiorOffice()
    {
        return $this->belongsTo(OfficeLibrary::class, 'superior_office_id', 'id');
    }

    public function subOffices()
    {
        return $this->hasMany(OfficeLibrary::class, 'superior_office_id', 'id');
    }

    // Many-to-Many
    public function receivedDocuments()
    {
        return $this->belongsToMany(Document::class, 'document_recipients', 'office_id', 'document_no');
    }
}
