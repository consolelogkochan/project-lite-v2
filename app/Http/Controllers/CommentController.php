<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    /**
     * 新しいコメントを保存する (API)
     */
    public function store(Request $request, Card $card)
    {
        // TODO: ここに「このカードにコメントする権限があるか」の
        // 認可(Policy)チェックを将来追加する

        // バリデーション
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // コメントを作成
        $comment = $card->comments()->create([
            'content' => $request->content,
            'user_id' => Auth::id(), // 認証済みユーザーのID
        ]);

        // 作成したコメントを返す (ユーザー情報も一緒に読み込む)
        $comment->load('user');

        // 201 Created でJSONを返す
        return response()->json($comment, 201);
    }
    /**
     * コメントを更新する (API)
     * ★ このメソッドを追加
     */
    public function update(Request $request, Comment $comment)
    {
        // 認可(Policy)チェック: 認証済みユーザーがこのコメントを更新できるか
        // (自分自身のコメントであるか)
        if (Auth::id() !== $comment->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        // (将来的に Gate::authorize('update', $comment); に置き換える)

        // バリデーション
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // コメントを更新
        $comment->update([
            'content' => $request->content,
        ]);

        // 更新されたコメントを返す (ユーザー情報も一緒に)
        $comment->load('user');

        return response()->json($comment);
    }

    /**
     * コメントを削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(Comment $comment)
    {
        // 認可(Policy)チェック: 認証済みユーザーがこのコメントを削除できるか
        // (自分自身のコメントであるか、またはボードのオーナーであるか等)
        // ※
        // 簡易的な認可チェック（本人のみ）
        if (Auth::id() !== $comment->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        // (将来的に Gate::authorize('delete', $comment); に置き換える)

        // コメントをDBから削除
        $comment->delete();

        // 成功したら、 204 (No Content) ステータスを返す
        return response()->noContent();
    }
}
