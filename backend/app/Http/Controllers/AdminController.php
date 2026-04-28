<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Game;
use App\Models\Review;
use App\Models\Collection;

class AdminController extends Controller
{
    public function getUsers(){
        return User::select('id', 'name', 'email', 'bio', 'avatar', 'role', 'created_at')->get();
    }

    public function updateUser(Request $request, $id){
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:users,name,' . $id,
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'bio' => 'sometimes|nullable|string|max:500',
            'avatar' => 'sometimes|nullable|url|max:2048',
            'role' => 'sometimes|in:user,admin',
        ]);
        $user->update($validated);
    }

    public function deleteUser($id){
        User::findOrFail($id)->delete();
    }

    public function getGames(){
        return Game::select('id', 'name', 'developer', 'publisher', 'release_date', 'rating', 'cover_url')->get();
    }

    public function updateGame(Request $request, $id){
        $game = Game::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'developer' => 'sometimes|nullable|string|max:255',
            'publisher' => 'sometimes|nullable|string|max:255',
            'release_date' => 'sometimes|nullable|date',
            'rating' => 'sometimes|nullable|numeric|min:0|max:100',
            'cover_url' => 'sometimes|nullable|string|max:2048',
            'description' => 'sometimes|nullable|string',
        ]);
        $game->update($validated);
    }

    public function deleteGame($id){
        Game::findOrFail($id)->delete();
    }

    public function getReviews(){
        return Review::with(['user:id,name', 'game:id,name'])->get();
    }

    public function deleteReview($id){
        Review::findOrFail($id)->delete();
    }
    
    public function deleteUserCollection($id){
    Collection::where('user_id', $id)->delete();
    }
}
