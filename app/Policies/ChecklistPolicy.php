<?php

namespace App\Policies;

use App\Models\Checklist;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChecklistPolicy
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
    public function view(User $user, Checklist $checklist): bool
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

    // チェックリストの更新 (ボードメンバーならOK)
    public function update(User $user, Checklist $checklist): bool
    {
        // Checklist -> Card -> List -> Board
        $boardId = $checklist->card->list->board_id;
        return $user->boards()->where('boards.id', $boardId)->exists();
    }

    // チェックリストの削除
    public function delete(User $user, Checklist $checklist): bool
    {
        $boardId = $checklist->card->list->board_id;
        return $user->boards()->where('boards.id', $boardId)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Checklist $checklist): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Checklist $checklist): bool
    {
        return false;
    }
}
