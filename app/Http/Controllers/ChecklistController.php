<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Checklist;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ChecklistStoreRequest;
use App\Http\Requests\ChecklistUpdateRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ChecklistController extends Controller
{
    // クラス先頭に追加
    use AuthorizesRequests;
    /**
     * 新しいチェックリストを作成して保存する (API)
     */
    public function store(ChecklistStoreRequest $request, Card $card)
    {
        // カード編集権限が必要
        $this->authorize('update', $card);

        // チェックリストを作成
        $checklist = $card->checklists()->create([
            'title' => $request->title,
        ]);

        // ★ 重要: 作成したチェックリストにはまだ 'items' がないため、
        // items 配列を明示的に追加して返す
        $checklist['items'] = [];

        // 201 Created でJSONを返す
        return response()->json($checklist, 201);
    }

    /**
     * チェックリストを更新する (API)
     * (主にタイトルの更新用)
     * ★ このメソッドを追加
     */
    public function update(ChecklistUpdateRequest $request, Checklist $checklist)
    {
        // ChecklistPolicy@update
        $this->authorize('update', $checklist);

        // データを更新
        $checklist->update($request->validated());

        // 更新されたチェックリストを返す (200 OK)
        return response()->json($checklist);
    }

    /**
     * チェックリストを削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(Checklist $checklist)
    {
        // ChecklistPolicy@delete
        $this->authorize('delete', $checklist);

        // 削除 (onDelete('cascade') により、items も自動削除される)
        $checklist->delete();

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }
}
