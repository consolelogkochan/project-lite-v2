<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Board; 
use App\Models\Card;

class Label extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ // ★ 2. fillable を追加
        'board_id',
        'name',
        'color',
    ];

    /**
     * このラベルが属するボード
     * ★ 3. リレーションを追加
     */
    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * このラベルが紐付いているカード
     * ★ 3. リレーションを追加
     */
    public function cards()
    {
        // 'card_label' 中間テーブルを経由
        return $this->belongsToMany(Card::class, 'card_label');
    }
}
