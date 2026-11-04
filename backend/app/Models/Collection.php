<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    public const STATUSES = ['Spēlēju', 'Pabeigta', 'Plānots', 'Pārtraukts'];

    protected $fillable = [
        'user_id', 'game_id', 'status', 'user_score', 'notes'
    ];

     public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
