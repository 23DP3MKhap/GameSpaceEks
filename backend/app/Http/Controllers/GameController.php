<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;



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
    
    public function getgames(Request $request){
        $client = (Http::post("https://id.twitch.tv/oauth2/token", 
        ["client_id" => config('services.twitch.igdbclientid'), 
        "client_secret" => config('services.twitch.igdbclientsecret'),
        "grant_type" => 'client_credentials']))->json();
        
        $token = $client['access_token'];

        $httpBody = "fields id, name, cover.url, genres.name; sort popularity desc; limit 24;";
        $rules = [];
        $dbgamesquantity = $request->dbgamesquantity;
        $search = $request->search;
        $genres = $request->genres;
        $platforms = $request->platforms;
        $dbgamesids = $request->dbgamesids;
        $offset = $request->offset ?? 0;

        $limit = 24 - $dbgamesquantity;
        $igdbOffset = max(0, $offset - $dbgamesquantity);
        $httpBody = "fields id, name, cover.url, genres.name; sort popularity desc; limit $limit; offset $igdbOffset;";
        

        if ($dbgamesids) {
            $rules[] = "id != ($dbgamesids)";
        }

        if ($search) {
            $rules[] = "name ~ *\"$search\"*";
        }

        if (!empty($genres) && is_array($genres)) {
            $rules[] = "genres = (" . implode(',', $genres) . ")";
        }

        if (!empty($platforms) && is_array($platforms)) {
            $rules[] = "platforms = (" . implode(',', $platforms) . ")";
        }

        if (!empty($rules)) {
            $httpBody .= " where " . implode(' & ', $rules) . ";";
        }
        \Log::info($httpBody);
        $gameresponse = (Http::withBody($httpBody)->withHeaders([
        "Client-ID" => config('services.twitch.igdbclientid'),
        "Authorization" => "Bearer "  . $token])->post("https://api.igdb.com/v4/games"))->json();

        return $gameresponse;
        }

}



