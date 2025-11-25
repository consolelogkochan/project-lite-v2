<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TestNotification;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BoardInvitationController;
use App\Http\Controllers\CardAssignmentController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// === ログイン・認証済みユーザー用のルート ===
Route::middleware('auth')->group(function () {
    
    // プロフィール
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ボード
    Route::post('/boards', [BoardController::class, 'store'])->name('boards.store');
    Route::get('/boards/{board}', [BoardController::class, 'show'])->name('boards.show');
    // ★ ここから追加: ボードの削除
    Route::delete('/boards/{board}', [BoardController::class, 'destroy'])->name('boards.destroy');

    // リスト
    Route::post('/boards/{board}/lists', [ListController::class, 'store'])->name('lists.store');

    // ★ ここから追加: カレンダー用のイベント（カード）取得API
    Route::get('/boards/{board}/calendar-events', [BoardController::class, 'getCalendarEvents'])->name('boards.calendarEvents');

    
    
    // ▼▼▼ "update-order"をワイルドカードより「上」に移動 ▼▼▼
    Route::patch('/lists/update-order', [ListController::class, 'updateOrder'])->name('lists.updateOrder');
    Route::patch('/lists/{list}', [ListController::class, 'update'])->name('lists.update');
    Route::delete('/lists/{list}', [ListController::class, 'destroy'])->name('lists.destroy');

    // 特定のリストにカードを追加する
    Route::post('/lists/{list}/cards', [CardController::class, 'store'])->name('cards.store');
    
    // カードのD&D（順序・所属リスト）を更新
    // (ワイルドカード {card} よりも「上」に定義する)
    Route::patch('/cards/update-order', [CardController::class, 'updateOrder'])->name('cards.updateOrder');

    // カードの更新と削除 (ワイルドカード)
    Route::patch('/cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    
    // ★ ここから追加: カード詳細（リレーション含む）を取得
    Route::get('/cards/{card}', [CardController::class, 'show'])->name('cards.show');

    // ★ ここから追加: カードにコメントを投稿
    Route::post('/cards/{card}/comments', [CommentController::class, 'store'])->name('comments.store');
    // ★ ここから追加: コメントの更新と削除
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // ★ ここから追加: ラベル管理 (ボードに紐づく)
    // ボードの全ラベルを取得
    Route::get('/boards/{board}/labels', [LabelController::class, 'index'])->name('labels.index');
    // 新しいラベルを作成
    Route::post('/boards/{board}/labels', [LabelController::class, 'store'])->name('labels.store');
    
    // ★ ここから追加: ラベルの更新と削除
    Route::patch('/labels/{label}', [LabelController::class, 'update'])->name('labels.update');
    Route::delete('/labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');

    // ★ ここから追加: カードへのラベル割り当て・解除
    Route::post('/cards/{card}/labels/{label}', [LabelController::class, 'attachLabel'])->name('labels.attach');
    Route::delete('/cards/{card}/labels/{label}', [LabelController::class, 'detachLabel'])->name('labels.detach');

    // ★ ここから追加: チェックリストの作成
    Route::post('/cards/{card}/checklists', [ChecklistController::class, 'store'])->name('checklists.store');
    // ★ ここから追加: チェックリスト自体の更新と削除
    Route::patch('/checklists/{checklist}', [ChecklistController::class, 'update'])->name('checklists.update');
    Route::delete('/checklists/{checklist}', [ChecklistController::class, 'destroy'])->name('checklists.destroy');

    // ★ ここから追加: チェックリストの「項目」のCRUD
    // 項目（サブタスク）の追加
    Route::post('/checklists/{checklist}/items', [ChecklistItemController::class, 'store'])->name('checklist_items.store');
    // ★ 修正: D&D(update-order)を、ワイルドカード({item})よりも「上」に定義する
    Route::patch('/checklist-items/update-order', [ChecklistItemController::class, 'updateOrder'])->name('checklist_items.updateOrder');
    // ★ ここから追加: 項目の更新と削除
    // (ChecklistItem だけで一意に特定できるため、ネスト不要)
    Route::patch('/checklist-items/{item}', [ChecklistItemController::class, 'update'])->name('checklist_items.update');
    Route::delete('/checklist-items/{item}', [ChecklistItemController::class, 'destroy'])->name('checklist_items.destroy');

    // ★ ここから追加: 添付ファイルのアップロード
    Route::post('/cards/{card}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    // ★ ここから追加: 添付ファイルのレビュー状況を更新
    Route::patch('/attachments/{attachment}/review', [AttachmentController::class, 'updateReviewStatus'])->name('attachments.updateReviewStatus');
    // ★ ここから追加: 添付ファイルの削除
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // ★ ここから追加: ボードメンバー招待
    // ユーザーをEmail/名前で検索
    Route::get('/boards/{board}/search-users', [BoardInvitationController::class, 'searchUsers'])->name('boards.searchUsers');
    // ★ ここから追加: ユーザーをボードに招待する
    Route::post('/boards/{board}/invite-user', [BoardInvitationController::class, 'inviteUser'])->name('boards.inviteUser');
    // ★ ここから追加: ボードの現在のメンバー一覧を取得
    Route::get('/boards/{board}/members', [BoardInvitationController::class, 'getMembers'])->name('boards.getMembers');
    // ★ ここから追加: メンバーの役割を更新
    // {user} は更新対象のユーザーID
    Route::patch('/boards/{board}/members/{user}', [BoardInvitationController::class, 'updateRole'])->name('boards.updateRole');
    // ★ ここから追加: メンバーをボードから退出させる
    Route::delete('/boards/{board}/members/{user}', [BoardInvitationController::class, 'removeMember'])->name('boards.removeMember');
    // ★ ここから追加: 自身がボードから退出する
    Route::delete('/boards/{board}/leave', [BoardInvitationController::class, 'leaveBoard'])->name('boards.leave');

    // ★ ここから追加: カードへのメンバー割り当て
    Route::post('/cards/{card}/assign-user/{user}', [CardAssignmentController::class, 'assignUser'])->name('cards.assignUser');
    Route::delete('/cards/{card}/assign-user/{user}', [CardAssignmentController::class, 'unassignUser'])->name('cards.unassignUser');

    // ★ 2. ここから追加: 通知関連
    // 未読通知の件数を取得
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unreadCount');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    // ★ ここから追加: 通知設定の更新「{notification}」ワイルドカードよりも「上」に定義
    Route::patch('/notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.updatePreferences');
    // ★ 1. 既読通知の一括削除 (ID指定より上に書く)
    Route::delete('/notifications/clear-read', [NotificationController::class, 'clearRead'])->name('notifications.clearRead');
    // ★ 2. 個別の通知削除
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::patch('/notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');


    // テスト用通知
    Route::get('/test-notification', function () {
        Notification::send(Auth::user(), new TestNotification());
        return 'Notification sent!';
    });
});
// === 認証ルートここまで ===

// 管理者用ルートグループ
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::post('/invitation/generate', [AdminController::class, 'generateInvitationCode'])->name('invitation.generate');
});

require __DIR__.'/auth.php';