<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Http\Requests\BoardStoreRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse; 
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Gate;


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
        // ★ 変更点: $board->load() の代わりに、
        // $lists 変数を明示的に作成する

        // ボードに属するリストを 'order' 順で取得
        // 同時に、各リストに属するカード (BoardListモデルで 'order' 順に定義済み) も
        // ★ 修正: 'cards.labels' も一緒に Eager Loading する
        $lists = $board->lists()->with('cards.labels')->orderBy('order')->get();

        // 'boards.show' ビューに、 $board と $lists の両方を渡す
        return view('boards.show', [
            'board' => $board,
            'lists' => $lists, // ★ 変更点: $lists を追加
        ]);
    }

    /**
     * ボードを削除する (API / Web)
     * ★ このメソッドを追加
     */
    public function destroy(Request $request, Board $board): RedirectResponse
    {
        // ★ 2. 認可チェック: ボードのオーナー（owner_id）本人であるか
        // (簡易チェック)
        if (Auth::id() !== $board->owner_id) {
            // (将来的に Gate::authorize('delete', $board); に置き換える)
            
            // APIリクエストの場合 (JSONを期待している場合)
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            // 通常のWebリクエストの場合
            return redirect()->route('dashboard')->with('error', 'You do not have permission to delete this board.');
        }

        // ★ 3. ボードを削除
        // マイグレーションで onDelete('cascade') を設定していれば、
        // 関連するリスト、カード、ラベル、コメントもすべて連鎖削除される
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
