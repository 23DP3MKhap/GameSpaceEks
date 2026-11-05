<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGame extends Model
{
    public const STATUSES = ['playing', 'completed', 'planned', 'dropped'];

    const CREATED_AT = 'added_at';
    const UPDATED_AT = null;

    public $incrementing = false;

    protected $fillable = [
        'user_id', 'game_id', 'status', 'rating', 'note'
    ];

     public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
