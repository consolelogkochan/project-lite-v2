<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Attachment;
use App\Models\Card;
use App\Models\User;

class NewAttachmentNotification extends Notification
{
    use Queueable;

    protected Attachment $attachment;
    protected Card $card;
    protected User $uploader;

    /**
     * Create a new notification instance.
     */
    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
        $this->card = $attachment->card;
        $this->uploader = $attachment->user;
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
                    ->subject('New Attachment on card: ' . $this->card->title)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line($this->uploader->name . ' uploaded a file to your card:')
                    ->line('File: ' . $this->attachment->file_name)
                    ->action('View Card', $cardUrl);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $cardUrl = route('boards.show', $this->card->list->board_id) . '?card=' . $this->card->id;
        $message = $this->uploader->name . ' uploaded "' . $this->attachment->file_name . '" to ' . $this->card->title;

        return [
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->list->board_id,
            'uploader_id' => $this->uploader->id,
            'uploader_name' => $this->uploader->name,
            'attachment_id' => $this->attachment->id,
            'file_name' => $this->attachment->file_name,
            // フロントエンド表示用
            'message' => $message,
            'url' => $cardUrl,
        ];
    }
}