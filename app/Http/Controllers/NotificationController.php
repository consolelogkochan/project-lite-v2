<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // この行を追加
use Illuminate\Notifications\DatabaseNotification; // この行をuse文に追加

class NotificationController extends Controller
{
    /**
     * 未読通知の件数を取得する (API)
     */
    public function getUnreadCount(Request $request)
    {
        $user = Auth::user();
        
        return response()->json([
            'count' => $user->unreadNotifications()->count()
        ]);
    }
    

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

    /**
     * 通知設定を更新する (API)
     * ★ このメソッドを追加
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        // バリデーション
        $validated = $request->validate([
            'notify_on_comment' => 'required|boolean',
            'notify_on_attachment' => 'required|boolean',
            'notify_on_due_date' => 'required|boolean',
            'notify_on_card_move' => 'required|boolean',
            'notify_on_card_created' => 'required|boolean',
            'notify_on_card_deleted' => 'required|boolean',
        ]);

        // 設定を更新
        $user->update($validated);

        return response()->json(['message' => 'Preferences updated successfully.']);
    }

    /**
     * 指定された通知を削除する (API)
     */
    public function destroy(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->noContent();
    }

    /**
     * 既読の通知をすべて削除する (API)
     */
    public function clearRead()
    {
        Auth::user()->readNotifications()->delete();

        return response()->noContent();
    }
}
