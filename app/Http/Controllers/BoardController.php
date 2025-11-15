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
use App\Models\User;
use App\Models\Card;


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
                       ->with('cards', 'cards.labels', 'cards.checklists.items', 'cards.attachments.user', 'cards.comments') 
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

    /**
     * カレンダービュー用のカード情報を取得する (API)
     */
    public function getCalendarEvents(Request $request, Board $board)
    {
        // 認可チェック
        $this->authorize('view', $board);

        // キーワードを取得 (なければ空文字)
        $keyword = $request->input('q', '');

        // ★ 1. [Fix] 'hasManyThrough' (Board->cards()) が orWhereHas と競合するため、
        //    先にリストIDを取得する
        $listIds = $board->lists()->pluck('id');

        // ★ 2. Card モデルを「直接」クエリする
        $cardsQuery = Card::whereIn('board_list_id', $listIds)
                          ->whereNotNull('end_date'); // 期限 (end_date) が必須

        // 3. [NEW] キーワードが存在する場合、フィルターを実行
        if (!empty($keyword)) {
            $cardsQuery->where(function ($query) use ($keyword) {
                // カード名
                $query->where('title', 'like', "%{$keyword}%")
                // 説明文
                ->orWhere('description', 'like', "%{$keyword}%")
                // コメント
                ->orWhereHas('comments', function ($q) use ($keyword) {
                    $q->where('content', 'like', "%{$keyword}%");
                })
                // 添付ファイル
                ->orWhereHas('attachments', function ($q) use ($keyword) {
                    $q->where('file_name', 'like', "%{$keyword}%");
                })
                // チェックリスト (タイトルまたはアイテム)
                ->orWhereHas('checklists', function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                      ->orWhereHas('items', function ($iq) use ($keyword) {
                          $iq->where('content', 'like', "%{$keyword}%");
                      });
                });
            });
        }
        
        $cards = $cardsQuery->get();

        // 4. FullCalendar.js が要求する形式にマッピング（変換）
        // (この部分は user_346_fix と同じ)
        $events = $cards->map(function (Card $card) use ($board) {
            
            $end = $card->end_date;
            $calendarEnd = $end->copy()->addDay()->startOfDay();
            $start = $card->start_date ? $card->start_date->copy()->startOfDay() : $end->copy()->startOfDay();

            return [
                'id' => $card->id,
                'title' => $card->title,
                'start' => $start->toISOString(),
                'end' => $calendarEnd->toISOString(),
                'allDay' => true,
                'url' => route('boards.show', $board->id) . '?card=' . $card->id,
                'color' => $card->is_completed ? '#16a34a' : '#2563eb',
                'className' => $card->is_completed ? 'opacity-70' : '',
            ];
        });

        return response()->json($events);
    }

    /**
     * タイムラインビュー用のリソース（リスト）を取得する (API)
     * ★ このメソッドを追加
     */
    public function getTimelineResources(Request $request, Board $board)
    {
        // 認可チェック
        $this->authorize('view', $board);

        // ボードに属するリストを `order` 順に取得
        $lists = $board->lists()->orderBy('order')->get();

        // FullCalendar Resource 形式 (id, title) にマッピング
        $resources = $lists->map(function ($list) {
            return [
                'id' => $list->id,
                'title' => $list->title,
            ];
        });

        return response()->json($resources);
    }

}
