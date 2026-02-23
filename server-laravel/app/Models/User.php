<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['id', 'email', 'first_name', 'middle_name', 'last_name', 'office_id', 'office_name', 'isActive', 'password'];

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // One-to-One
    public function office()
    {
        return $this->belongsTo(OfficeLibrary::class, 'office_id', 'id');
    }


    // One-to-Many
    public function documents()
    {
        return $this->hasMany(Document::class, 'created_by_id', 'id');
    }
    public function transactions()
    {
        return $this->hasMany(DocumentTransaction::class, 'created_by_id', 'id');
    }
    public function logs()
    {
        return $this->hasMany(DocumentLog::class, 'assigned_personnel_id', 'id');
    }
    public function comments()
    {
        return $this->hasMany(DocumentComment::class, 'assigned_personnel_id', 'id');
    }

    // Many-to-Many
    public function signatories()
    {
        return $this->belongsToMany(Document::class, 'document_signatories', 'employee_id', 'document_no');
    }


    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
