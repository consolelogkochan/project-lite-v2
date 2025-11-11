<?php

namespace App\Models;

use App\Models\User; // ← この行を追加！
use Illuminate\Database\Eloquent\Factories\HasFactory; // ← この行を追加！
use Illuminate\Database\Eloquent\Model;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;

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

    /**
     * このボードに所属するすべてのユーザー（中間テーブル経由）
     * ★ 2. このメソッドを追加
     */
    public function users()
    {
        // 'board_user' 中間テーブルを経由して User モデルにアクセス
        return $this->belongsToMany(User::class, 'board_user')
        ->withTimestamps()
        ->withPivot('role');
        
    }

    /**
     * このボードが持つすべてのラベル
     * ★ 2. このメソッドを追加
     */
    public function labels()
    {
        // ボードは多くのラベルを持つ
        return $this->hasMany(Label::class);
    }
}
