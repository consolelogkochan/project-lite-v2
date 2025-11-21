<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board; // 1. Boardモデルを追加
use App\Http\Requests\ListStoreRequest; // 2. 作成したRequestを追加
use App\Http\Requests\ListUpdateRequest;
use App\Http\Requests\ListOrderRequest;
use App\Models\BoardList;
use Illuminate\Support\Facades\Log;


class ListController extends Controller
{
    /**
     * 新しいリストを作成して保存する (AJAXリクエスト)
     */
    public function store(ListStoreRequest $request, Board $board)
    {
        // 3. バリデーション済みのデータを取得
        $validated = $request->validated();

        // 4. 新しいリストの「順序」を計算
        //    現在のボードにあるリストの最大の'order'値を取得し、それに1を加える
        $maxOrder = $board->lists()->max('order');
        $newOrder = $maxOrder + 1;

        // 5. 新しいリストを作成
        $list = $board->lists()->create([
            'title' => $validated['title'],
            'order' => $newOrder,
        ]);

        // 6. 作成したリストをJSON形式でフロントエンドに返す
        return response()->json($list);
    }
    /**
     * リストのタイトルを更新する (AJAXリクエスト)
     */
    public function update(ListUpdateRequest $request, BoardList $list)
    {
        // 1. バリデーション済みのデータを取得
        $validated = $request->validated();

        // 2. リストのタイトルを更新
        $list->update([
            'title' => $validated['title'],
        ]);

        // 3. 更新したリストをJSON形式で返す
        return response()->json($list);
    }
    /**
     * リストを削除する (AJAXリクエスト)
     */
    public function destroy(BoardList $list)
    {
        // TODO: 認可（ポリシー）を追加して、ボードの所有者だけが
        // リストを削除できるように制限するのが望ましい

        $list->delete();

        // 成功したが、返すコンテンツはない（204 No Content）
        return response()->noContent();
    }
    /**
    * リストの表示順序を更新する (AJAXリクエスト)
    */
    public function updateOrder(ListOrderRequest $request)
    {
        // ★ バリデーションは自動化されたので manual check は削除

        // バリデーション済みのデータを取得
        $orderedListIds = $request->validated()['orderedListIds'];

        // ▼▼▼ ログを仕込む ▼▼▼
        Log::info('Updating list order. Received IDs:', $orderedListIds);

        // 3. データベースを更新（ループは1回だけ）
        foreach ($orderedListIds as $index => $listId) {

            Log::info("Updating List ID: {$listId} to Order: {$index}");

            $affectedRows = BoardList::where('id', $listId)
                ->update(['order' => $index]);

            Log::info("Rows affected: {$affectedRows}");
        }
        // ▲▲▲ ログここまで ▲▲▲

        // 4. 成功を返す
        return response()->noContent();
    }
}
