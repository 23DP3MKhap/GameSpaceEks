<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $client = (Http::post("https://id.twitch.tv/oauth2/token", 
        ["client_id" => config('services.twitch.igdbclientid'), 
        "client_secret" => config('services.twitch.igdbclientsecret'),
        "grant_type" => 'client_credentials']))->json();
        
        $token = $client['access_token'];

        $gameresponse = (Http::withBody("fields name; limit 500;")->withHeaders([
        "Client-ID" => config('services.twitch.igdbclientid'),
        "Authorization" => "Bearer "  . $token])->post("https://api.igdb.com/v4/platforms"));


        if ($gameresponse->successful()) {
        foreach ($gameresponse->json() as $platform) {
            DB::table('platforms')->updateOrInsert(
                ['id' => $platform['id']],
                ['name' => $platform['name']]
                );
            }
        }
    }
}
