<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Card;
use App\Models\User;

class CardCreatedNotification extends Notification
{
    use Queueable;

    protected Card $card;
    protected User $creator;

    public function __construct(Card $card, User $creator)
    {
        $this->card = $card;
        $this->creator = $creator;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cardUrl = route('boards.show', $this->card->list->board_id) . '?card=' . $this->card->id;

        return (new MailMessage)
                    ->subject('New Card Created: ' . $this->card->title)
                    ->line($this->creator->name . ' created a new card in list "' . $this->card->list->title . '":')
                    ->line('"' . $this->card->title . '"')
                    ->action('View Card', $cardUrl);
    }

    public function toArray(object $notifiable): array
    {
        $cardUrl = route('boards.show', $this->card->list->board_id) . '?card=' . $this->card->id;
        $message = $this->creator->name . ' created card "' . $this->card->title . '" in ' . $this->card->list->title;

        return [
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->list->board_id,
            'creator_id' => $this->creator->id,
            'creator_name' => $this->creator->name,
            'message' => $message,
            'url' => $cardUrl,
        ];
    }
}
