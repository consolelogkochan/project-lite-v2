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
        // ★ 変更点: $board->load() の代わりに、
        // $lists 変数を明示的に作成する

        // ボードに属するリストを 'order' 順で取得
        // 同時に、各リストに属するカード (BoardListモデルで 'order' 順に定義済み) も
        // Eager Loading (with('cards')) する
        $lists = $board->lists()->with('cards')->orderBy('order')->get();

        // 'boards.show' ビューに、 $board と $lists の両方を渡す
        return view('boards.show', [
            'board' => $board,
            'lists' => $lists, // ★ 変更点: $lists を追加
        ]);
    }
}
