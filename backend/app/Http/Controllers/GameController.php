<?php

namespace App\Http\Controllers;
use App\Exceptions\IgdbException;
use App\Models\Game;
use App\Services\IgdbClient;
use Illuminate\Http\Request;



class GameController extends Controller
{
    private const PAGE_SIZE = 40;
    private const IGDB_FIELDS = 'fields name, cover.url, first_release_date, genres.name; sort total_rating_count desc;';

    public function catalog(Request $request, IgdbClient $igdb){
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'genres' => 'nullable|array',
            'genres.*' => 'integer',
            'platforms' => 'nullable|array',
            'platforms.*' => 'integer',
            'page' => 'nullable|integer|min:1',
        ]);

        $search = $validated['search'] ?? null;
        $genres = $validated['genres'] ?? [];
        $platforms = $validated['platforms'] ?? [];
        $start = (($validated['page'] ?? 1) - 1) * self::PAGE_SIZE;

        if (!$search && !$genres && !$platforms) {
            try {
                return $this->popularGames($igdb, $start);
            } catch (IgdbException) {
                [$dbTotal, $dbGames] = $this->databaseGames(Game::where('is_hidden', false), $start);
                return ['games' => $dbGames, 'total' => $dbTotal, 'partial' => true];
            }
        }

        $query = Game::where('is_hidden', false);

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($genres) {
            $query->whereHas('genres', fn ($genre) => $genre->whereIn('genre_id', $genres));
        }

        if ($platforms) {
            $query->whereHas('platforms', fn ($platform) => $platform->whereIn('platform_id', $platforms));
        }

        [$dbTotal, $dbGames] = $this->databaseGames($query, $start);

        $rules = [];

        $dbIds = Game::pluck('id');
        if ($dbIds->isNotEmpty()) {
            $rules[] = "id != (" . $dbIds->join(',') . ")";
        }

        if ($search) {
            $search = str_replace(['\\', '"'], ['\\\\', '\\"'], $search);
            $rules[] = "name ~ *\"$search\"*";
        }

        if ($genres) {
            $rules[] = "genres = (" . implode(',', $genres) . ")";
        }

        if ($platforms) {
            $rules[] = "platforms = (" . implode(',', $platforms) . ")";
        }

        $where = "where " . implode(' & ', $rules) . ";";
        $limit = self::PAGE_SIZE - $dbGames->count();
        $offset = max(0, $start - $dbTotal);

        $body = "query games/count \"count\" { $where };";
        if ($limit > 0) {
            $body .= " query games \"games\" { " . self::IGDB_FIELDS . " $where limit $limit; offset $offset; };";
        }

        try {
            $response = collect($igdb->query('multiquery', $body))->keyBy('name');
        } catch (IgdbException) {
            return ['games' => $dbGames, 'total' => $dbTotal, 'partial' => true];
        }

        $igdbGames = collect($response['games']['result'] ?? [])->map(fn ($game) => $this->igdbCard($game));

        return [
            'games' => $dbGames->concat($igdbGames),
            'total' => $dbTotal + $response['count']['count'],
            'partial' => false,
        ];
    }

    private function databaseGames($query, int $start){
        return [
            $query->count(),
            $query->with('genres')
                ->withAvg('userGames as average_rating', 'rating')
                ->orderBy('id')
                ->skip($start)
                ->take(self::PAGE_SIZE)
                ->get()
                ->map(fn ($game) => $this->databaseCard($game)),
        ];
    }

    private function popularGames(IgdbClient $igdb, int $offset){
        $hiddenIds = Game::where('is_hidden', true)->pluck('id');
        $where = $hiddenIds->isNotEmpty() ? "where id != (" . $hiddenIds->join(',') . ");" : '';

        $response = collect($igdb->query('multiquery', "query games/count \"count\" { $where };"
            . " query games \"games\" { " . self::IGDB_FIELDS . " $where limit " . self::PAGE_SIZE . "; offset $offset; };"))->keyBy('name');

        $igdbGames = collect($response['games']['result']);
        $dbGames = Game::with('genres')
            ->withAvg('userGames as average_rating', 'rating')
            ->whereIn('id', $igdbGames->pluck('id'))
            ->get()
            ->keyBy('id');

        return [
            'games' => $igdbGames->map(fn ($game) => isset($dbGames[$game['id']])
                ? $this->databaseCard($dbGames[$game['id']])
                : $this->igdbCard($game)),
            'total' => $response['count']['count'],
            'partial' => false,
        ];
    }

    private function databaseCard(Game $game){
        return [
            'id' => $game->id,
            'title' => $game->title,
            'image' => $game->cover_url ?? "https://placehold.co/600x400",
            'year' => $game->release_date ? substr($game->release_date, 0, 4) : null,
            'genres' => $game->genres->pluck('name')->take(3),
            'average_rating' => $game->average_rating,
        ];
    }

    private function igdbCard(array $game){
        return [
            'id' => $game['id'],
            'title' => $game['name'],
            'image' => Game::coverUrl($game) ?? "https://placehold.co/600x400",
            'year' => isset($game['first_release_date']) ? date('Y', $game['first_release_date']) : null,
            'genres' => collect($game['genres'] ?? [])->pluck('name')->take(3),
            'average_rating' => null,
        ];
    }

    public function show(int $id, IgdbClient $igdb){
        $game = Game::with(['genres', 'platforms'])
            ->withAvg('userGames as average_rating', 'rating')
            ->withCount('userGames as player_count')
            ->find($id);

        if ($game) {
            return [
                'id' => $game->id,
                'title' => $game->title,
                'description' => $game->description,
                'developer' => $game->developer,
                'publisher' => $game->publisher,
                'release_date' => $game->release_date,
                'image' => $game->cover_url,
                'genres' => $game->genres->pluck('name'),
                'platforms' => $game->platforms->pluck('name'),
                'average_rating' => $game->average_rating,
                'player_count' => $game->player_count,
            ];
        }

        $response = $igdb->query('games', "fields name, summary, first_release_date, cover.url, genres.name, platforms.name,
            involved_companies.developer, involved_companies.publisher, involved_companies.company.name; where id = $id;");

        if (empty($response)) {
            return response()->json(['message' => 'Spēle netika atrasta'], 404);
        }

        $igdbGame = $response[0];
        $fields = Game::fromIgdb($igdbGame);

        return [
            'id' => $fields['id'],
            'title' => $fields['title'],
            'description' => $fields['description'],
            'developer' => $fields['developer'],
            'publisher' => $fields['publisher'],
            'release_date' => $fields['release_date'],
            'image' => $fields['cover_url'],
            'genres' => collect($igdbGame['genres'] ?? [])->pluck('name'),
            'platforms' => collect($igdbGame['platforms'] ?? [])->pluck('name'),
            'average_rating' => null,
            'player_count' => 0,
        ];
    }

}



