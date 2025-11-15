<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
// ★ use Illuminate\Validation\Rule; // (将来の権限チェックで使うかも)
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Events\CardMoved; // ★ 追加
use Illuminate\Support\Facades\Auth; // (なければ追加)
use App\Events\CardCreated;
use App\Events\CardDeleted;

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
        // TODO: ここに「このカードを閲覧する権限があるか」の
        // 認可(Policy)チェックを将来追加する

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
    public function update(Request $request, Card $card)
    {
        // TODO: ここに「このカードを編集する権限があるか」の
        // 認可(Policy)チェックを将来追加する

        // バリデーション
        $validator = Validator::make($request->all(), [
            // title と description のどちらか一方だけが送られてくる場合があるため、
            // 'sometimes' (リクエストに存在する場合のみ) 'required' (必須) とする
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string', // nullable(nullを許可) に設定
            // ★ 修正点: start_date はシンプルに
            'start_date' => 'sometimes|nullable|date',
            // ★ 修正点: end_date のルールを条件付きにする
            'end_date' => [
                'sometimes', 'nullable', 'date',
                // ★ 修正: start_date "も" end_date "も" filled 
                // (nullでない) 場合にのみ、after_or_equal を適用
                Rule::when($request->filled('start_date') && $request->filled('end_date'), [
                    'after_or_equal:start_date'
                ]),
            ],
            
            'reminder_at' => [
                'sometimes', 'nullable', 'date',
                // ★ 修正: end_date "も" reminder_at "も" filled 
                // (nullでない) 場合にのみ、before_or_equal を適用
                Rule::when($request->filled('end_date') && $request->filled('reminder_at'), [
                    'before_or_equal:end_date'
                ]),
            ],

            'cover_image_id' => [
                'sometimes',
                'nullable',
                'integer',
                // cover_image_id が null でない場合、
                // attachments テーブルに存在し、
                // かつ、その attachment がこのカードに属していることを確認
                Rule::exists('attachments', 'id')->where(function ($query) use ($card) {
                    return $query->where('card_id', $card->id);
                }),
            ],

            'is_completed' => 'sometimes|required|boolean',
            'board_list_id' => 'sometimes|required|integer|exists:lists,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // データを更新
        // $request->all() を使うことで、送られてきたキー(titleまたはdescription)のみを更新
        $card->update($request->all());

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

        // TODO: 認可(Policy)チェック

        // トランザクション開始
        try {
            DB::beginTransaction();

            $lists = $request->input('lists', []);
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
