<?php

namespace App\Events;

use App\Models\Card;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardMoved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Card $card;
    public string $fromListName;
    public string $toListName;
    public User $mover;

    /**
     * Create a new event instance.
     */
    public function __construct(Card $card, string $fromListName, string $toListName, User $mover)
    {
        $this->card = $card;
        $this->fromListName = $fromListName;
        $this->toListName = $toListName;
        $this->mover = $mover;
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
