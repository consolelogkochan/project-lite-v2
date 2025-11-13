<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BoardList;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Checklist;
use App\Models\Attachment;
use App\Models\User;

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
        'reminder_sent',
        'cover_image_id',
        'is_completed',
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
        'is_completed' => 'boolean',
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

    /**
     * このカードに紐付いているラベル
     * ★ 2. このメソッドを追加
     */
    public function labels()
    {
        // 'card_label' 中間テーブルを経由
        return $this->belongsToMany(Label::class, 'card_label');
    }

    /**
     * このカードに紐付いているチェックリスト
     * ★ 2. このメソッドを追加
     */
    public function checklists()
    {
        // 通常、チェックリストは作成順に表示する
        return $this->hasMany(Checklist::class)->orderBy('created_at');
    }

    /**
     * このカードに添付されたファイル
     * ★ このメソッドを追加
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class)->latest();
    }

    /**
     * このカードのカバー画像 (Attachmentモデル)
     * ★ 2. このリレーションを追加
     */
    public function coverImage()
    {
        // cover_image_id (Card) が attachments.id (Attachment) を参照
        return $this->belongsTo(Attachment::class, 'cover_image_id');
    }

    /**
     * このカードに割り当てられているユーザー（担当者）
     * ★ このメソッドを追加
     */
    public function assignedUsers()
    {
        // 'card_user' 中間テーブルを経由
        // (Boardの'users'と区別するため 'assignedUsers' という名前にします)
        return $this->belongsToMany(User::class, 'card_user');
    }
}
