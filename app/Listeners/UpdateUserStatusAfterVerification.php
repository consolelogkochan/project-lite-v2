<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified; // Verifiedイベントを受け取る
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateUserStatusAfterVerification
{
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
    public function handle(Verified $event): void
    {
        // $event->user で認証されたユーザーを取得できる
        $user = $event->user;
        
        // ユーザーのステータスを'active'に更新して保存
        if ($user && $user->status === 'inactive') {
            $user->status = 'active';
            $user->save();
        }
    }
}
