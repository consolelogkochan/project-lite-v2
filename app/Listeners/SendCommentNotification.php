<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User; // ★ 1. インポート
use App\Notifications\NewCommentNotification; // ★ 1. インポート
use Illuminate\Support\Facades\Notification; // ★ 1. インポート

// ★ 2. ShouldQueue を実装 (通知送信をバックグラウンドで行うため)
class SendCommentNotification implements ShouldQueue
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
     * ★ 3. handle メソッドを修正
     */
    public function handle(CommentPosted $event): void
    {
        // ★ 1. $event->comment はシリアライズ解除され、関連データが失われている
        $comment = $event->comment;

        // ★ 2. データベースから必要なデータを「再ロード」する
        $comment->load('user', 'card.list.board', 'card.assignedUsers');

        $card = $comment->card;
        $commenter = $comment->user;

        // ★ 3. 通知を送信する相手（$recipients）を決定する
        $recipients = $card->assignedUsers;

        // ★ 4. 「コメントした本人」を除外 & 「通知設定(notify_on_comment)がONの人」のみ抽出
        $recipientsToNotify = $recipients->filter(function ($user) use ($commenter) {
            return $user->id !== $commenter->id && $user->notify_on_comment;
        });

        // ★ 5. 実際の通知を送信
        if ($recipientsToNotify->isNotEmpty()) {
            Notification::send($recipientsToNotify, new NewCommentNotification($comment));
        }
    }
}
