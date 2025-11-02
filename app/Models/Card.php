<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BoardList;

class Card extends Model
{
    use HasFactory;
    
    /**
     * このカードが属するリスト
     */
    public function list()
    {
        return $this->belongsTo(BoardList::class, 'board_list_id');
    }
}
