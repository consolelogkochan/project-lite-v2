<?php

namespace App\Listeners;

use App\Events\CardDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CardDeletedNotification;
use App\Models\Board;

class SendCardDeletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(CardDeleted $event): void
    {
        // ボードとメンバーを取得
        $board = Board::find($event->boardId);
        
        if (!$board) return; // ボード自体が消えていたら何もしない

        $recipients = $board->users;
        $deleter = $event->deleter;

        // フィルタリング
        // 1. 削除者本人は除外
        // 2. 設定がONのユーザーのみ
        $recipientsToNotify = $recipients->filter(function ($user) use ($deleter) {
            return $user->id !== $deleter->id && $user->notify_on_card_deleted;
        });

        if ($recipientsToNotify->isNotEmpty()) {
            Notification::send($recipientsToNotify, new CardDeletedNotification(
                $event->cardTitle,
                $event->listName,
                $event->boardId,
                $deleter
            ));
        }
    }
}
