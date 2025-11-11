<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Board;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;
use App\Models\Attachment;

class User extends Authenticatable
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
}
