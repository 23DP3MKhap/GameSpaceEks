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
                'name'      => $game['name'],
                'image'     => !empty($game['cover']['url']) ? 'https:' . str_replace('t_thumb', 't_cover_big', $game['cover']['url']) : 'https://placehold.co/600x400',
                'genre'     => collect($game['genres'] ?? [])->pluck('name')->join(', ') ?: 'Unknown',
                'platforms' => collect($game['platforms'] ?? [])->pluck('name')->join(', ') ?: 'Unknown',
                'developer' => $companies['developer'] ?? 'Unknown',
                'publisher' => $companies['publisher'] ?? 'Unknown',
            ];
        });
        }

}



