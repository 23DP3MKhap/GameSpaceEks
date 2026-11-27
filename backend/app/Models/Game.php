<?php

namespace App\Models;

use App\Services\IgdbClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
class Game extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id', 'title', 'developer', 'publisher',
        'release_date', 'description', 'cover_url'
    ];

    protected function casts(): array
    {
        return [
            'average_rating' => 'float',
            'is_hidden' => 'boolean',
        ];
    }

    public static function coverUrl(array $igdbGame): ?string
    {
        if (empty($igdbGame['cover']['url'])) {
            return null;
        }
        return 'https:' . str_replace('t_thumb', 't_cover_big', $igdbGame['cover']['url']);
    }

    public static function fromIgdb(array $igdbGame): array
    {
        $companies = IgdbClient::companies($igdbGame);

        return [
            'id' => $igdbGame['id'],
            'title' => self::cut($igdbGame['name'], 100),
            'description' => self::cut($igdbGame['summary'] ?? null, 1000),
            'developer' => self::cut($companies['developer'], 50),
            'publisher' => self::cut($companies['publisher'], 50),
            'release_date' => !empty($igdbGame['first_release_date']) ? date('Y-m-d', $igdbGame['first_release_date']) : null,
            'cover_url' => self::coverUrl($igdbGame),
        ];
    }

    public static function importFromIgdb(IgdbClient $igdb, int $id): ?self
    {
        $response = $igdb->query('games', "fields name, summary, first_release_date, cover.url, involved_companies.developer,
            involved_companies.publisher, involved_companies.company.name, genres, platforms; where id = $id;");

        if (empty($response)) {
            return null;
        }

        $igdbGame = $response[0];
        $game = self::updateOrCreate(['id' => $igdbGame['id']], self::fromIgdb($igdbGame));
        $game->genres()->sync(Genre::whereIn('id', $igdbGame['genres'] ?? [])->pluck('id'));
        $game->platforms()->sync(Platform::whereIn('id', $igdbGame['platforms'] ?? [])->pluck('id'));

        return $game;
    }

    private static function cut(?string $text, int $length): ?string
    {
        return $text === null ? null : Str::limit($text, $length, '');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'game_genres');
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'game_platforms');
    }

    public function userGames(): HasMany
    {
        return $this->hasMany(UserGame::class);
    }
}
