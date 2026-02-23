<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentActivityLoggedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

    public string $documentNo;
    public string $transactionNo;
    public string $status;
    public string $actionTaken;
    public ?string $remarks;
    public object $user;
    public array $office;
    public ?array $routed_office;
    public string $routing_type;
    public $activity;


    public function __construct(array $payload)
    {
        $this->documentNo = $payload['document_no'];
        $this->transactionNo = $payload['transaction_no'];
        $this->status = $payload['status'];
        $this->actionTaken = $payload['action_taken'];
        $this->activity = $payload['activity'] ?? null;
        $this->remarks = $payload['remarks'] ?? null;
        $this->user = $payload['user'];
        $this->office = $payload['office'];
        $this->routing_type = $payload['routing_type'] ?? "Single";
        $this->routed_office = $payload['routed_office'] ?? null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
