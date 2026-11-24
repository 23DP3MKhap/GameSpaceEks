<?php

namespace App\Http\Controllers;
use App\Models\Game;
use App\Services\IgdbClient;
use Illuminate\Http\Request;



class GameController extends Controller
{
    public function getgames(Request $request, IgdbClient $igdb){
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'genres' => 'nullable|array',
            'genres.*' => 'integer',
            'platforms' => 'nullable|array',
            'platforms.*' => 'integer',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0',
        ]);

        $rules = [];
        $search = $validated['search'] ?? null;
        $genres = $validated['genres'] ?? [];
        $platforms = $validated['platforms'] ?? [];
        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        $httpBody = "fields id, name, cover.url, genres.name, platforms.name, involved_companies.company.name, involved_companies.developer, involved_companies.publisher; sort total_rating_count desc; limit $limit; offset $offset;";

        $dbgamesids = Game::pluck('id');
        if ($dbgamesids->isNotEmpty()) {
            $rules[] = "id != (" . $dbgamesids->join(',') . ")";
        }

        if ($search) {
            $search = str_replace(['\\', '"'], ['\\\\', '\\"'], $search);
            $rules[] = "name ~ *\"$search\"*";
        }

        if (!empty($genres)) {
            $rules[] = "genres = (" . implode(',', $genres) . ")";
        }

        if (!empty($platforms)) {
            $rules[] = "platforms = (" . implode(',', $platforms) . ")";
        }

        if (!empty($rules)) {
            $httpBody .= " where " . implode(' & ', $rules) . ";";
        }

        $gameresponse = $igdb->query('games', $httpBody);

        return collect($gameresponse)->map(function ($game){
            $companies = IgdbClient::companies($game);

            return [
                'id'        => $game['id'],
                'source'    => 'igdb',
                'title'     => $game['name'],
                'image'     => Game::coverUrl($game) ?? 'https://placehold.co/600x400',
                'genre'     => collect($game['genres'] ?? [])->pluck('name')->join(', ') ?: 'Nav zināms',
                'platforms' => collect($game['platforms'] ?? [])->pluck('name')->join(', ') ?: 'Nav zināms',
                'developer' => $companies['developer'] ?? 'Nav zināms',
                'publisher' => $companies['publisher'] ?? 'Nav zināms',
            ];
        });
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



