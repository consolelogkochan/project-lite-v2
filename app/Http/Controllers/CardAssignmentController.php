<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\Request;

class CardAssignmentController extends Controller
{
    /**
     * カードにユーザーを割り当てる (API)
     */
    public function assignUser(Request $request, Card $card, User $user)
    {
        // TODO: 認可チェック
        // (ユーザーはこのカードにアクセスできるか？)
        // (割り当てられる 'user' は、このボードのメンバーか？)
        
        // 中間テーブル (card_user) にレコードを追加
        // (既に存在する場合でもエラーにならない)
        $card->assignedUsers()->syncWithoutDetaching($user->id);

        return response()->json(['message' => 'User assigned successfully.']);
    }

    /**
     * カードからユーザーの割り当てを解除する (API)
     */
    public function unassignUser(Request $request, Card $card, User $user)
    {
        // TODO: 認可チェック

        // 中間テーブル (card_user) からレコードを削除
        $card->assignedUsers()->detach($user->id);

        return response()->json(['message' => 'User unassigned successfully.']);
    }
}
