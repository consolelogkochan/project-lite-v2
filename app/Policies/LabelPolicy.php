<?php

namespace App\Policies;

use App\Models\Label;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LabelPolicy
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
    public function view(User $user, Label $label): bool
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

    // ラベルの更新 (ボードメンバーならOK)
    public function update(User $user, Label $label): bool
    {
        return $user->boards()->where('boards.id', $label->board_id)->exists();
    }

    // ラベルの削除 (ボードメンバーならOK)
    public function delete(User $user, Label $label): bool
    {
        return $user->boards()->where('boards.id', $label->board_id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Label $label): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Label $label): bool
    {
        return false;
    }
}
