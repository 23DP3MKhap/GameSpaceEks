<?php

namespace App\Services;

use App\Exceptions\IgdbException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IgdbClient
{
    private const TOKEN_CACHE_KEY = 'igdb_token';

    public function query(string $endpoint, string $body)
    {
        $response = $this->send($endpoint, $body);

        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->send($endpoint, $body);
        }

        if ($response->failed()) {
            throw new IgdbException("IGDB request to /$endpoint failed with status {$response->status()}");
        }

        return $response->json();
    }

    public static function companies(array $game)
    {
        $developer = null;
        $publisher = null;

        foreach ($game['involved_companies'] ?? [] as $company) {
            $companyName = $company['company']['name'] ?? null;

            if (!empty($company['developer']) && $developer === null) {
                $developer = $companyName;
            }
            if (!empty($company['publisher']) && $publisher === null) {
                $publisher = $companyName;
            }
        }

        return ['developer' => $developer, 'publisher' => $publisher];
    }

    private function send(string $endpoint, string $body)
    {
        $token = $this->token();

        try {
            return Http::withBody($body)->withHeaders([
                "Client-ID" => config('services.twitch.igdbclientid'),
                "Authorization" => "Bearer " . $token,
            ])->post("https://api.igdb.com/v4/$endpoint");
        } catch (ConnectionException $e) {
            throw new IgdbException("Could not connect to IGDB", 0, $e);
        }
    }

    private function token()
    {
        $data = Cache::remember(self::TOKEN_CACHE_KEY, fn ($data) => $data['expires_in'] - 60, function () {
            try {
                $response = Http::post("https://id.twitch.tv/oauth2/token", [
                    "client_id" => config('services.twitch.igdbclientid'),
                    "client_secret" => config('services.twitch.igdbclientsecret'),
                    "grant_type" => 'client_credentials',
                ]);
            } catch (ConnectionException $e) {
                throw new IgdbException("Could not connect to Twitch", 0, $e);
            }

            if ($response->failed()) {
                throw new IgdbException("Twitch token request failed with status {$response->status()}");
            }

            return $response->json();
        });

        return $data['access_token'];
    }
}
