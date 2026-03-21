<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'scope',
        'document_type',
        'action_type',
        'routing_type',
        'urgency_level',
        'origin_type',
        'sender',
        'sender_position',
        'sender_office',
        'sender_email',
        'remarks_template',
        'created_by_id',
        'created_by_name',
        'office_id',
        'isActive',
        'use_count',
        'last_used_at',
    ];

    protected $casts = [
        'isActive'     => 'boolean',
        'use_count'    => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(DocumentTemplateRecipient::class, 'template_id')
            ->orderBy('sequence');
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(DocumentTemplateSignatory::class, 'template_id')
            ->orderBy('sequence');
    }

    /**
     * Scope: templates visible to the given user.
     * - personal: created_by_id = user
     * - office:   office_id = user's office
     * - system:   all users
     */
    public function scopeVisible(Builder $query, $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where(function (Builder $inner) use ($user) {
                $inner->where('scope', 'personal')
                      ->where('created_by_id', $user->id);
            })->orWhere(function (Builder $inner) use ($user) {
                $inner->where('scope', 'office')
                      ->where('office_id', $user->office_id);
            })->orWhere('scope', 'system');
        });
    }
}
