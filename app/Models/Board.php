<?php

namespace App\Models;

use App\Models\User; // ← この行を追加！
use Illuminate\Database\Eloquent\Factories\HasFactory; // ← この行を追加！
use Illuminate\Database\Eloquent\Model;
use App\Models\BoardList;
use App\Models\Card;

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

    /**
     * このボードに属するすべてのリスト
     */
    public function lists()
    {
        return $this->hasMany(BoardList::class)->orderBy('order');
    }

    /**
     * このボードに属するすべてのカード（リストを経由）
     */
    public function cards()
    {
        return $this->hasManyThrough(Card::class, BoardList::class, 'board_id', 'board_list_id');
    }
}
