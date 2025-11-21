<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttachmentPolicy
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
    public function view(User $user, Attachment $attachment): bool
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
     * 添付ファイルの更新 (レビュー状況など)
     * 現状はボードメンバー全員に許可
     */
    public function update(User $user, Attachment $attachment): bool
    {
        $boardId = $attachment->card->list->board_id;
        return $user->boards()->where('boards.id', $boardId)->exists();
    }

    /**
     * 添付ファイルの削除 (アップロード本人 OR ボード管理者)
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        // 1. 本人ならOK
        if ($user->id === $attachment->user_id) {
            return true;
        }

        // 2. ボード管理者ならOK
        $board = $attachment->card->list->board;
        $role = $board->users()->find($user->id)?->pivot->role;

        return $role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attachment $attachment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attachment $attachment): bool
    {
        return false;
    }
}
