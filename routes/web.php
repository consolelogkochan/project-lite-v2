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

    // 通知 (API)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');

    // テスト用通知
    Route::get('/test-notification', function () {
        Notification::send(Auth::user(), new TestNotification());
        return 'Notification sent!';
    });
});
// === 認証ルートここまで ===

require __DIR__.'/auth.php';