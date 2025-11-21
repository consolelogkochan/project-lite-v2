<?php

namespace App\Http\Controllers;

use App\Models\Board; 
use App\Models\Label;
use App\Models\Card;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator; 
// use Illuminate\Validation\Rule;
// ★ 1. この行を追加
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

// ★ 追加
use App\Http\Requests\LabelStoreRequest;
use App\Http\Requests\LabelUpdateRequest;

class LabelController extends Controller
{
    // ★ 2. クラス内でトレイトを使用
    use AuthorizesRequests;
    /**
     * 特定のボードに属するすべてのラベルを取得 (API)
     */
    public function index(Board $board)
    {
        // ボード閲覧権限
        $this->authorize('view', $board);
        
        // 'created_at' の昇順（古い順）で返す
        $labels = $board->labels()->orderBy('created_at', 'asc')->get();
        
        return response()->json($labels);
    }

    /**
     * 新しいラベルを作成して保存する (API)
     */
    public function store(LabelStoreRequest $request, Board $board)
    {
        // ラベル作成 = ボード更新権限 (メンバーならOK)
        $this->authorize('update', $board); // または view でも良いが update が無難

        // ラベルを作成
        $label = $board->labels()->create([
            'name' => $request->name,
            'color' => $request->color,
        ]);

        // 作成したラベルを 201 Created で返す
        return response()->json($label, 201);
    }

    /**
     * ラベルを更新する (API)
     * ★ このメソッドを追加
     */
    public function update(LabelUpdateRequest $request, Label $label)
    {
        // LabelPolicy@update
        $this->authorize('update', $label);
        
        $board = $label->board; // ラベルが属するボードを取得

        // ラベルを更新
        $label->update($request->validated());

        // 更新されたラベルを返す (200 OK)
        return response()->json($label);
    }

    /**
     * ラベルを削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(Label $label)
    {
        // LabelPolicy@delete
        $this->authorize('delete', $label);
        
        $label->delete();

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }

    /**
     * カードにラベルを割り当てる (API)
     * ★ このメソッドを追加
     */
    public function attachLabel(Card $card, Label $label)
    {
        // カードを編集する権限が必要
        $this->authorize('update', $card);
        // (ユーザーはこのカードとラベルの両方にアクセスできるか？)
        // (カードとラベルが同じボードに属しているか？)
        // if ($card->board->id !== $label->board_id) {
        //     return response()->json(['message' => 'Forbidden'], 403);
        // }

        // 中間テーブル (card_label) にレコードを追加
        // syncWithoutDetaching を使うと、既に存在する場合でもエラーにならない
        $card->labels()->syncWithoutDetaching($label->id);

        // 成功したら、割り当てられたラベルIDのリストを返す
        // (または 204 No Content でも良い)
        return response()->json([
            'attached' => $card->labels()->pluck('id') // 現在の全ラベルID
        ]);
    }

    /**
     * カードからラベルを解除する (API)
     * ★ このメソッドを追加
     */
    public function detachLabel(Card $card, Label $label)
    {
        // カードを編集する権限が必要
        $this->authorize('update', $card);

        // 中間テーブル (card_label) からレコードを削除
        $card->labels()->detach($label->id);

        return response()->json([
            'attached' => $card->labels()->pluck('id')
        ]);
    }
}
