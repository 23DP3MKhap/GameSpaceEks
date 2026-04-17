<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Review extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'title',
        'content',
        'rating',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function game() {
        return $this->belongsTo(Game::class);
    }
}
