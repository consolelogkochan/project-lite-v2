<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\InvitationCode;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * 管理ダッシュボード表示
     */
    public function index()
    {
        // 1. 全ユーザーを取得 (招待コード情報も一緒に)
        $users = User::with('invitationCode')->orderBy('created_at', 'desc')->get();

        // 2. 未使用の招待コードを取得
        $unusedCodes = InvitationCode::where('is_used', false)
                                     ->orderBy('created_at', 'desc')
                                     ->get();

        return view('admin.dashboard', compact('users', 'unusedCodes'));
    }

    /**
     * 新しい招待コードを生成する
     */
    public function generateInvitationCode()
    {
        // ランダムなコードを生成 (例: INV-X7Z9A2)
        $code = 'INV-' . strtoupper(Str::random(6));

        // 重複チェック
        while (InvitationCode::where('code', $code)->exists()) {
            $code = 'INV-' . strtoupper(Str::random(6));
        }

        InvitationCode::create([
            'code' => $code,
            'is_used' => false,
            'expires_at' => null, // 今回は無期限で作成（必要なら now()->addDays(7) 等に）
        ]);

        return redirect()->route('admin.dashboard')->with('status', 'Invitation code generated!');
    }
}