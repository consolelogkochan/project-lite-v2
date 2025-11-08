<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Card; 
use App\Models\ChecklistItem;

class Checklist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ // ★ 2. fillable を追加
        'card_id',
        'title',
    ];

    /**
     * このチェックリストが属するカード
     * ★ 3. リレーションを追加
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * このチェックリストが持つアイテム（position順）
     * ★ 3. リレーションを追加
     */
    public function items()
    {
        // 'position' カラムの昇順でアイテムを取得
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }
}
