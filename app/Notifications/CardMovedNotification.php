<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Card;
use App\Models\User;

class CardMovedNotification extends Notification
{
    use Queueable;

    protected Card $card;
    protected string $fromListName;
    protected string $toListName;
    protected User $mover;

    /**
     * Create a new notification instance.
     */
    public function __construct(Card $card, string $fromListName, string $toListName, User $mover)
    {
        $this->card = $card;
        $this->fromListName = $fromListName;
        $this->toListName = $toListName;
        $this->mover = $mover;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $cardUrl = route('boards.show', $this->card->list->board_id) . '?card=' . $this->card->id;

        return (new MailMessage)
                    ->subject('Card Moved: ' . $this->card->title)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line($this->mover->name . ' moved the card "' . $this->card->title . '"')
                    ->line('From: ' . $this->fromListName)
                    ->line('To: ' . $this->toListName)
                    ->action('View Card', $cardUrl);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $cardUrl = route('boards.show', $this->card->list->board_id) . '?card=' . $this->card->id;
        $message = $this->mover->name . ' moved "' . $this->card->title . '" to ' . $this->toListName;

        return [
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->list->board_id,
            'mover_id' => $this->mover->id,
            'mover_name' => $this->mover->name,
            'from_list' => $this->fromListName,
            'to_list' => $this->toListName,
            // フロントエンド表示用
            'message' => $message,
            'url' => $cardUrl,
        ];
    }
}