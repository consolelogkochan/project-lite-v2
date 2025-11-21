<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comment $comment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * コメントの更新 (自分のコメントのみ)
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * コメントの削除 (自分のコメント OR ボードの管理者)
     */
    public function delete(User $user, Comment $comment): bool
    {
        // 1. 自分のコメントならOK
        if ($user->id === $comment->user_id) {
            return true;
        }

        // 2. ボードの管理者ならOK
        // (コメント -> カード -> リスト -> ボード)
        $board = $comment->card->list->board;
        
        // 中間テーブルの role を確認
        $role = $board->users()->find($user->id)?->pivot->role;
        
        return $role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        return false;
    }
}
