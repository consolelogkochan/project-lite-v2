<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\Card;
use Illuminate\Support\Carbon;

class ExternalApiController extends Controller
{
    /**
     * 指定されたボードのサマリー情報を取得する
     * GET /api/external/boards/{boardId}/summary
     */
    public function getBoardSummary($boardId)
    {
        // 1. ボードが存在するか簡易チェック (Boardモデルがあれば)
        // もしBoardモデルがない場合は、このブロックを削除して $boardTitle = "Board #$boardId"; にしてください
        $board = Board::find($boardId);
        if (!$board) {
            return response()->json(['error' => 'Board not found'], 404);
        }

        // 2. このボードに紐づくカードを取得
        // Card belongsTo List, List belongsTo Board という構造を利用して検索
        $query = Card::whereHas('list', function ($q) use ($boardId) {
            $q->where('board_id', $boardId);
        });

        // --- 集計データ作成 ---

        // A. 期限切れ (未完了 かつ 期限 < 現在)
        // Cardモデルにある scopeWhereOverdue も使えますが、未完了条件と合わせるため明示的に書きます
        $overdueCount = (clone $query)
            ->where('is_completed', false)
            ->where('end_date', '<', Carbon::now())
            ->count();

        // B. 今日のタスク (未完了 かつ 期限が今日)
        $todayTasks = (clone $query)
            ->where('is_completed', false)
            ->whereDate('end_date', Carbon::today())
            ->orderBy('end_date')
            ->get(['id', 'title', 'end_date', 'is_completed']);

        // C. 今週のタスク (未完了 かつ 期限が今週中)
        // scopeWhereDueThisWeek を利用
        $weekTasks = (clone $query)
            ->where('is_completed', false)
            ->whereDueThisWeek()
            ->orderBy('end_date')
            ->limit(10) // 数が多いと大変なので一旦10件
            ->get(['id', 'title', 'end_date', 'is_completed']);

        // D. 全体の進捗 (完了率計算用)
        $totalCards = (clone $query)->count();
        $completedCards = (clone $query)->where('is_completed', true)->count();
        
        // ゼロ除算回避
        $progressRate = $totalCards > 0 ? round(($completedCards / $totalCards) * 100) : 0;

        // JSONでレスポンス
        return response()->json([
            'board_title' => $board->title ?? "Board {$boardId}",
            'progress' => [
                'total' => $totalCards,
                'completed' => $completedCards,
                'rate' => $progressRate,
                'overdue_count' => $overdueCount,
            ],
            'tasks' => [
                'today' => $todayTasks,
                'week' => $weekTasks,
            ]
        ]);
    }
}