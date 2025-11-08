<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Checklist;

class ChecklistItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ // ★ 2. fillable を追加
        'checklist_id',
        'content',
        'is_completed',
        'position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [ // ★ 3. is_completed を boolean にキャスト
        'is_completed' => 'boolean',
    ];

    /**
     * このアイテムが属するチェックリスト
     * ★ 4. リレーションを追加
     */
    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
}
