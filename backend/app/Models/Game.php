<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Game extends Model
{   
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'developer', 'publisher', 
        'release_date', 'description', 'cover_url', 'rating'
    ];

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'game_genre');
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'game_platform');
    }

    public function reviews(){
    return $this->hasMany(Review::class, 'game_id', 'id');
    }  
}
