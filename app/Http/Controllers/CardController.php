<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Support\Facades\Validator;
// ★ use Illuminate\Validation\Rule; // (将来の権限チェックで使うかも)
use Illuminate\Support\Facades\DB;

class CardController extends Controller
{
    /**
     * 新しいカードを作成して保存する (API)
     */
    public function store(Request $request, BoardList $list)
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 新しいカードの 'order' を決定
        // そのリストにある既存のカードの最大 'order' + 1、またはカードがなければ 0
        $maxOrder = $list->cards()->max('order');
        $order = is_null($maxOrder) ? 0 : $maxOrder + 1;

        // カードを作成
        $card = $list->cards()->create([
            'title' => $request->title,
            'order' => $order,
        ]);

        // 作成したカードをJSONで返す (HTTPステータス 201G)
        return response()->json($card, 201);
    }
    /**
     * カードの情報を更新する (API)
     * (主にタイトル更新用)
     * ★ このメソッドを追加
     */
    public function update(Request $request, Card $card)
    {
        // TODO: ここに「このカードを編集する権限があるか」の
        // 認可(Policy)チェックを将来追加する

        // バリデーション
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            // 'description' など、他のフィールドも将来ここに追加できる
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // データを更新
        $card->update([
            'title' => $request->title,
        ]);

        // 更新されたカードデータを返す
        return response()->json($card);
    }
    /**
     * カードを削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(Card $card)
    {
        // TODO: ここに「このカードを削除する権限があるか」の
        // 認可(Policy)チェックを将来追加する

        // カードをDBから削除
        $card->delete();

        // 成功したら、 204 (No Content) ステータスを返す
        // (レスポンスボディは空)
        return response()->noContent(); 
    }
    /**
     * カードの順序と所属リストを一括更新する (API)
     * ★ このメソッドを追加
     */
    public function updateOrder(Request $request)
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'lists' => 'required|array',
            'lists.*.id' => 'required|integer|exists:lists,id',
            'lists.*.cards' => 'required|array',
            'lists.*.cards.*' => 'required|integer|exists:cards,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // TODO: ここに「このボードのカードを編集する権限があるか」の
        // 認可(Policy)チェックを将来追加する

        // トランザクション開始
        try {
            DB::beginTransaction();

            $lists = $request->input('lists', []);

            foreach ($lists as $listData) {
                $listId = $listData['id'];
                foreach ($listData['cards'] as $index => $cardId) {
                    // カードの 'order' と 'board_list_id' を一括更新
                    Card::where('id', $cardId)
                        // ->where('board_list_id', '!=', $listId) // (念のため)
                        ->update([
                            'order' => $index,
                            'board_list_id' => $listId
                        ]);
                }
            }

            DB::commit(); // 成功したらコミット

            return response()->json(['message' => 'Card order updated successfully.']);

        } catch (\Exception $e) {
            DB::rollBack(); // エラーが発生したらロールバック
            // (エラーログを記録)
            report($e);
            return response()->json(['message' => 'An error occurred while updating card order.'], 500);
        }
    }
}
