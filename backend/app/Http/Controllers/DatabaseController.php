<?php

namespace App\Http\Controllers;
use App\Services\IgdbClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Game;
use App\Models\Review;
use App\Models\UserGame;
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

    $game = Game::create([
        ...Game::fromIgdb($resData),
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
        'status' => ['required', Rule::in(UserGame::STATUSES)],
        'rating' => 'nullable|integer|min:1|max:10',
        'note' => 'nullable|string|max:50',
    ]);

    UserGame::query()->updateOrInsert(
        [
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id']
        ],
        [
            'status' => $validated['status'],
            'rating' => $validated['rating'] ?? null,
            'note'  => $validated['note'] ?? null,
        ]);
    }

    public function removeFromCollection(Request $request){
        $request->validate(['game_id' => 'required|integer']);
        $userId = $request->user()->id;
        UserGame::where('game_id', $request->game_id)->where('user_id', $userId)->delete();
    }

    public function updateUser(Request $request){
        $user = $request->user(); 
        $validated = $request->validate([
            'email' => 'sometimes|email|max:50|unique:users,email,' . $user->id,
            'username' => 'sometimes|string|max:20|regex:/^[a-zA-Z][a-zA-Z0-9]*$/|unique:users,username,' . $user->id,
            'bio' => 'sometimes|nullable|string|max:250',
            'avatar_url' => 'sometimes|nullable|max:255|regex:/^https?:\/\/.+/',
            'password' => 'sometimes|nullable|string|min:8|max:250',
            'is_private' => 'sometimes|boolean',
            'current_password' => 'sometimes|nullable|string',
        ]);

        $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;
        $passwordChanged = !empty($validated['password']);

        if (($emailChanged || $passwordChanged)
            && !Hash::check($validated['current_password'] ?? '', $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'Pašreizējā parole nav pareiza',
            ]);
        }

        if ($emailChanged) {
            $user->email = $validated['email'];
            $user->email_verified = false;
            $user->emailVerifications()->whereNull('used_at')->delete();
        }
        if (isset($validated['username'])) {
            $user->username = $validated['username'];
        }
        if (array_key_exists('bio', $validated)) {
            $user->bio = $validated['bio'];
        }
        if (array_key_exists('avatar_url', $validated)) {
            $user->avatar_url = $validated['avatar_url'];
        }
        if (array_key_exists('is_private', $validated)) {
            $user->is_private = $validated['is_private'];
        }
        if ($passwordChanged) {
            $user->password_hash = $validated['password'];
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

    $reviews = Review::with('user:id,username,avatar_url')
        ->where('game_id', $request->game_id)
        ->latest()
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
                    'id' => $review->user->id,
                    'username' => $review->user->username,
                    'avatar_url' => $review->user->avatar_url,
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
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'genres' => 'nullable|array',
            'genres.*' => 'integer',
            'platforms' => 'nullable|array',
            'platforms.*' => 'integer',
            'offset' => 'nullable|integer|min:0',
        ]);

        $search = $validated['search'] ?? null;
        $genres = $validated['genres'] ?? [];
        $platforms = $validated['platforms'] ?? [];
        $offset = $validated['offset'] ?? 0;

        $rules = Game::with(['genres', 'platforms']);

        if ($search) {
            $rules->where('title', 'like', "%{$search}%");
        }

        if (!empty($genres)) {
            $rules->whereHas('genres', function ($rul) use ($genres) {
                $rul->whereIn('genre_id', $genres);
            });
        }

        if (!empty($platforms)) {
            $rules->whereHas('platforms', function ($rul) use ($platforms) {
                $rul->whereIn('platform_id', $platforms);
            });
        }

        $games = $rules->orderBy('id')->skip($offset)->take(50)->get();
        return $games->map(function ($game) {
        return [
            'id' => $game->id,
            'title' => $game->title,
            'image' => $game->cover_url ?? 'https://placehold.co/600x400',
            'genre' => $game->genres->pluck('name')->join(', ') ?: 'Nav zināms',
            'platforms' => $game->platforms->pluck('name')->join(', ') ?: 'Nav zināms',
            'source' => 'database',
            'developer' => $game->developer ?? 'Nav zināms',
            'publisher' => $game->publisher ?? 'Nav zināms',
        ];
        });
    }

    public function getCollection(Request $request){
    $request->validate([
        'user_id' => 'required|integer', 
        'status'  => ['sometimes', 'nullable', Rule::in(UserGame::STATUSES)],
    ]);

    $profileUser = User::findOrFail($request->user_id);
    $user = auth('sanctum')->user();
    $isOwner = $user && $user->id == $request->user_id;

    if ($profileUser->is_private && !$isOwner) {
        return response()->json(['message' => 'Profils ir privāts'], 403);
    }

    $baserules = UserGame::where('user_id', $request->user_id);
    $counts = (clone $baserules)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
    $stats = ['all' => $counts->sum()];
    foreach (UserGame::STATUSES as $status) {
        $stats[$status] = $counts[$status] ?? 0;
    }

    if ($request->status) {
        $baserules->where('status', $request->status);
    }
    if ($request->sort){
        $baserules->orderBy('rating', $request->sort);
    }

    $collection = $baserules->with('game')->get()->map(function ($item) {
        return [
            'game' => [
                'id' => $item->game->id,
                'title' => $item->game->title,
                'image' => $item->game->cover_url ?? 'https://placehold.co/60x80/111/444?text=' . urlencode($item->game->title),
            ],
            'status' => $item->status,
            'rating' => $item->rating,
            'note' => $item->note ?? '',
        ];
    });

    return response([
        'collection' => $collection,
        'stats' => $stats,
    ]);
}

    

    public function checkCollection(Request $request){
    $collection = UserGame::where('user_id', auth()->id())
        ->where('game_id', $request->game_id)
        ->first();

    return [
        'exists' => $collection !== null,
        'status' => $collection?->status,
        'rating' => $collection?->rating,
        'note' => $collection?->note,
    ];
}


    
}