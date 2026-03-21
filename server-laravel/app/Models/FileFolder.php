<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileFolder extends Model
{
    protected $fillable = [
        'office_id',
        'name',
        'description',
        'parent_id',
        'created_by_id',
        'created_by_name',
    ];

    public function parent()
    {
        return $this->belongsTo(FileFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FileFolder::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(OfficeFile::class, 'folder_id');
    }

    public function office()
    {
        return $this->belongsTo(OfficeLibrary::class, 'office_id', 'id');
    }

    public function scopeForOffice($query, string $officeId)
    {
        return $query->where('office_id', $officeId);
    }
}
