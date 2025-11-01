<?php

namespace App\Models;

use App\Models\User; // ← この行を追加！
use Illuminate\Database\Eloquent\Factories\HasFactory; // ← この行を追加！
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory; // ← この行を追加！
    
    /**
     * このボードを所有するユーザー（作成者）
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * このボードに所属するすべてのメンバー（中間テーブル経由）
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'board_user')->withTimestamps();
    }
}
