<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Http\Requests\BoardStoreRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


class BoardController extends Controller
{
    /**
     * 新しいボードを作成して保存する
     */
    public function store(BoardStoreRequest $request)
    {
        // バリデーションはBoardStoreRequestが自動で実行するので、
        // $validated = $request->validate([...]); のブロックは丸ごと削除します。

        // バリデーション済みデータを取得
        $validated = $request->validated();

        // ログイン中のユーザーを取得
        $user = Auth::user();

        // ボードを作成
        $board = new Board();
        $board->title = $validated['title'];
        $board->background_color = $validated['background_color'];
        $board->owner_id = $user->id;
        $board->save();

        // ボードの作成者を自動的にメンバーとして中間テーブルに追加
        $user->boards()->attach($board->id);

        // ダッシュボードにリダイレクト
        return redirect()->route('dashboard');
    }

    /**
     * 特定のボードを表示する
     */
    public function show(Board $board): View
    {
        // ボードに紐づくリストと、各リストに紐づくカードをすべて読み込む
        $board->load(['lists.cards']);

        // 'boards.show'というビューに、取得したボードのデータを渡す
        return view('boards.show', [
            'board' => $board
        ]);
    }
}
