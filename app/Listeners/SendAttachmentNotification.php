<?php

namespace App\Listeners;

use App\Events\AttachmentUploaded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\NewAttachmentNotification;
use Illuminate\Support\Facades\Notification;

class SendAttachmentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AttachmentUploaded $event): void
    {
        // 1. Attachment オブジェクトを取得
        $attachment = $event->attachment;

        // 2. 必要なリレーションを再ロード (シリアライズ対策 & 通知生成用)
        //    card.list.board は URL生成に必要
        //    card.assignedUsers は 通知先決定に必要
        $attachment->load('user', 'card.list.board', 'card.assignedUsers');

        $card = $attachment->card;
        $uploader = $attachment->user;

        // 3. 通知先を決定 (カードに割り当てられているユーザー)
        $recipients = $card->assignedUsers;

        // 4. フィルタリング
        //    - アップロードした本人を除外
        //    - 通知設定 (notify_on_attachment) が ON のユーザーのみ ★ここを変更
        $recipientsToNotify = $recipients->filter(function ($user) use ($uploader) {
            return $user->id !== $uploader->id && $user->notify_on_attachment;
        });

        // 5. 通知送信
        if ($recipientsToNotify->isNotEmpty()) {
            Notification::send($recipientsToNotify, new NewAttachmentNotification($attachment));
        }
    }
}