<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // この行を追加
use Illuminate\Notifications\DatabaseNotification; // この行をuse文に追加

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ユーザーの通知を最新10件取得
        $notifications = $user->notifications()->latest()->take(10)->get();

        // 未読の通知を既読にする
        // $user->unreadNotifications->markAsRead();

        return response()->json($notifications);
    }

    public function update(DatabaseNotification $notification)
    {
        // ポリシーなどで認可チェックを行うのが望ましいが、今回はシンプルに実装
        // if ($notification->notifiable_id !== Auth::id()) {
        //     abort(403);
        // }

        $notification->markAsRead();

        return response()->noContent();
    }
}
