<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Comment;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use App\Events\CommentPosted;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

// ★ 追加
use App\Http\Requests\CommentStoreRequest;
use App\Http\Requests\CommentUpdateRequest;

class CommentController extends Controller
{
    use AuthorizesRequests;
    /**
     * 新しいコメントを保存する (API)
     */
    public function store(CommentStoreRequest $request, Card $card)
    {
        // コメント投稿 = カードの閲覧権限があればOKとみなす
        // (あるいは 'update' 権限でも可)
        $this->authorize('view', $card);

        // ★ Validator ブロックを削除

        // コメントを作成
        $comment = $card->comments()->create([
            'content' => $request->content,
            'user_id' => Auth::id(), // 認証済みユーザーのID
        ]);

        // ★ 修正: 'user' のみロード（UIの即時反映に必要）
        $comment->load('user');

        // ★ 2. イベントを発火させる
        //    (リスナー 'SendCommentNotification' がこれをキャッチする)
        CommentPosted::dispatch($comment);

        // 201 Created でJSONを返す
        return response()->json($comment, 201);
    }
    /**
     * コメントを更新する (API)
     * ★ このメソッドを追加
     */
    public function update(CommentUpdateRequest $request, Comment $comment)
    {
        // CommentPolicy@update (本人のみ)
        $this->authorize('update', $comment);

        // ★ Validator ブロックを削除

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
        // CommentPolicy@delete (本人 or 管理者)
        $this->authorize('delete', $comment);

        // コメントをDBから削除
        $comment->delete();

        // 成功したら、 204 (No Content) ステータスを返す
        return response()->noContent();
    }
}
