<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Game;
use App\Models\Review;
use App\Models\Collection;
use App\Models\Genre;
use App\Models\Platform;
use App\Models\User;

class DatabaseController extends Controller
{

    // Create metodes 

    public function addGame(Request $request){
        $validated = $request->validate([
        'igdb_id' => 'required|integer'
    ]);

    $igdbId = $validated['igdb_id'];

    $existingGame = Game::find($igdbId);
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
        return;
    }

    $resData = $response->json()[0];

    $developer = null;
    $publisher = null;

    if (!empty($resData['involved_companies'])) {
        foreach ($resData['involved_companies'] as $company) {
            $companyName = $company['company']['name'] ?? null;
            if (!empty($company['developer']) && $developer === null) {
                $developer = $companyName;
            }
            if (!empty($company['publisher']) && $publisher === null) {
                $publisher = $companyName;
            }
        }
    }

    $game = Game::create([
        'id' => $resData['id'],
        'name' => $resData['name'],
        'description' => $resData['summary'] ?? null,
        'developer' => $dev ?? null,
        'publisher' => $pub ?? null,
        'release_date' => !empty($resData['first_release_date']) ? date('Y-m-d', $resData['first_release_date']) : null,
        'cover_url' => !empty($resData['cover']) ? str_replace('t_thumb', 't_cover_big', $resData['cover']['url']) : null,
        'rating' => $resData['aggregated_rating'] ?? null,
    ]);


if (!empty($resData['genres']) && is_array($resData['genres'])) {
    foreach ($resData['genres'] as $genreData) {
        $genreId = $genreData ?? null;

        if ($genreId) {
            $game->genres()->attach((int)$genreId);
        }
    }
}

if (!empty($resData['platforms']) && is_array($resData['platforms'])) {
    foreach ($resData['platforms'] as $platformData) {
        $platformId = $platformData ?? null;

        if ($platformId) {
            $game->platforms()->attach((int)$platformId);
        }
    }
}
    }


    public function addReview(Request $request){
        $validated = $request->validate([
            'game_id' => 'required|integer',
            'title' => 'required|string|max:20',
            'content' => 'required|string|max:500',
            'rating' => 'required|integer|min:1|max:10',
        ]);

        Review::updateOrCreate(
        ['user_id' => $request->user()->id, 'game_id' => $validated['game_id']],
        [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'rating' => $validated['rating'],
        ]
        );  
    }

    public function deleteReview(Request $request){
        $request->validate(['game_id' => 'required|integer']);
        $userId = $request->user()->id;
        Review::where('game_id', $request->game_id)->where('user_id', $userId)->delete();
    }

    public function addToCollection(Request $request){
        $validated = $request->validate([
        'game_id' => 'required|integer',
        'status' => 'required|in:Spēlēju,Pabeigta,Plānots,Pārtraukts',
        'user_score' => 'nullable|integer|min:1|max:10',
        'notes' => 'nullable|string|max:50',
    ]);

    $collectionItem = Collection::updateOrCreate(
        [
            'user_id' => $request->user()->id, 
            'game_id' => $validated['game_id']
        ],
        [
            'status' => $validated['status'],
            'user_score' => $validated['user_score'],
            'notes'  => $validated['notes'],
        ]);
    }

    public function removeFromCollection(Request $request){
        $request->validate(['game_id' => 'required|integer']);
        $userId = $request->user()->id;
        Collection::where('game_id', $request->game_id)->where('user_id', $userId)->delete();
    }

    public function updateUser(Request $request){
        $user = $request->user(); 
        $validated = $request->validate([
            'username'   => 'sometimes|string|max:10|unique:users,name,' . $user->id,
            'bio'        => 'sometimes|nullable|string|max:50',
            'avatar_url' => 'sometimes|nullable|url|max:2048',
            'password'   => 'sometimes|nullable|string|min:8|max:255',
            'is_private' => 'sometimes|boolean',
        ]);
    
        if (!empty($validated['username'])) {
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
        if (isset($validated['is_private'])) {
            $user->isPrivate = $validated['is_private'];
        }
    
        $user->save();
    }

    public function deleteUser(Request $request){
    $user = $request->user();
    $user->tokens()->delete();
    $user->delete();
    }
    


    // Get metodes

    public function getReviews(Request $request)
{
    $request->validate(['game_id' => 'required|integer']);

    $reviews = Review::join('users', 'reviews.user_id', '=', 'users.id')
        ->where('reviews.game_id', $request->game_id)
        ->select(
            'reviews.id',
            'reviews.user_id',
            'reviews.game_id',
            'reviews.title',
            'reviews.content',
            'reviews.rating',
            'reviews.created_at',
            'users.name as user_name',
            'users.avatar as user_avatar'
        )
        ->latest('reviews.created_at')
        ->get()
        ->map(function ($review) {
            return [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'game_id' => $review->game_id,
                'title' => $review->title,
                'content' => $review->content,
                'rating' => $review->rating,
                'created_at' => $review->created_at->format('Y-m-d'),
                'user' => [
                    'id' => $review->user_id,
                    'name' => $review->user_name,
                    'avatar' => $review->user_avatar,
                ],
            ];
        });

    return $reviews;
}

    public function getGenres(){
        $genres = Genre::orderBy("name", "asc")->get();

        return $genres;
    }

    public function getPlatforms(){
        $platforms = Platform::orderBy("name", "asc")->get();

        return $platforms;
    }

    public function getGames(Request $request)
    {
        $search = $request->search;
        $genres = $request->genres;
        $platforms = $request->platforms;
        $offset = $request->offset ?? 0;

        $rules = Game::with(['genres', 'platforms']);

        if ($search) {
            $rules->where('name', 'like', "%{$search}%");
        }


        if (!empty($genres) && is_array($genres)) {
            $rules->whereHas('genres', 
            function ($rul) use ($genres) {
                $rul->whereIn('genre_id', $genres);
            });
        }

        if (!empty($platforms) && is_array($platforms)) {
        $rules->whereHas('platforms', function ($rul) use ($platforms) {
            $rul->whereIn('platform_id', $platforms);
        });
        }

       

        $games = $rules->skip($offset)->take(24)->get();
        return $games->map(function ($game) {
        return [
            'id' => $game->id,
            'name' => $game->name,
            'image' => $game->cover_url
            ? 'https:' . str_replace('t_thumb', 't_cover_big', $game->cover_url)
            : 'https://placehold.co/600x400',
            'genre' => $game->genres->pluck('name')->join(', ') ?: 'Unknown',
            'platforms' => $game->platforms->pluck('name')->join(', ') ?: 'Unknown',
            'source' => 'database',
            'developer' => $game->developer ?? "Unknown",
            'publisher' => $game->publisher ?? 'Unknown',
        ];
        });
    }

    public function getCollection(Request $request){
    $request->validate([
        'user_id' => 'required|integer', 
        'status'  => 'sometimes|nullable|in:Spēlēju,Pabeigta,Plānots,Pārtraukts'
    ]);

    $profileUser = User::findOrFail($request->user_id);
    $user = auth('sanctum')->user();
    $isOwner = $user && $user->id == $request->user_id;

    if ($profileUser->isPrivate && !$isOwner) {
        return response()->json(['message' => 'Profils ir privāts'], 403);
    }

    $baserules = Collection::where('user_id', $request->user_id);
    $stats = [
        'Visi' => (clone $baserules)->count(),
        'Spēlēju' => (clone $baserules)->where('status', 'Spēlēju')->count(),
        'Pabeigta' => (clone $baserules)->where('status', 'Pabeigta')->count(),
        'Plānots' => (clone $baserules)->where('status', 'Plānots')->count(),
        'Pārtraukts' => (clone $baserules)->where('status', 'Pārtraukts')->count(),
    ];

    if ($request->status) {
        $baserules->where('status', $request->status);
    }
    if ($request->sort){
        $baserules->orderBy('user_score', $request->sort);
    }

    $collection = $baserules->with('game')->get()->map(function ($item) {
        return [
            'id' => $item->id,
            'game' => [
                'id' => $item->game->id,
                'name' => $item->game->name,
                'image' => $item->game->cover_url
                    ? 'https:' . str_replace('t_thumb', 't_cover_big', $item->game->cover_url)
                    : 'https://placehold.co/60x80/111/444?text=' . urlencode($item->game->name),
            ],
            'status' => $item->status,
            'user_score' => $item->user_score,
            'notes' => $item->notes ?? '',
        ];
    });

    return response([
        'collection' => $collection,
        'stats' => $stats,
    ]);
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