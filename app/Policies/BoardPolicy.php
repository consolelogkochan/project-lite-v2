<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BoardPolicy
{
    /**
     * Determine whether the user can view any models.
     * (ダッシュボードでの一覧表示)
     */
    public function viewAny(User $user): bool
    {
        // ログインしていれば自分に関係するボードは見れる (DashboardController で制御)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * (カンバンボードの閲覧権限)
     */
    public function view(User $user, Board $board): bool
    {
        // ★ 1. ユーザーがそのボードのメンバー (board_user) であるか？
        return $board->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     * (ボードの新規作成)
     */
    public function create(User $user): bool
    {
        // ログインしていれば誰でも作成可能
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * (ボードのタイトル変更や設定変更)
     */
    public function update(User $user, Board $board): bool
    {
        // ★ 2. ユーザーが 'admin' 権限を持っているか？
        $role = $board->users()->find($user->id)?->pivot->role;
        return $role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     * (ボードの削除)
     */
    public function delete(User $user, Board $board): bool
    {
        // ★ 3. ユーザーが 'admin' 権限を持っているか？
        // (BoardController@destroy での実装とロジックを統一)
        $role = $board->users()->find($user->id)?->pivot->role;
        return $role === 'admin';
        
        // (参考: より厳格な「オーナーのみ」のルール)
        // return $user->id === $board->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Board $board): bool
    {
        // (今回は使用しない)
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Board $board): bool
    {
        // (今回は使用しない)
        return false;
    }

    /**
     * Determine whether the user can add a member to the board.
     * (招待機能の権限)
     * ★ 4. このメソッドを追記
     */
    public function addMember(User $user, Board $board): bool
    {
        // ★ ユーザーが 'admin' 権限を持っているか？
        $role = $board->users()->find($user->id)?->pivot->role;
        return $role === 'admin';
    }
}