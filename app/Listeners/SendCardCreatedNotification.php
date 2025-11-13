<?php

namespace App\Listeners;

use App\Events\CardCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CardCreatedNotification;

class SendCardCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(CardCreated $event): void
    {
        $card = $event->card;
        $creator = $event->creator;

        // 必要なリレーションをロード
        $card->load('list.board.users');

        // ボードの全メンバーを取得
        $recipients = $card->list->board->users;

        // フィルタリング
        // 1. 作成者本人は除外
        // 2. 設定がONのユーザーのみ
        $recipientsToNotify = $recipients->filter(function ($user) use ($creator) {
            return $user->id !== $creator->id && $user->notify_on_card_created;
        });

        if ($recipientsToNotify->isNotEmpty()) {
            Notification::send($recipientsToNotify, new CardCreatedNotification($card, $creator));
        }
    }
}
