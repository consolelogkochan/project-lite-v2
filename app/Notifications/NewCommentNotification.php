<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Comment; // ★ 1. Comment モデルをインポート
use App\Models\Card;    // ★ 1. Card モデルをインポート
use App\Models\User;    // ★ 1. User モデルをインポート

class NewCommentNotification extends Notification
{
    use Queueable;

    protected Comment $comment;
    protected Card $card;
    protected User $commenter;

    /**
     * Create a new notification instance.
     * ★ 2. コンストラクタを修正
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
        $this->card = $comment->card;
        $this->commenter = $comment->user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // ★ 3. 'mail' (メール) と 'database' (DB) の両方で通知
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     * ★ 4. メール通知の内容を定義
     */
    public function toMail(object $notifiable): MailMessage
    {
        $cardUrl = route('boards.show', $this->card->list->board_id) . '?card=' . $this->card->id;

        return (new MailMessage)
                    ->subject('New Comment on card: ' . $this->card->title)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line($this->commenter->name . ' commented on your card:')
                    ->line('"' . $this->comment->content . '"')
                    ->action('View Card', $cardUrl)
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     * (データベースに保存される 'data' カラムの中身)
     * ★ 5. データベース通知の内容を定義
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // ★ 1. URLを生成
        $cardUrl = route('boards.show', $this->card->list->board_id) . '?card=' . $this->card->id;
        
        // ★ 2. メッセージを生成
        $message = $this->commenter->name . ' commented on ' . $this->card->title;
        
        return [
            // (既存のデータ)
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->list->board_id,
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name,
            'comment_content' => $this->comment->content,
            'comment_id' => $this->comment->id,
            
            // ★ 3. JS (renderNotifications) が必要とするデータを追加
            'message' => $message,
            'url' => $cardUrl,
        ];
    }
}
