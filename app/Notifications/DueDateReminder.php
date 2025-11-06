<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Card; // ★ 1. Cardモデルをインポート

class DueDateReminder extends Notification
{
    use Queueable;

    public $card; // ★ 2. Cardオブジェクトを保持する変数

    /**
     * Create a new notification instance.
     */
    public function __construct(Card $card) // ★ 3. コンストラクタでCardを受け取る
    {
        $this->card = $card;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // 'mail' (メール) と 'database' (DB＝ベル通知) の両方で送る
        // (メールが不要なら 'mail' を削除)
        return ['mail', 'database']; 
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // ★ 4. メール通知の内容
        $board = $this->card->list->board;
        $url = route('boards.show', $board);

        return (new MailMessage)
                    ->subject('Task Reminder: ' . $this->card->title)
                    ->line('This is a reminder that the card "' . $this->card->title . '" is due soon.')
                    ->line('Board: ' . $board->title)
                    ->action('View Card', $url) // (本当はカードモーダルを直接開きたいが、まずはボードへ)
                    ->line('Thank you for using Project-Lite!');
    }

    /**
     * Get the array representation of the notification.
     * (database チャンネル＝ベル通知用)
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // ★ 5. データベース通知の内容
        // この配列が notifications テーブルの 'data' カラムにJSONで保存される
        return [
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->list->board->id,
            'message' => 'Reminder: The card "' . $this->card->title . '" is due soon.',
        ];
    }
}