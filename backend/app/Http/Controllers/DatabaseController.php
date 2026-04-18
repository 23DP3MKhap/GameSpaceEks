<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\Review;

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
        'developer'    => $dev,
        'publisher'    => $pub,
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


    // Get metodes

    public function getReviews(Request $request){
        $request->validate(['game_id' => 'required|integer']);
        $reviews = Review::where('game_id', $request->game_id)->with('user')->latest()->get();
        return response()->json($reviews);
    }
}