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
        'start_date', 
        'end_date',
        'reminder_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ // ★ このプロパティを追加
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'reminder_at' => 'datetime',
    ];

    /**
     * このカードが属するリスト
     */
    public function list()
    {
        return $this->belongsTo(BoardList::class, 'board_list_id');
    }

    /**
     * このカードが属するボード（listテーブル経由）
     * ★ このメソッドを追加
     */
    public function board()
    {
        // Card -> hasOne(BoardList::class) -> hasOne(Board::class)
        // (BoardListモデルで 'board_id' を 'board' として定義済みなら使える)
        // ※BoardListモデルの 'board' リレーション (belongsTo) が必要
        return $this->hasOneThrough(
            Board::class,
            BoardList::class,
            'id', // BoardList (中間) のキー
            'id', // Board (最終) のキー
            'board_list_id', // Card (起点) のキー
            'board_id' // BoardList (中間) の (Boardへの) キー
        );
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
