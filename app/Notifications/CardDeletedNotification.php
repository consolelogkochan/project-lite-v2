<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class CardDeletedNotification extends Notification
{
    use Queueable;

    protected string $cardTitle;
    protected string $listName;
    protected int $boardId;
    protected User $deleter;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $cardTitle, string $listName, int $boardId, User $deleter)
    {
        $this->cardTitle = $cardTitle;
        $this->listName = $listName;
        $this->boardId = $boardId;
        $this->deleter = $deleter;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // カードは削除されているので、ボードへのリンクにする
        $boardUrl = route('boards.show', $this->boardId);

        return (new MailMessage)
                    ->subject('Card Deleted: ' . $this->cardTitle)
                    ->line($this->deleter->name . ' deleted the card "' . $this->cardTitle . '" from list "' . $this->listName . '".')
                    ->action('View Board', $boardUrl);
    }

    public function toArray(object $notifiable): array
    {
        $boardUrl = route('boards.show', $this->boardId);
        $message = $this->deleter->name . ' deleted card "' . $this->cardTitle . '"';

        return [
            // カードIDはもう無効だが、記録として0または元のIDを渡すことも可能
            'card_id' => null, 
            'card_title' => $this->cardTitle,
            'board_id' => $this->boardId,
            'deleter_id' => $this->deleter->id,
            'deleter_name' => $this->deleter->name,
            'message' => $message,
            'url' => $boardUrl,
        ];
    }
}
