<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // ★ 1. Log をインポート

class CardAssignmentController extends Controller
{
    /**
     * カードにユーザーを割り当てる (API)
     */
    public function assignUser(Request $request, Card $card, User $user)
    {
        // TODO: 認可チェック
        
        Log::debug("assignUser: CardID {$card->id}, UserID {$user->id}"); // ★ 2. ログ追加

        try {
            // 中間テーブル (card_user) にレコードを追加
            $card->assignedUsers()->syncWithoutDetaching($user->id);
            
            Log::debug("assignUser: syncWithoutDetaching SUCCESS"); // ★ 2. ログ追加

        } catch (\Exception $e) {
            Log::error("assignUser: FAILED", ['error' => $e->getMessage()]); // ★ 2. エラーログ追加
            return response()->json(['message' => 'Failed to assign user.'], 500);
        }

        return response()->json(['message' => 'User assigned successfully.']);
    }

    /**
     * カードからユーザーの割り当てを解除する (API)
     */
    public function unassignUser(Request $request, Card $card, User $user)
    {
        // TODO: 認可チェック

        Log::debug("unassignUser: CardID {$card->id}, UserID {$user->id}"); // ★ 2. ログ追加

        try {
            // 中間テーブル (card_user) からレコードを削除
            $card->assignedUsers()->detach($user->id);

            Log::debug("unassignUser: detach SUCCESS"); // ★ 2. ログ追加

        } catch (\Exception $e) {
            Log::error("unassignUser: FAILED", ['error' => $e->getMessage()]); // ★ 2. エラーログ追加
            return response()->json(['message' => 'Failed to unassign user.'], 500);
        }

        return response()->json(['message' => 'User unassigned successfully.']);
    }
}