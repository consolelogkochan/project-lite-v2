<?php

namespace App\Http\Controllers;

use App\Models\Card; 
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Validator;
use App\Models\User;
// use Illuminate\Validation\Rule;
use App\Events\AttachmentUploaded;
use App\Http\Requests\AttachmentStoreRequest;
use App\Http\Requests\AttachmentReviewRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AttachmentController extends Controller
{
    use AuthorizesRequests;
    /**
     * 新しい添付ファイルをアップロードして保存する (API)
     */
    public function store(AttachmentStoreRequest $request, Card $card)
    {
        // ファイル添付 = カードの更新権限が必要
        $this->authorize('update', $card);

        // 1. ファイルをストレージに保存
        $file = $request->file('file');
        
        // 'storage/app/public/attachments' フォルダに、ランダムなファイル名で保存
        // (public ディスク = config/filesystems.php で定義済み)
        $path = $file->store('attachments', 'public');
        
        // 2. データベースにメタデータを保存
        $attachment = $card->attachments()->create([
            'user_id' => Auth::id(),
            'file_path' => $path, // 例: "attachments/aJk...png"
            'file_name' => $file->getClientOriginalName(), // 元のファイル名
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'review_status' => 'pending',
        ]);

        // 3. 成功応答 (load('user') でアップロード者情報も返す)
        $attachment->load('user');

        // ★ ここでイベントを発火
        AttachmentUploaded::dispatch($attachment);
        
        // 201 Created でJSONを返す (file_url アクセサも自動で含まれる)
        return response()->json($attachment, 201);
    }

    /**
     * 添付ファイルのレビュー状況を更新する (API)
     * ★ このメソッドを追加
     */
    public function updateReviewStatus(AttachmentReviewRequest $request, Attachment $attachment)
    {
        // AttachmentPolicy@update
        $this->authorize('update', $attachment);

        // データを更新
        $attachment->update([
            'review_status' => $request->review_status,
        ]);

        // 更新された添付ファイルデータを返す (user情報も念のため)
        $attachment->load('user');
        return response()->json($attachment);
    }

    /**
     * 添付ファイルを削除する (API)
     * ★ このメソッドを追加
     */
    public function destroy(Request $request, Attachment $attachment)
    {
        // AttachmentPolicy@delete
        $this->authorize('delete', $attachment);

        // 1. ストレージからファイルを削除
        // ('public' ディスク = storage/app/public)
        Storage::disk('public')->delete($attachment->file_path);

        // 2. データベースからレコードを削除
        $attachment->delete();

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }
}
