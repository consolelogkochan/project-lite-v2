<?php

namespace App\Policies;

use App\Models\ChecklistItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChecklistItemPolicy
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
    public function view(User $user, ChecklistItem $checklistItem): bool
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

    // 項目の更新
    public function update(User $user, ChecklistItem $item): bool
    {
        // Item -> Checklist -> Card -> List -> Board
        $boardId = $item->checklist->card->list->board_id;
        return $user->boards()->where('boards.id', $boardId)->exists();
    }

    // 項目の削除
    public function delete(User $user, ChecklistItem $item): bool
    {
        $boardId = $item->checklist->card->list->board_id;
        return $user->boards()->where('boards.id', $boardId)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ChecklistItem $checklistItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ChecklistItem $checklistItem): bool
    {
        return false;
    }
}
