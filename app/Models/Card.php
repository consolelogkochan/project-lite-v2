<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BoardList;
use App\Models\Comment;

class Card extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'board_list_id',
        'title',
        'order',
        'description',
    ];

    /**
     * このカードが属するリスト
     */
    public function list()
    {
        return $this->belongsTo(BoardList::class, 'board_list_id');
    }

    /**
     * このカードに属するコメント（最新順）
     * ★ このメソッドを追加
     */
    public function comments()
    {
        // 'created_at' の降順（新しいものが先）で取得
        return $this->hasMany(Comment::class)->latest();
    }
}
