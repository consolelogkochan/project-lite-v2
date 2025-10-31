<?php

namespace App\Providers;

use Illuminate\Support\Facades\View; // 1. この行を追加
use Illuminate\Support\Facades\Auth; // 2. この行を追加
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 3. このブロックを追記
        View::composer('components.header', function ($view) {
            if (Auth::check()) {
                $view->with('unreadNotificationsCount', Auth::user()->unreadNotifications()->count());
            } else {
                $view->with('unreadNotificationsCount', 0);
            }
        });
    }
}
