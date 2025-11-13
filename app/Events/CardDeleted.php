<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $cardTitle;
    public string $listName;
    public int $boardId;
    public User $deleter;

    public function __construct(string $cardTitle, string $listName, int $boardId, User $deleter)
    {
        $this->cardTitle = $cardTitle;
        $this->listName = $listName;
        $this->boardId = $boardId;
        $this->deleter = $deleter;
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
