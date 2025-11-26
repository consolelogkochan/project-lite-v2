<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Board;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;
use App\Models\Attachment;
use App\Models\Card;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'status',
        'notify_on_comment',
        'notify_on_attachment',
        'notify_on_due_date',
        'notify_on_card_move',
        'notify_on_card_created', // ★ 追加
        'notify_on_card_deleted', // ★ 追加
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_on_comment' => 'boolean',
            'notify_on_attachment' => 'boolean',
            'notify_on_due_date' => 'boolean',
            'notify_on_card_move' => 'boolean',
            'notify_on_card_created' => 'boolean', // ★ 追加
            'notify_on_card_deleted' => 'boolean', // ★ 追加
            'is_admin' => 'boolean',
        ];
    }

    /**
     * The attributes that should be appended to arrays.
     *
     * @var array
     */
    protected $appends = [ // ★ 2. このプロパティを追加
        'avatar_url',
    ];

    /**
     * ユーザーが所有するボード（作成者であるボード）
     */
    public function ownedBoards()
    {
        return $this->hasMany(Board::class, 'owner_id');
    }

    /**
     * ユーザーが所属するすべてのボード（中間テーブル経由）
     */
    public function boards()
    {
        return $this->belongsToMany(Board::class, 'board_user')
        ->withTimestamps()
        ->withPivot('role');
    }

    /**
     * アバターへの公開URLを取得するアクセサ
     */
    public function getAvatarUrlAttribute(): ?string
    {
        // 1. $this->avatar (DBの値) が null でも安全なように ?? '' を使い、
        //    trim() で前後の空白を削除
        $avatarPath = trim($this->avatar ?? '');

        // 2. $avatarPath が「空文字」になった場合
        //    (NULL, "", " " はすべてここで false になる)
        if ($avatarPath === '') {
            return null;
        }
        
        // 3. 有効な文字列パスが存在する場合のみ、URLを生成
        return Storage::url($avatarPath);
    }

    /**
     * このユーザーがアップロードした添付ファイル
     * ★ このメソッドを追加
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * このユーザーが割り当てられているカード
     * ★ このメソッドを追加
     */
    public function assignedCards()
    {
        // 'card_user' 中間テーブルを経由
        return $this->belongsToMany(Card::class, 'card_user');
    }

    // ★ 追加: このユーザーが登録に使用した招待コード
    public function invitationCode()
    {
        return $this->hasOne(InvitationCode::class);
    }

    
}
