<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TestNotification;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index']) // ← この行を変更
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/test-notification', function () {
    Notification::send(Auth::user(), new TestNotification());
    return 'Notification sent!';
})->middleware('auth');

// 通知取得用のAPIルート
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update'); // ← この行を追加
});

Route::middleware('auth')->group(function () {
    Route::post('/boards', [BoardController::class, 'store'])->name('boards.store');

    Route::get('/boards/{board}', [BoardController::class, 'show'])->name('boards.show');
});