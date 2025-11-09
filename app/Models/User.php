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
        return $this->belongsToMany(Board::class, 'board_user')->withTimestamps();
    }

    /**
     * アバターへの公開URLを取得するアクセサ
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) { // ★ "avatar_path" から "avatar" に変更
            // "avatar" (例: "avatars/xyz.jpg") から
            // "storage:link" された公開URL (例: "/storage/avatars/xyz.jpg") を生成
            return Storage::url($this->avatar); // ★ "avatar_path" から "avatar" に変更
        }
        
        return null;
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
