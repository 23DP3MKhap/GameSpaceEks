<?php

namespace App\Http\Controllers;
use App\Services\CloudinaryClient;
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

    $response = $igdb->query('games', "fields name, summary, first_release_date, cover.url, involved_companies.developer,
        involved_companies.publisher, involved_companies.company.name, genres, platforms; where id = $igdbId;");

    if (empty($response)) {
        return response()->json(['message' => 'Spēle IGDB netika atrasta'], 404);
    }

    $resData = $response[0];

    $game = Game::create(Game::fromIgdb($resData));

    $game->genres()->attach(Genre::whereIn('id', $resData['genres'] ?? [])->pluck('id'));
    $game->platforms()->attach(Platform::whereIn('id', $resData['platforms'] ?? [])->pluck('id'));
    }


    public function addReview(Request $request){
        $validated = $request->validate([
            'game_id' => 'required|integer|exists:games,id',
            'title' => 'required|string|max:50',
            'content' => 'required|string|min:10|max:500',
        ]);

        Review::updateOrCreate(
        ['user_id' => $request->user()->id, 'game_id' => $validated['game_id']],
        [
            'title' => $validated['title'],
            'content' => $validated['content'],
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
        'rating' => 'nullable|integer|min:1|max:5',
        'note' => 'nullable|string|max:250',
        'hours_played' => 'nullable|integer|min:0|max:99999',
        'completion_percent' => 'nullable|integer|min:0|max:100',
        'completed_at' => 'nullable|date_format:Y-m-d|before_or_equal:today',
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
            'hours_played' => $validated['hours_played'] ?? null,
            'completion_percent' => $validated['completion_percent'] ?? null,
            'completed_at' => $validated['completed_at'] ?? null,
        ]);

        return $this->gameStats($validated['game_id']);
    }

    public function removeFromCollection(Request $request){
        $validated = $request->validate(['game_id' => 'required|integer|exists:games,id']);
        $userId = $request->user()->id;
        UserGame::where('game_id', $validated['game_id'])->where('user_id', $userId)->delete();

        return $this->gameStats($validated['game_id']);
    }

    private function gameStats(int $gameId){
        return Game::withAvg('userGames as average_rating', 'rating')
            ->withCount('userGames as player_count')
            ->find($gameId)
            ->only(['average_rating', 'player_count']);
    }

    public function updateUser(Request $request){
        $user = $request->user(); 
        $validated = $request->validate([
            'username' => 'sometimes|string|max:20|regex:/^[a-zA-Z][a-zA-Z0-9]*$/|unique:users,username,' . $user->id,
            'bio' => 'sometimes|nullable|string|max:250',
            'password' => 'sometimes|nullable|string|min:8|max:250',
            'is_private' => 'sometimes|boolean',
            'current_password' => 'sometimes|nullable|string',
        ]);

        $passwordChanged = !empty($validated['password']);

        if ($passwordChanged && !Hash::check($validated['current_password'] ?? '', $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'Pašreizējā parole nav pareiza',
            ]);
        }

        if (isset($validated['username'])) {
            $user->username = $validated['username'];
        }
        if (array_key_exists('bio', $validated)) {
            $user->bio = $validated['bio'];
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

    public function updateAvatar(Request $request, CloudinaryClient $cloudinary){
        $request->validate(
            ['avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120'],
            ['avatar' => 'Atļauti JPG, PNG vai WEBP attēli līdz 5 MB']
        );
        $user = $request->user();

        $user->avatar_url = $cloudinary->uploadAvatar($user->id, $request->file('avatar'));
        $user->save();

        return $user;
    }

    public function deleteAvatar(Request $request, CloudinaryClient $cloudinary){
        $user = $request->user();

        $cloudinary->deleteAvatar($user->id);
        $user->avatar_url = null;
        $user->save();

        return $user;
    }

    public function deleteUser(Request $request, CloudinaryClient $cloudinary){
        $validated = $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (!Hash::check($validated['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'password' => 'Nepareiza parole',
            ]);
        }

        if ($user->avatar_url) {
            $cloudinary->deleteAvatar($user->id);
        }
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

    public function getCollection(Request $request){
    $validated = $request->validate([
        'user_id' => 'required|integer',
        'status'  => ['sometimes', 'nullable', Rule::in(UserGame::STATUSES)],
        'sort' => ['sometimes', Rule::in(['rating_desc', 'rating_asc', 'hours_desc', 'added_desc'])],
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
    match ($validated['sort'] ?? 'added_desc') {
        'rating_desc' => $baserules->orderByDesc('rating'),
        'rating_asc' => $baserules->orderByRaw('rating IS NULL')->orderBy('rating'),
        'hours_desc' => $baserules->orderByDesc('hours_played'),
        'added_desc' => null,
    };
    $baserules->orderByDesc('added_at')->orderByDesc('game_id');

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
            'hours_played' => $item->hours_played,
            'completion_percent' => $item->completion_percent,
            'completed_at' => $item->completed_at,
        ];
    });

    return response([
        'collection' => $collection,
        'stats' => $stats,
    ]);
}

    public function getStatistics(Request $request){
        $rows = UserGame::where('user_id', $request->user()->id)
            ->selectRaw('status, count(*) as games, sum(hours_played) as hours, sum(rating) as rating_sum, count(rating) as rated')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $statistics = [];
        foreach (UserGame::STATUSES as $status) {
            $statistics[$status] = $rows[$status]->games ?? 0;
        }
        $rated = $rows->sum('rated');

        return [
            ...$statistics,
            'total' => $rows->sum('games'),
            'hours' => $rows->sum('hours'),
            'average_rating' => $rated ? round($rows->sum('rating_sum') / $rated, 1) : null,
        ];
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
        'hours_played' => $collection?->hours_played,
        'completion_percent' => $collection?->completion_percent,
        'completed_at' => $collection?->completed_at,
    ];
}


    
}