<?php

namespace App\Http\Controllers;

use App\Models\GameList;
use App\Models\User;
use Illuminate\Http\Request;

class ListController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['user_id' => 'required|integer']);

        $owner = User::findOrFail($request->user_id);
        $isOwner = $owner->is(auth('sanctum')->user());

        abort_if($owner->is_private && !$isOwner, 403);

        return GameList::where('user_id', $owner->id)
            ->when(!$isOwner, fn ($query) => $query->where('is_public', true))
            ->latest()
            ->get(['id', 'title', 'description', 'is_public']);
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

        return $list->load('user:id,username,avatar_url');
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
}
