<?php

namespace App\Listeners;

use App\Events\CardMoved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\CardMovedNotification;
use Illuminate\Support\Facades\Notification;

class SendCardMovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(CardMoved $event): void
    {
        $card = $event->card;
        // リレーションを再ロード
        $card->load('assignedUsers', 'list.board');

        $recipients = $card->assignedUsers;
        $mover = $event->mover;

        // フィルタリング
        // 1. 移動させた本人は除外
        // 2. 'notify_on_card_move' 設定がONのユーザーのみ
        $recipientsToNotify = $recipients->filter(function ($user) use ($mover) {
            return $user->id !== $mover->id && $user->notify_on_card_move;
        });

        if ($recipientsToNotify->isNotEmpty()) {
            Notification::send($recipientsToNotify, new CardMovedNotification(
                $card,
                $event->fromListName,
                $event->toListName,
                $mover
            ));
        }
    }
}
