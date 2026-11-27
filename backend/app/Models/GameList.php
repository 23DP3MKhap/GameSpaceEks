<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GameList extends Model
{
    public const MAX_GAMES = 100;

    protected $table = 'lists';

    protected $fillable = ['user_id', 'title', 'description', 'is_public'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'list_games', 'list_id')
            ->withPivot('position', 'note', 'added_at');
    }
}
