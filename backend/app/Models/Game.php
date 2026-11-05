<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Game extends Model
{   
    public $incrementing = false;

    protected $fillable = [
        'id', 'title', 'developer', 'publisher',
        'release_date', 'description', 'cover_url', 'rating'
    ];

    public static function coverUrl(array $igdbGame): ?string
    {
        if (empty($igdbGame['cover']['url'])) {
            return null;
        }
        return 'https:' . str_replace('t_thumb', 't_cover_big', $igdbGame['cover']['url']);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'game_genres');
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'game_platforms');
    }
}
