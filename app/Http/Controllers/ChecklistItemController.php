<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\ChecklistItem;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ChecklistItemStoreRequest;
use App\Http\Requests\ChecklistItemUpdateRequest;
use App\Http\Requests\ChecklistItemOrderRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ChecklistItemController extends Controller
{
    use AuthorizesRequests;
    /**
     * 新しいチェックリスト項目を作成して保存する (API)
     */
    public function store(ChecklistItemStoreRequest $request, Checklist $checklist)
    {
        // 親チェックリストの更新権限 = アイテム追加権限
        $this->authorize('update', $checklist);

        // 新しい項目の 'position' を決定
        // 既存のアイテムの最大 'position' + 1、またはアイテムがなければ 0
        $maxPosition = $checklist->items()->max('position');
        $position = is_null($maxPosition) ? 0 : $maxPosition + 1;

        // チェックリスト項目を作成
        $item = $checklist->items()->create([
            'content' => $request->content,
            'position' => $position,
            'is_completed' => false,
        ]);

        // 201 Created でJSONを返す
        return response()->json($item, 201);
    }

    /**
     * チェックリスト項目を更新する (API)
     * (主に 'is_completed' または 'content' の更新用)
     * ★ このメソッドを追加
     */
    public function update(ChecklistItemUpdateRequest $request, ChecklistItem $item)
    {
        // ChecklistItemPolicy@update
        $this->authorize('update', $item);

        // データを更新
        $item->update($request->validated());

        // 更新されたアイテムを返す (200 OK)
        return response()->json($item);
    }

    /**
     * チェックリスト項目を削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(ChecklistItem $item)
    {
        // ChecklistItemPolicy@delete
        $this->authorize('delete', $item);

        $item->delete();

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }

    /**
     * チェックリスト項目の順序を一括更新する (API)
     * ★ このメソッドを追加
     */
    public function updateOrder(ChecklistItemOrderRequest $request)
    {

        // 配列処理のため、代表して親チェックリストの権限を確認する
        $checklist = \App\Models\Checklist::findOrFail($request->checklist_id);
        $this->authorize('update', $checklist);
        
        $checklistId = $request->input('checklist_id');
        $itemIds = $request->input('ordered_item_ids', []);

        // トランザクション開始
        try {
            DB::beginTransaction();

            foreach ($itemIds as $index => $itemId) {
                // 'position' を更新
                // (同じ checklist_id 内でのみ更新するように制限)
                ChecklistItem::where('id', $itemId)
                            ->where('checklist_id', $checklistId)
                            ->update(['position' => $index]);
            }

            DB::commit(); // 成功したらコミット

            return response()->json(['message' => 'Item order updated successfully.']);

        } catch (\Exception $e) {
            DB::rollBack(); // エラーが発生したらロールバック
            report($e);
            return response()->json(['message' => 'An error occurred while updating item order.'], 500);
        }
    }
}
