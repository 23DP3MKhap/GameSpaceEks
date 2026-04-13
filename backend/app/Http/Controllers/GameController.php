<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

// config('services.twitch.igdb');


class GameController extends Controller
{
    public function getclient(){
        $response = (Http::post("https://id.twitch.tv/oauth2/token", 
            ["client_id" => config('services.twitch.igdbclientid'), 
            "client_secret" => config('services.twitch.igdbclientsecret'),
            "grant_type" => 'client_credentials'
]))->json();
        return $response['expires_in'];
    }
    // btq5tl9mt5ydurj0ws7nfrdqu317al
    
    public function getgames(){
        $client = (Http::post("https://id.twitch.tv/oauth2/token", 
        ["client_id" => config('services.twitch.igdbclientid'), 
        "client_secret" => config('services.twitch.igdbclientsecret'),
        "grant_type" => 'client_credentials']))->json();
        
        $token = $client['access_token'];

        $gameresponse = (Http::withBody("fields id, name, cover.url, genres.name; limit 21;")->withHeaders([
        "Client-ID" => config('services.twitch.igdbclientid'),
        "Authorization" => "Bearer "  . $token])->post("https://api.igdb.com/v4/games"))->json();

        return $gameresponse;
        }


    public function getgamesbyname(Request $request){
        $client = (Http::post("https://id.twitch.tv/oauth2/token", 
        ["client_id" => config('services.twitch.igdbclientid'), 
        "client_secret" => config('services.twitch.igdbclientsecret'),
        "grant_type" => 'client_credentials']))->json();
        
        $token = $client['access_token'];

        $gameresponse = (Http::withBody("search \"$request->search\"; fields id, name, cover.url, genres.name; limit 21;")->withHeaders([
        "Client-ID" => config('services.twitch.igdbclientid'),
        "Authorization" => "Bearer "  . $token])->post("https://api.igdb.com/v4/games"))->json();

        return $gameresponse;
    }
}


    

// "Client-ID" => config('services.twitch.igdbclientid'),
//          "Authorization" =>  "Bearer " . $token


