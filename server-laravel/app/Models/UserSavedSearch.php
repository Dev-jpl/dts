<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSavedSearch extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'filters_json',
        'sort_by',
    ];

    protected $casts = [
        'filters_json' => 'array',
    ];

    /**
     * Get the user that owns this saved search.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
