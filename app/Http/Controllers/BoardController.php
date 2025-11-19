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
use Illuminate\Database\Eloquent\Builder;


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
    public function show(Request $request, Board $board): View
    {
        $this->authorize('view', $board);
        $board->load('users');

        $currentUserId = Auth::id(); // 担当者フィルター用

        // 3. リストと関連データを Eager Loading (カードにフィルターを適用)
        $lists = $board->lists()
                       ->with([
                            // ★★★ 1. Eager Loading とフィルタリングを統合 ★★★
                            'cards' => function ($query) use ($request, $currentUserId) {
                                
                                // ★ 1. NEW FIX: 必要な全てのネストされたリレーションをここでロード
                                $query->with('assignedUsers', 'labels', 'checklists.items', 'attachments.user', 'comments');

                                // フィルターパラメータの取得
                                $labelsFilter = $request->input("filterLabels");
                                $periodFilter = $request->input("filterPeriod");
                                $memberFilter = $request->input("filterMember");
                                $checklistFilter = $request->input("filterChecklist");
                                $completedFilter = $request->input("filterCompleted");
                                $keyword = $request->input("q", "");

                                // 3-1. 完了ステータス (Completion Status)
                                if ($completedFilter === "true") {
                                    $query->whereCompleted();
                                } elseif ($completedFilter === "false") {
                                    $query->whereIncomplete();
                                }

                                // 3-2. メンバー (Member)
                                if ($memberFilter === "mine") {
                                    $query->whereAssignedToMe($currentUserId);
                                } elseif ($memberFilter === "none") {
                                    $query->whereNoAssignee();
                                }

                                // 3-3. ラベル (Label)
                                if ($labelsFilter === "has") {
                                    $query->whereHasLabels();
                                } elseif ($labelsFilter === "none") {
                                    $query->whereDoesntHaveLabels();
                                }

                                // 3-4. チェックリスト (Checklist)
                                if ($checklistFilter === "has") {
                                    $query->whereHasChecklists(); 
                                } elseif ($checklistFilter === "none") {
                                    $query->whereDoesntHaveChecklists();
                                }

                                // 3-5. 期間 (Period / Due Date)
                                if ($periodFilter === "none_due") {
                                    $query->whereNoDueDate();
                                } elseif ($periodFilter === "overdue") {
                                    $query->whereOverdue();
                                } elseif ($periodFilter === "tomorrow") {
                                    $query->whereDueTomorrow();
                                } elseif ($periodFilter === "this_week") {
                                    $query->whereDueThisWeek();
                                } elseif ($periodFilter === "this_month") {
                                    $query->whereDueThisMonth();
                                }

                                // 3-6. キーワードフィルター
                                if (!empty($keyword)) {
                                    $query->where(function ($q) use ($keyword) {
                                        $q->where("title", "like", "%{$keyword}%")
                                          ->orWhere("description", "like", "%{$keyword}%")
                                          ->orWhereHas("comments", fn($com) => $com->where("content", "like", "%{$keyword}%"))
                                          ->orWhereHas("attachments", fn($att) => $att->where("file_name", "like", "%{$keyword}%"))
                                          ->orWhereHas("checklists", function ($chk) use ($keyword) {
                                              $chk->where("title", "like", "%{$keyword}%")
                                                  ->orWhereHas("items", fn($itm) => $itm->where("content", "like", "%{$keyword}%"));
                                          });
                                    });
                                }
                                
                                // カンバンボードではカードの order でソートする
                                $query->orderBy('order');
                            }
                            // ★ 2. 外部の Eager Load 指定は全て削除
                       ])
                       ->orderBy('order')->get();
        
        // 4. ビューに渡す
        return view('boards.show', [
            'board' => $board, 
            'lists' => $lists,
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
     * カレンダー/タイムラインビュー用のカード情報を取得する (API)
     */
    public function getCalendarEvents(Request $request, Board $board)
    {
        // 認可チェック
        $this->authorize("view", $board);

        // 1. フロントエンドから送信されたすべてのフィルターパラメータを取得
        $labelsFilter = $request->input("filterLabels");
        $periodFilter = $request->input("filterPeriod");
        $memberFilter = $request->input("filterMember");
        $checklistFilter = $request->input("filterChecklist");
        $completedFilter = $request->input("filterCompleted");
        $keyword = $request->input("q", "");

        // 2. ベースクエリの構築
        $listIds = $board->lists()->pluck("id");
        // 期限が設定されているカードを対象とする
        $cardsQuery = Card::whereIn("board_list_id", $listIds)
                          ->whereNotNull("end_date"); 

        // 3. ★★★ NEW: 新しいフィルターロジックの適用 ★★★

        // 3-1. 完了ステータス (Completion Status)
        if ($completedFilter === "true") {
            $cardsQuery->whereCompleted();
        } elseif ($completedFilter === "false") {
            $cardsQuery->whereIncomplete();
        }

        // 3-2. メンバー (Member)
        if ($memberFilter === "mine") {
            $cardsQuery->whereAssignedToMe(Auth::id());
        } elseif ($memberFilter === "none") {
            $cardsQuery->whereNoAssignee();
        }

        // 3-3. ラベル (Label)
        if ($labelsFilter === "has") {
            $cardsQuery->whereHasLabels();
        } elseif ($labelsFilter === "none") {
            $cardsQuery->whereDoesntHaveLabels();
        }

        // 3-4. チェックリスト (Checklist)
        if ($checklistFilter === "has") {
            $cardsQuery->whereHasChecklists();
        } elseif ($checklistFilter === "none") {
            $cardsQuery->whereDoesntHaveChecklists();
        }

        // 3-5. 期間 (Period / Due Date)
        if ($periodFilter === "none_due") {
            $cardsQuery->whereNoDueDate();
        } elseif ($periodFilter === "overdue") {
            $cardsQuery->whereOverdue();
        } elseif ($periodFilter === "tomorrow") {
            $cardsQuery->whereDueTomorrow();
        } elseif ($periodFilter === "this_week") {
            $cardsQuery->whereDueThisWeek();
        } elseif ($periodFilter === "this_month") {
            $cardsQuery->whereDueThisMonth();
        }
        
        // 4. ★★★ 既存のキーワードフィルターロジックの適用 ★★★
        if (!empty($keyword)) {
            $cardsQuery->where(function ($query) use ($keyword) {
                $query->where("title", "like", "%{$keyword}%")
                ->orWhere("description", "like", "%{$keyword}%")
                ->orWhereHas("comments", fn($q) => $q->where("content", "like", "%{$keyword}%"))
                ->orWhereHas("attachments", fn($q) => $q->where("file_name", "like", "%{$keyword}%"))
                ->orWhereHas("checklists", function ($q) use ($keyword) {
                    $q->where("title", "like", "%{$keyword}%")
                      ->orWhereHas("items", fn($iq) => $iq->where("content", "like", "%{$keyword}%"));
                });
            });
        }
        
        // 5. ソートとデータ取得 (user_399_fix の修正)
        $cards = $cardsQuery->orderBy("end_date")->get();
        
        // 6. FullCalendar 形式にマッピング
        $viewType = $request->input("view", "calendar");

        // FullCalendar 形式にマッピング
        $events = $cards->map(function (Card $card) use ($board, $viewType) {
            
            // Carbon オブジェクト (UTC) を取得
            $end = $card->end_date;
            $start = $card->start_date ? $card->start_date : $end;
            
            // ★★★ [FIX] タイムゾーン変換 ★★★
            // ユーザーのタイムゾーン (例: 'Asia/Tokyo') に変換
            // (config/app.php の 'timezone' が 'UTC' 前提のコード)
            $localTimezone = config("app.timezone_user", "Asia/Tokyo"); // (JSTをデフォルトに)
            
            if ($viewType === "calendar") {
                // --- カレンダービュー (dayGrid) 用 ---
                
                // 1. [FIX] UTC -> JST に変換してから Y-m-d を取得
                $startStr = $start->copy()->setTimezone($localTimezone)->startOfDay()->format("Y-m-d");
                
                // 2. [FIX] UTC -> JST に変換してから Y-m-d を取得
                $endStr = $end->copy()->setTimezone($localTimezone)->addDay()->startOfDay()->format("Y-m-d");
                
                $isAllDay = true;
                
            } else {
                // --- タイムラインビュー (timeGrid) 用 ---
                // (toISOString() は元々 UTC を返すので変換不要)
                $startStr = $start->toISOString();
                $isAllDay = $start->isStartOfDay() && $end->isStartOfDay();
                $displayEnd = $isAllDay ? $end->copy()->addDay() : $end;
                $endStr = $displayEnd->toISOString();
            }
            // ★★★ 分岐ここまで ★★★

            return [
                "id" => $card->id,
                "title" => $card->title,
                "start" => $startStr,
                "end" => $endStr,
                "allDay" => $isAllDay,
                "url" => route("boards.show", $board->id) . "?card=" . $card->id,
                "color" => $card->is_completed ? "#16a34a" : "#2563eb",
                "className" => $card->is_completed ? "opacity-70" : "",
            ];
        });

        return response()->json($events);
    }
}
