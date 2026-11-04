<?php

namespace App\Http\Controllers;
use App\Services\IgdbClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Game;
use App\Models\Review;
use App\Models\Collection;
use App\Models\Genre;
use App\Models\Platform;
use App\Models\User;

class DatabaseController extends Controller
{

    // Create metodes 

    public function addGame(Request $request, IgdbClient $igdb){
        $validated = $request->validate([
        'igdb_id' => 'required|integer'
    ]);

    $igdbId = $validated['igdb_id'];

    $existingGame = Game::find($igdbId);
    if ($existingGame) {
        return;
    }

    $response = $igdb->query('games', "fields name, summary, first_release_date, aggregated_rating, cover.url, involved_companies.developer,
        involved_companies.publisher, involved_companies.company.name, genres, platforms; where id = $igdbId;");

    if (empty($response)) {
        return response()->json(['message' => 'Spēle IGDB netika atrasta'], 404);
    }

    $resData = $response[0];
    $companies = IgdbClient::companies($resData);

    $game = Game::create([
        'id' => $resData['id'],
        'name' => $resData['name'],
        'description' => $resData['summary'] ?? null,
        'developer' => $companies['developer'],
        'publisher' => $companies['publisher'],
        'release_date' => !empty($resData['first_release_date']) ? date('Y-m-d', $resData['first_release_date']) : null,
        'cover_url' => !empty($resData['cover']) ? str_replace('t_thumb', 't_cover_big', $resData['cover']['url']) : null,
        'rating' => $resData['aggregated_rating'] ?? null,
    ]);

    $game->genres()->attach(Genre::whereIn('id', $resData['genres'] ?? [])->pluck('id'));
    $game->platforms()->attach(Platform::whereIn('id', $resData['platforms'] ?? [])->pluck('id'));
    }


    public function addReview(Request $request){
        $validated = $request->validate([
            'game_id' => 'required|integer|exists:games,id',
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
        'game_id' => 'required|integer|exists:games,id',
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
            'user_score' => $validated['user_score'] ?? null,
            'notes'  => $validated['notes'] ?? null,
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
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'username' => 'sometimes|string|max:10|regex:/^[a-zA-Z][\w]*$/|unique:users,name,' . $user->id,
            'bio' => 'sometimes|nullable|string|max:50',
            'avatar_url' => 'sometimes|nullable|regex:/^https?:\/\/.+/',
            'password' => 'sometimes|nullable|string|min:8|max:255',
            'is_private' => 'sometimes|boolean',
            'current_password' => 'sometimes|nullable|string',
        ]);

        $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;
        $passwordChanged = !empty($validated['password']);

        if (($emailChanged || $passwordChanged)
            && !Hash::check($validated['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'wrong current password',
            ]);
        }

        if ($emailChanged) {
            $user->email = $validated['email'];
            $user->email_verified = false; 
            $user->verification_code = null;
            $user->verification_code_expires_at = null;
        }
        if (isset($validated['username'])) {
            $user->name = $validated['username'];
        }
        if (array_key_exists('bio', $validated)) {
            $user->bio = $validated['bio'];
        }
        if (array_key_exists('avatar_url', $validated)) {
            $user->avatar = $validated['avatar_url'];
        }
        if (array_key_exists('is_private', $validated)) {
            $user->isPrivate = $validated['is_private'];
        }
        if ($passwordChanged) {
            $user->password = $validated['password'];
        }

        $user->save();

        if ($passwordChanged) {
            $user->tokens()
                ->where('id', '!=', $user->currentAccessToken()->id)
                ->delete();
        }

        return $user;
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

       

        $games = $rules->skip($offset)->take(50)->get();
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