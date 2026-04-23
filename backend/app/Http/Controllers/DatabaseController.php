<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\Review;
use App\Models\Collection;
use App\Models\Genre;
use App\Models\Platform;

class DatabaseController extends Controller
{

    // Create metodes 

    public function addGame(Request $request){
        $validated = $request->validate([
        'igdb_id' => 'required|integer'
    ]);

    $igdbId = $validated['igdb_id'];

    $existingGame = Game::where('id', $igdbId)->first();
    if ($existingGame) {
        return;
    }

    $client = (Http::post("https://id.twitch.tv/oauth2/token", 
        ["client_id" => config('services.twitch.igdbclientid'), 
        "client_secret" => config('services.twitch.igdbclientsecret'),
        "grant_type" => 'client_credentials']))->json();
        
    $token = $client['access_token'];

    $response = (Http::withBody("fields name, summary, first_release_date, aggregated_rating, cover.url, involved_companies.developer,
        involved_companies.publisher, involved_companies.company.name, genres, platforms; where id = $igdbId;")->withHeaders([
        "Client-ID" => config('services.twitch.igdbclientid'),
        "Authorization" => "Bearer "  . $token])->post("https://api.igdb.com/v4/games"));

        if ($response->failed() || empty($response->json())) {
        return response()->json(['error']);
    }

    $externalData = $response->json()[0];

    if (isset($externalData['involved_companies'])) {
    foreach ($externalData['involved_companies'] as $item) {
        if ($item['developer'] === true) {
            $dev = $item['company']['name'];
        }
        
        if ($item['publisher'] === true) {
            $pub = $item['company']['name'];
        }
    }
}

    $game = Game::create([
        'id'      => $externalData['id'],
        'name'         => $externalData['name'],
        'description'  => $externalData['summary'] ?? null,
        'developer'    => $dev ?? null,
        'publisher'    => $pub ?? null,
        'release_date' => isset($externalData['first_release_date']) ? date('Y-m-d', $externalData['first_release_date']) : null,
        'cover_url'    => isset($externalData['cover']) ? str_replace('t_thumb', 't_cover_big', $externalData['cover']['url']) : null,
        'rating'       => $externalData['aggregated_rating'] ?? null,
    ]);


if (isset($externalData['genres']) && is_array($externalData['genres'])) {
    foreach ($externalData['genres'] as $genreData) {
        $genreId = is_array($genreData) ? ($genreData['id'] ?? null) : $genreData;

        if ($genreId) {
            $game->genres()->attach((int)$genreId);
        }
    }
}

if (isset($externalData['platforms']) && is_array($externalData['platforms'])) {
    foreach ($externalData['platforms'] as $platformData) {
        $platformId = is_array($platformData) ? ($platformData['id'] ?? null) : $platformData;

        if ($platformId) {
            $game->platforms()->attach((int)$platformId);
        }
    }
}
    }


    public function addReview(Request $request){
        $validated = $request->validate([
            'game_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'rating' => 'required|integer|min:1|max:10',
        ]);

        Review::updateOrCreate(
        ['user_id' => $request->user()->id, 'game_id' => $validated['game_id']],

        [
            'title'   => $validated['title'],
            'content' => $validated['content'],
            'rating'  => $validated['rating'],
        ]
        );  
        
    }

    public function deleteReview(Request $request){
        $request->validate(['game_id' => 'required|integer']);
        $userId = auth()->id();
        Review::where('game_id', $request->game_id)->where('user_id', $userId)->delete();
    }

    public function addToCollection(Request $request){
        $validated = $request->validate([
        'game_id'    => 'required|integer',
        'status'     => 'required|in:playing,completed,planned,dropped',
        'user_score' => 'nullable|integer|min:1|max:10',
        'notes'      => 'nullable|string|max:1000',
    ]);

    $collectionItem = Collection::updateOrCreate(
        [
            'user_id' => $request->user()->id, 
            'game_id' => $validated['game_id']
        ],
        [
            'status'     => $validated['status'],
            'user_score' => $validated['user_score'] ?? null,
            'notes'      => $validated['notes'] ?? null,
        ]);
    }

    public function removeFromCollection(Request $request){
        $request->validate(['game_id' => 'required|integer']);
        $userId = auth()->id();
        Collection::where('game_id', $request->game_id)->where('user_id', $userId)->delete();
    }

    public function updateUser(Request $request){
        $user = $request->user(); 

        $validated = $request->validate([
            'username'   => 'sometimes|string|max:255|unique:users,name,' . $user->id,
            'bio'        => 'sometimes|nullable|string|max:500',
            'avatar_url' => 'sometimes|nullable|url|max:2048',
            'password'   => 'sometimes|nullable|string|min:8|max:255',
        ]);

        if (isset($validated['username'])) {
            $user->name = $validated['username'];
        }

        if (array_key_exists('bio', $validated)) {
            $user->bio = $validated['bio'];
        }

        if (array_key_exists('avatar_url', $validated)) {
            $user->avatar = $validated['avatar_url'];
        }

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
    }
    


    // Get metodes

    public function getReviews(Request $request){
        $request->validate(['game_id' => 'required|integer']);
        $reviews = Review::where('game_id', $request->game_id)->with('user')->latest()->get();
        return response()->json($reviews);
    }

    public function getGenres(){
        $genres = Genre::orderBy("name", "asc")->get();

        return response()->json($genres);
    }

    public function getPlatforms(){
        $platforms = Platform::orderBy("name", "asc")->get();

        return response()->json($platforms);
    }

    public function getGames(Request $request)
    {
        $search    = $request->search;
        $genres    = $request->genres;
        $platforms = $request->platforms;
        $offset = $request->offset ?? 0;

        $query = Game::with(['genres', 'platforms']);

        $query->skip($offset);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }


        if (!empty($genres) && is_array($genres)) {
            $query->whereHas('genres', function ($q) use ($genres) {
                $q->whereIn('genre_id', $genres);
            });
        }

        if (!empty($platforms) && is_array($platforms)) {
        $query->whereHas('platforms', function ($q) use ($platforms) {
            $q->whereIn('platform_id', $platforms);
        });
        }

        $games = $query->take(24)->get();
        return $games->map(function ($game) {
        return [
            'id'        => $game->id,
            'name'      => $game->name,
            'cover'     => ['url' => $game->cover_url],
            'genres'    => $game->genres->map(fn($g) => ['name' => $g->name]),
            'platforms' => $game->platforms->map(fn($p) => ['name' => $p->name]),
        ];
        });
    }

    public function getCollection(Request $request){
    $request->validate(['user_id' => 'required|integer']);

    $collection = Collection::where('user_id', $request->user_id)
        ->with('game')
        ->get();

    return response()->json(
        $collection->map(function ($item) {
            return [
                'id'         => $item->id,
                'game'       => [
                    'id'    => $item->game->id,
                    'name'  => $item->game->name,
                    'image' => $item->game->cover_url
                        ? 'https:' . str_replace('t_thumb', 't_cover_big', $item->game->cover_url)
                        : 'https://placehold.co/60x80/111/444?text=' . urlencode($item->game->name),
                ],
                'status'     => $item->status,
                'user_score' => $item->user_score,
                'notes'      => $item->notes ?? '',
            ];
        })
    );
    }

    

    public function checkCollection(Request $request){
    $collection = Collection::where('user_id', auth()->id())
        ->where('game_id', $request->game_id)
        ->first();

    return [
        'exists' => $collection,
        'status' => $collection?->status,
        'user_score' => $collection?->user_score,
        'notes' => $collection?->notes,
    ];
}


    
}