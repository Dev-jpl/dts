<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'in_app',
        'email',
    ];

    protected $casts = [
        'in_app' => 'boolean',
        'email' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get all notification event types
     */
    public static function eventTypes(): array
    {
        return [
            'document_released' => 'Document Released',
            'document_received' => 'Document Received',
            'document_returned' => 'Document Returned',
            'document_done' => 'Document Marked as Done',
            'document_forwarded' => 'Document Forwarded',
            'routing_halted' => 'Routing Halted',
            'overdue' => 'Document Overdue',
            'note_added' => 'Official Note Added',
        ];
    }
}
