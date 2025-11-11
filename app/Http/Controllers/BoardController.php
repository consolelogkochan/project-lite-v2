<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Http\Requests\BoardStoreRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse; 
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class BoardController extends Controller
{
    use AuthorizesRequests;

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
        // ★ 'role' => 'admin' を設定
        $user->boards()->attach($board->id, ['role' => 'admin']);

        // デフォルトラベル「今日の目標」を作成する
        $board->labels()->create([
            'name' => '今日の目標',
            'color' => 'bg-green-500' // (例: Tailwind の背景色クラス)
        ]);

        // ダッシュボードにリダイレクト
        return redirect()->route('dashboard');
    }

    /**
     * 特定のボードを表示する
     */
    public function show(Board $board): View
    {
        // 1. 認可(Policy)チェック
        $this->authorize('view', $board);

        // ★ 2. 修正: シンプルな Eager Loading に戻す
        $board->load('users'); // ヘッダーのアバター用

        // 3. リストと関連データを Eager Loading
        $lists = $board->lists()
                       ->with('cards.labels', 'cards.checklists.items', 'cards.attachments.user')
                       ->orderBy('order')->get();
        
        // 4. ビューに渡す
        return view('boards.show', [
            'board' => $board, // ★ 'users' リレーションを含んだ $board
            'lists' => $lists,
            // 'members' => $members, // ★ $members 変数は不要になった
        ]);
    }

    /**
     * ボードを削除する (API / Web)
     * ★ このメソッドを追加
     */
    public function destroy(Request $request, Board $board): RedirectResponse
    {
        // ★ 修正: BoardPolicy@delete を呼び出す
        $this->authorize('delete', $board);

        // ★ 3. ボードを削除
        $board->delete();

        // ★ 4. 成功時の応答
        
        // APIリクエストの場合
        if ($request->expectsJson()) {
            return response()->noContent(); // 204 No Content
        }
        
        // 通常のWebリクエストの場合 (ダッシュボードに戻す)
        return redirect()->route('dashboard')->with('status', 'Board deleted successfully.');
    }
}
