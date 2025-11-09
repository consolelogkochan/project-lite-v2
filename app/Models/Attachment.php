<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // ★ Storageファサード

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'user_id',
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'review_status',
    ];

    // ★ JSONに含めるアクセサ
    protected $appends = ['file_url', 'is_image'];

    /**
     * この添付ファイルが属するカード
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * アップロードしたユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ファイルの公開URLを取得するアクセサ
     */
    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    /**
     * 画像ファイルかどうかを判定するアクセサ
     */
    public function getIsImageAttribute()
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
