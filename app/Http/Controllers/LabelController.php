<?php

namespace App\Http\Controllers;

use App\Models\Board; 
use App\Models\Label;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Validation\Rule;

class LabelController extends Controller
{
    /**
     * 特定のボードに属するすべてのラベルを取得 (API)
     */
    public function index(Board $board)
    {
        // TODO: 認可チェック (このボードを閲覧する権限があるか)
        
        // 'created_at' の昇順（古い順）で返す
        $labels = $board->labels()->orderBy('created_at', 'asc')->get();
        
        return response()->json($labels);
    }

    /**
     * 新しいラベルを作成して保存する (API)
     */
    public function store(Request $request, Board $board)
    {
        // TODO: 認可チェック (このボードにラベルを作成する権限があるか)

        // バリデーション
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                // ボード内でラベル名が重複しないようにする
                Rule::unique('labels')->where(function ($query) use ($board) {
                    return $query->where('board_id', $board->id);
                }),
            ],
            'color' => 'required|string|max:50', // 例: 'bg-red-500'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

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
    public function update(Request $request, Label $label)
    {
        // TODO: 認可チェック (このボードのラベルを編集する権限があるか)
        $board = $label->board; // ラベルが属するボードを取得

        // バリデーション
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                // ボード内でラベル名が重複しないようにする (自分自身のIDは除く)
                Rule::unique('labels')->where(function ($query) use ($board) {
                    return $query->where('board_id', $board->id);
                })->ignore($label->id), // ★ 自分のIDを無視
            ],
            'color' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // ラベルを更新
        $label->update($request->all());

        // 更新されたラベルを返す (200 OK)
        return response()->json($label);
    }

    /**
     * ラベルを削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(Label $label)
    {
        // TODO: 認可チェック (このボードのラベルを削除する権限があるか)
        
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
        // TODO: 認可チェック
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
        // TODO: 認可チェック

        // 中間テーブル (card_label) からレコードを削除
        $card->labels()->detach($label->id);

        return response()->json([
            'attached' => $card->labels()->pluck('id')
        ]);
    }
}
