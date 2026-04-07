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

    
    // Test functions
    public function getgames(){
        $client = (Http::post("https://id.twitch.tv/oauth2/token", 
        ["client_id" => config('services.twitch.igdbclientid'), 
        "client_secret" => config('services.twitch.igdbclientsecret'),
        "grant_type" => 'client_credentials']))->json();
        
        $token = $client['access_token'];

        $gameresponse = (Http::withBody("fields *;")->withHeaders([
        "Client-ID" => config('services.twitch.igdbclientid'),
        "Authorization" => "Bearer "  . $token])->post("https://api.igdb.com/v4/games"))->json();

        return $gameresponse;
        }
}

// "Client-ID" => config('services.twitch.igdbclientid'),
//          "Authorization" =>  "Bearer " . $token


