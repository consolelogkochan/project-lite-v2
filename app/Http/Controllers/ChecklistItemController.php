<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ChecklistItemController extends Controller
{
    /**
     * 新しいチェックリスト項目を作成して保存する (API)
     */
    public function store(Request $request, Checklist $checklist)
    {
        // TODO: 認可チェック

        // バリデーション
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

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
    public function update(Request $request, ChecklistItem $item)
    {
        // TODO: 認可チェック
        
        // バリデーション
        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|required|string',
            'is_completed' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // データを更新
        $item->update($request->all());

        // 更新されたアイテムを返す (200 OK)
        return response()->json($item);
    }

    /**
     * チェックリスト項目を削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(ChecklistItem $item)
    {
        // TODO: 認可チェック

        $item->delete();

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }

    /**
     * チェックリスト項目の順序を一括更新する (API)
     * ★ このメソッドを追加
     */
    public function updateOrder(Request $request)
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'checklist_id' => 'required|integer|exists:checklists,id',
            'ordered_item_ids' => 'required|array',
            'ordered_item_ids.*' => 'required|integer|exists:checklist_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // TODO: 認可チェック (このチェックリストを編集する権限があるか)
        
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
