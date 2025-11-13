<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Card;

class CardDueNotification extends Notification
{
    use Queueable;

    protected Card $card;

    /**
     * Create a new notification instance.
     */
    public function __construct(Card $card)
    {
        $this->card = $card;
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
                    ->subject('Card Due Reminder: ' . $this->card->title)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('This is a reminder for the card: "' . $this->card->title . '"')
                    ->line('Due Date: ' . $this->card->end_date->format('Y-m-d H:i'))
                    ->action('View Card', $cardUrl);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $cardUrl = route('boards.show', $this->card->list->board_id) . '?card=' . $this->card->id;
        $message = 'Reminder: "' . $this->card->title . '" is due soon.';

        return [
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->list->board_id,
            'message' => $message,
            'url' => $cardUrl,
        ];
    }
}
