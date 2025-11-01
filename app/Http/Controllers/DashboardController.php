<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 1. この行を追加
use Illuminate\View\View; // 2. この行を追加

class DashboardController extends Controller
{
    /**
     * ユーザーのダッシュボードを表示し、関連するボードを渡す
     */
    public function index(): View
    {
        $user = Auth::user();

        // ユーザーが所有するボードと、招待されたボードの両方を取得
        $boards = $user->boards()->latest()->get();

        return view('dashboard', [
            'boards' => $boards,
        ]);
    }
}
