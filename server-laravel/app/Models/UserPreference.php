<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'date_format',
        'timezone',
        'default_period',
        'dashboard_realtime',
    ];

    protected $casts = [
        'dashboard_realtime' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
