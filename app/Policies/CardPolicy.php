<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CardPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * カードの閲覧 (ボードメンバーならOK)
     */
    public function view(User $user, Card $card): bool
    {
        // カード -> リスト -> ボード の順でIDを辿る
        // (N+1を防ぐため、Controller側でEagerLoadされている前提だが、
        // ここでは安全策として外部キー経由で確認)
        $boardId = $card->list->board_id; 
        return $this->isBoardMember($user, $boardId);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * カードの更新 (ボードメンバーならOK)
     */
    public function update(User $user, Card $card): bool
    {
        $boardId = $card->list->board_id;
        return $this->isBoardMember($user, $boardId);
    }

    /**
     * カードの削除 (ボードメンバーならOK)
     */
    public function delete(User $user, Card $card): bool
    {
        $boardId = $card->list->board_id;
        return $this->isBoardMember($user, $boardId);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Card $card): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Card $card): bool
    {
        return false;
    }

    protected function isBoardMember(User $user, int $boardId): bool
    {
        return $user->boards()->where('boards.id', $boardId)->exists();
    }
}
