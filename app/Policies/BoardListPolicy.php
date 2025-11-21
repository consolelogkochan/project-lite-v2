<?php

namespace App\Policies;

use App\Models\BoardList;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BoardListPolicy
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
    public function view(User $user, BoardList $boardList): bool
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
     * リストの作成・更新・移動 (ボードメンバーならOK)
     * ※ storeメソッドでは Board モデルを使うため、ここには記述せず Controller で BoardPolicy を使います。
     */
    public function update(User $user, BoardList $list): bool
    {
        return $this->isBoardMember($user, $list->board_id);
    }

    /**
     * リストの削除 (ボードメンバーならOK)
     * (厳格にするならAdminのみにするが、Trello的運用ならMemberもOK)
     */
    public function delete(User $user, BoardList $list): bool
    {
        return $this->isBoardMember($user, $list->board_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BoardList $boardList): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BoardList $boardList): bool
    {
        return false;
    }

    /**
     * ヘルパー: ユーザーがそのリストの親ボードのメンバーか確認
     */
    protected function isBoardMember(User $user, int $boardId): bool
    {
        // Userモデルの boards() リレーションを使って確認
        return $user->boards()->where('boards.id', $boardId)->exists();
    }
}
