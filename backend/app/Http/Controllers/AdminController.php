<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Game;
use App\Models\Review;
use App\Models\UserGame;

class AdminController extends Controller
{
    public function getUsers(Request $request){
        return User::select('id', 'username', 'email', 'bio', 'avatar_url', 'role', 'created_at')
            ->orderBy('id')
            ->paginate($this->perPage($request));
    }

    public function updateUser(Request $request, $id){
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'username' => 'sometimes|string|max:20|regex:/^[a-zA-Z][\w]*$/|unique:users,username,' . $id,
            'email' => 'sometimes|email|max:255|unique:users,email,' . $id,
            'bio' => 'sometimes|nullable|string|max:50',
            'avatar_url' => 'sometimes|nullable|url|max:2048',
            'role' => 'sometimes|in:user,admin',
        ]);
        if (($validated['role'] ?? null) === 'user' && $request->user()->id == $id) {
            return response()->json(['message' => 'Nevar noņemt sev administratora lomu'], 403);
        }
        $user->forceFill($validated)->save();
    }

    public function deleteUser(Request $request, $id){
        if ($request->user()->id == $id) {
            return response()->json(['message' => 'Nevar dzēst savu kontu'], 403);
        }
        $user = User::findOrFail($id);
        $user->tokens()->delete();
        $user->delete();
    }

    public function getGames(Request $request){
        return Game::select('id', 'title', 'developer', 'publisher', 'release_date', 'rating', 'cover_url', 'description')
            ->orderBy('id')
            ->paginate($this->perPage($request));
    }

    public function updateGame(Request $request, $id){
        $game = Game::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'developer' => 'sometimes|nullable|string|max:255',
            'publisher' => 'sometimes|nullable|string|max:255',
            'release_date' => 'sometimes|nullable|date',
            'rating' => 'sometimes|nullable|numeric|min:0|max:100',
            'cover_url' => 'sometimes|nullable|url|max:2048',
            'description' => 'sometimes|nullable|string',
        ]);
        $game->update($validated);
    }

    public function deleteGame($id){
        Game::findOrFail($id)->delete();
    }

    public function getReviews(Request $request){
        return Review::with(['user:id,username', 'game:id,title'])
            ->orderBy('id')
            ->paginate($this->perPage($request));
    }

    public function deleteReview($id){
        Review::findOrFail($id)->delete();
    }

    public function deleteUserCollection($id){
        User::findOrFail($id);
        UserGame::where('user_id', $id)->delete();
    }

    private function perPage(Request $request){
        return $request->validate(['per_page' => 'sometimes|integer|min:1|max:100'])['per_page'] ?? 25;
    }
}
