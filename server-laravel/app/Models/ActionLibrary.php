<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionLibrary extends Model
{
    protected $table = 'action_library';

    protected $fillable = [
        'name',
        'type',
        'reply_is_terminal',
        'requires_proof',
        'proof_description',
        'default_urgency_level',
        'isActive',
    ];

    protected $casts = [
        'reply_is_terminal' => 'boolean',
        'requires_proof'    => 'boolean',
        'isActive'          => 'boolean',
    ];

    public function isFI(): bool
    {
        return $this->type === 'FI';
    }

    public function isFA(): bool
    {
        return $this->type === 'FA';
    }
}
