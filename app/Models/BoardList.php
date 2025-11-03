<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Board;
use App\Models\Card;

class BoardList extends Model
{
    use HasFactory;

    protected $table = 'lists';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'board_id',
        'order',
    ];
    
    /**
     * このリストが属するボード
     */
    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * このリストに属するすべてのカード
     */
    public function cards()
    {
        return $this->hasMany(Card::class)->orderBy('order');
    }
}
