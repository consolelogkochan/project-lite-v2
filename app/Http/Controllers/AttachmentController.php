<?php

namespace App\Http\Controllers;

use App\Models\Card; 
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Events\AttachmentUploaded;

class AttachmentController extends Controller
{
    /**
     * 新しい添付ファイルをアップロードして保存する (API)
     */
    public function store(Request $request, Card $card)
    {
        // TODO: 認可チェック
        
        // バリデーション
        $validator = Validator::make($request->all(), [
            // 'file' という名前でファイルが送信されているか
            'file' => [
                'required',
                'file',
                'max:10240', // 最大 10MB (10 * 1024 KB)
                // (必要に応じて MIME タイプの指定も可能)
                // 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

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
    public function updateReviewStatus(Request $request, Attachment $attachment)
    {
        // TODO: 認可チェック (レビューする権限があるか)
        
        // バリデーション
        $validator = Validator::make($request->all(), [
            'review_status' => [
                'required',
                'string',
                // 'pending', 'approved', 'rejected' のいずれかであることを強制
                Rule::in(['pending', 'approved', 'rejected']),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

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
        // TODO: 認可チェック
        // (簡易チェック: アップロード者 or ボードオーナー)
        $boardOwnerId = $attachment->card->list->board->owner_id;
        if (Auth::id() !== $attachment->user_id && Auth::id() !== $boardOwnerId) {
             return response()->json(['message' => 'Forbidden'], 403);
        }

        // 1. ストレージからファイルを削除
        // ('public' ディスク = storage/app/public)
        Storage::disk('public')->delete($attachment->file_path);

        // 2. データベースからレコードを削除
        $attachment->delete();

        // 成功したら 204 No Content を返す
        return response()->noContent();
    }
}
