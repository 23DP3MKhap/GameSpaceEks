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

        $rules = [];
        $dbgamesquantity = $request->dbgamesquantity;
        $search = $request->search;
        $genres = $request->genres;
        $platforms = $request->platforms;
        $dbgamesids = $request->dbgamesids;
        $offset = $request->offset ?? 0;

        $limit = 24 - $dbgamesquantity;
        $igdbOffset = max(0, $offset - $dbgamesquantity);
        $httpBody = "fields id, name, cover.url, genres.name, involved_companies.company.name, involved_companies.developer, involved_companies.publisher; sort total_rating_count desc; limit $limit; offset $igdbOffset;";
        

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

        $gameresponse = (Http::withBody($httpBody)->withHeaders([
        "Client-ID" => config('services.twitch.igdbclientid'),
        "Authorization" => "Bearer "  . $token])->post("https://api.igdb.com/v4/games"))->json();

        

        return collect($gameresponse)->map(function ($game){
            $developer = null;
            $publisher = null;
            
            if (!empty($game['involved_companies'])){
                foreach ($game['involved_companies'] as $company){
                    $companyName = $company['company']['name'] ?? null;

                    if (!empty($company['developer']) && $developer === null){
                        $developer = $companyName;
                    }

                    if (!empty($company['publisher']) && $publisher === null){
                        $publisher = $companyName;
                    }
                }
            }

            return [
                'id'        => $game['id'],
                'source'    => 'igdb',
                'name'      => $game['name'] ?? 'Unknown',
                'image'     => !empty($game['cover']['url']) ? 'https:' . str_replace('t_thumb', 't_cover_big', $game['cover']['url']) : 'https://placehold.co/600x400',
                'genre'     => collect($game['genres'] ?? [])->pluck('name')->join(', ') ?: 'Unknown',
                'platform'  => collect($game['platforms'] ?? [])->pluck('name')->join(', ') ?: 'Unknown',
                'developer' => $developer ?? 'Unknown',
                'publisher' => $publisher ?? 'Unknown',
            ];
        });
        }

}



