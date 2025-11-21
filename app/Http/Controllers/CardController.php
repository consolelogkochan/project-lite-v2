<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
// use Illuminate\Support\Facades\Validator;
// ★ use Illuminate\Validation\Rule; // (将来の権限チェックで使うかも)
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Events\CardMoved; // ★ 追加
use Illuminate\Support\Facades\Auth; // (なければ追加)
use App\Events\CardCreated;
use App\Events\CardDeleted;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

// ★ 新しいRequestクラスをインポート
use App\Http\Requests\CardStoreRequest;
use App\Http\Requests\CardUpdateRequest;
use App\Http\Requests\CardOrderRequest;

class CardController extends Controller
{
    use AuthorizesRequests;
    /**
     * 新しいカードを作成して保存する (API)
     */
    public function store(CardStoreRequest $request, BoardList $list)
    {
        // ★ バリデーションロジックを削除 (自動化されるため)

        // 新しいカードの 'order' を決定
        // そのリストにある既存のカードの最大 'order' + 1、またはカードがなければ 0
        $maxOrder = $list->cards()->max('order');
        $order = is_null($maxOrder) ? 0 : $maxOrder + 1;

        // カードを作成
        $card = $list->cards()->create([
            'title' => $request->title,
            'order' => $order,
        ]);

        // ★ イベント発火
        // (作成直後の $card には list がロードされていない場合があるためロードしておく)
        $card->setRelation('list', $list); 
        CardCreated::dispatch($card, Auth::user());

        // 作成したカードをJSONで返す (HTTPステータス 201G)
        return response()->json($card, 201);
    }
    /**
     * カード詳細情報を取得する (API)
     * ★ このメソッドを追加
     */
    public function show(Card $card)
    {
        // ★ 追加: CardPolicy@view を実行
        $this->authorize('view', $card);

        // ★ 2. 修正: 'assignedUsers' の Eager Loading を削除
        $card->load('list.board', 'comments.user', 'labels', 'checklists.items', 'attachments.user'); 

        // ★ 3. 割り当て済みメンバーを「手動」でロード
        // (belongsToMany のEager Loadingバグを回避するため)
        $assignedUserIds = $card->assignedUsers()->pluck('users.id');
        $card->assignedUsers = User::whereIn('id', $assignedUserIds)->get();

        return response()->json($card);
    }

    /**
     * カードの情報を更新する (API)
     * (主にタイトル更新用)
     * ★ このメソッドを追加
     */
    public function update(CardUpdateRequest $request, Card $card)
    {
        // ★ 追加: CardPolicy@update を実行
        $this->authorize('update', $card);

        // ★ Validator::make ブロックをすべて削除

        // データを更新
        // ★ validated() を使うと、バリデーション済みのデータだけが取得できるのでより安全です
        $card->update($request->validated());

        // 更新されたカードデータを返す
        return response()->json($card);
    }
    /**
     * カードを削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(Card $card)
    {
        // ★ 追加: CardPolicy@delete を実行
        $this->authorize('delete', $card);

        // ★ 1. 削除前に通知に必要な情報を退避
        $cardTitle = $card->title;
        $listName = $card->list ? $card->list->title : 'Unknown List';
        $boardId = $card->list ? $card->list->board_id : null;
        $deleter = Auth::user();

        // カードをDBから削除
        $card->delete();

        // ★ 3. ボードIDが取得できていればイベント発火
        if ($boardId) {
            CardDeleted::dispatch($cardTitle, $listName, $boardId, $deleter);
        }

        // 成功したら、 204 (No Content) ステータスを返す
        // (レスポンスボディは空)
        return response()->noContent(); 
    }
    /**
     * カードの順序と所属リストを一括更新する (API)
     */
    public function updateOrder(CardOrderRequest $request)
    {
        // ★ Validator::make ブロックを削除

        // TODO: 認可(Policy)チェック

        // トランザクション開始
        try {
            DB::beginTransaction();

            $lists = $request->validated()['lists'] ?? [];
            $movedCardsInfo = []; // ★ 移動したカードの情報を一時保存する配列

            foreach ($lists as $listData) {
                $listId = $listData['id'];
                
                // 移動先のリスト情報を取得
                $targetList = BoardList::find($listId);
                if (!$targetList) continue;

                foreach ($listData['cards'] as $index => $cardId) {
                    // 現在のカード情報を取得
                    $card = Card::find($cardId);
                    
                    if ($card) {
                        // ★ リストが変更されているかチェック (移動判定)
                        if ($card->board_list_id !== $listId) {
                            // 移動前のリスト名を取得
                            $fromListName = $card->list ? $card->list->title : 'Unknown List';
                            
                            // イベント発火用に情報を保存 (コミット後に送信)
                            $movedCardsInfo[] = [
                                'card' => $card,
                                'from' => $fromListName,
                                'to' => $targetList->title,
                                'mover' => Auth::user(),
                            ];
                        }

                        // カードの 'order' と 'board_list_id' を更新
                        $card->update([
                            'order' => $index,
                            'board_list_id' => $listId
                        ]);
                    }
                }
            }

            DB::commit(); // 成功したらコミット

            // ★ コミット成功後、移動イベントを一括発火
            foreach ($movedCardsInfo as $info) {
                CardMoved::dispatch(
                    $info['card'],
                    $info['from'],
                    $info['to'],
                    $info['mover']
                );
            }

            return response()->json(['message' => 'Card order updated successfully.']);

        } catch (\Exception $e) {
            DB::rollBack(); // エラーが発生したらロールバック
            report($e);
            return response()->json(['message' => 'An error occurred while updating card order.'], 500);
        }
    }
}
