<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChecklistController extends Controller
{
    /**
     * 新しいチェックリストを作成して保存する (API)
     */
    public function store(Request $request, Card $card)
    {
        // TODO: 認可チェック

        // バリデーション
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

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
    public function update(Request $request, Checklist $checklist)
    {
        // TODO: 認可チェック
        
        // バリデーション
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // データを更新
        $checklist->update($request->all());

        // 更新されたチェックリストを返す (200 OK)
        return response()->json($checklist);
    }

    /**
     * チェックリストを削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(Checklist $checklist)
    {
        // TODO: 認可チェック

        // 削除 (onDelete('cascade') により、items も自動削除される)
        $checklist->delete();

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }
}
