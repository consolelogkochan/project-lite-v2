<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TestNotification;

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

    // リスト
    Route::post('/boards/{board}/lists', [ListController::class, 'store'])->name('lists.store');
    
    // ▼▼▼ "update-order"をワイルドカードより「上」に移動 ▼▼▼
    Route::patch('/lists/update-order', [ListController::class, 'updateOrder'])->name('lists.updateOrder');
    Route::patch('/lists/{list}', [ListController::class, 'update'])->name('lists.update');
    Route::delete('/lists/{list}', [ListController::class, 'destroy'])->name('lists.destroy');

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