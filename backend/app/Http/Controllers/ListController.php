<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ListController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'game_id' => 'nullable|integer',
        ]);

        $owner = User::findOrFail($request->user_id);
        $isOwner = $owner->is(auth('sanctum')->user());

        abort_if($owner->is_private && !$isOwner, 403);

        return GameList::where('user_id', $owner->id)
            ->select(['id', 'title', 'description', 'is_public'])
            ->when(!$isOwner, fn ($query) => $query->where('is_public', true))
            ->when($request->game_id, function ($query, $gameId) {
                $query->withExists(['games as has_game' => fn ($games) => $games->whereKey($gameId)]);
            })
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'required|boolean',
        ]);

        return GameList::create([...$validated, 'user_id' => $request->user()->id]);
    }

    public function show(GameList $list)
    {
        abort_if(!$list->is_public && !$list->user()->is(auth('sanctum')->user()), 404);

        return $list->load([
            'user:id,username,avatar_url',
            'games' => fn ($query) => $query->where('is_hidden', false)
                ->orderByPivot('position')
                ->select('games.id', 'title', 'release_date', 'cover_url'),
        ]);
    }

    public function update(Request $request, GameList $list)
    {
        abort_if(!$list->user()->is($request->user()), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'required|boolean',
        ]);

        $list->update($validated);

        return $list;
    }

    public function destroy(Request $request, GameList $list)
    {
        abort_if(!$list->user()->is($request->user()), 403);

        $list->delete();
    }

    public function addGame(Request $request, GameList $list)
    {
        abort_if(!$list->user()->is($request->user()), 403);

        $validated = $request->validate(['game_id' => 'required|integer']);

        $game = Game::where('is_hidden', false)->findOrFail($validated['game_id']);

        abort_if($list->games()->whereKey($game->id)->exists(), 409, 'Game is already in this list');

        $count = $list->games()->count();

        abort_if($count >= GameList::MAX_GAMES, 422, 'A list can contain up to ' . GameList::MAX_GAMES . ' games');

        $list->games()->attach($game->id, ['position' => $count + 1]);
        $list->touch();
    }

    public function updateGame(Request $request, GameList $list, Game $game)
    {
        abort_if(!$list->user()->is($request->user()), 403);

        $validated = $request->validate(['note' => 'present|nullable|string|max:250']);

        $list->games()->updateExistingPivot($game->id, $validated);
        $list->touch();
    }

    public function removeGame(Request $request, GameList $list, Game $game)
    {
        abort_if(!$list->user()->is($request->user()), 403);

        $position = $list->games()->findOrFail($game->id)->pivot->position;

        DB::transaction(function () use ($list, $game, $position) {
            $list->games()->detach($game->id);
            $list->games()->newPivotQuery()->where('position', '>', $position)->decrement('position');
            $list->touch();
        });
    }

    public function reorder(Request $request, GameList $list)
    {
        abort_if(!$list->user()->is($request->user()), 403);

        $games = $list->games()->orderByPivot('position')->get(['games.id', 'is_hidden']);
        $visibleIds = $games->where('is_hidden', false)->pluck('id');

        $validated = $request->validate([
            'game_ids' => 'required|array|size:' . $visibleIds->count(),
            'game_ids.*' => ['integer', 'distinct', Rule::in($visibleIds)],
        ]);

        $order = collect($validated['game_ids'])->concat($games->where('is_hidden', true)->pluck('id'));

        DB::transaction(function () use ($list, $order) {
            foreach ($order as $index => $gameId) {
                $list->games()->updateExistingPivot($gameId, ['position' => $index + 1]);
            }
            $list->touch();
        });
    }
}
