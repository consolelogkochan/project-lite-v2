<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Board; // ★ 1. Board モデルをインポート

class BoardInviteModal extends Component
{
    public Board $board; // ★ 2. public プロパティを追加

    /**
     * Create a new component instance.
     */
    public function __construct(Board $board) // ★ 3. コンストラクタで受け取る
    {
        $this->board = $board;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.board-invite-modal');
    }
}