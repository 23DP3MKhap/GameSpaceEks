<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private function randomNote(string $status)
    {
        $notes = [
            'playing' => [
                'Es spēlēju tieši tagad',
                'Pagaidām man patīk, un neplānoju apstāties',
                'tikko sāku spēlēt',
                null,
            ],
            'completed' => [
                'Kaut kā trūka',
                'Es pārspēlēju ceturto reizi!',
                '100% pabeigts.',
                null,
            ],
            'planned' => [
                'vēl nav laika',
                'Man nav naudas, lai to nopirktu, bet es ļoti vēlos',
                'Draugi ieteica man kādreiz pamēģināt',
                null,
            ],
            'dropped' => [
                'Es netiku tam cauri, tas bija pārāk grūti',
                'ļoti garlaicīgi',
                'kādreiz turpināšu',
                null,
            ],
        ];
 
        $options = $notes[$status] ?? [null];
        if (count($options) > 1){
            return $options[rand(0,3)];
        }
        return $options[0];
    }


    public function run(): void
    {
        $this->call([GenreSeeder::class, PlatformSeeder::class]);



        // Games

        $client = (Http::post("https://id.twitch.tv/oauth2/token", 
        ["client_id" => config('services.twitch.igdbclientid'), 
        "client_secret" => config('services.twitch.igdbclientsecret'),
        "grant_type" => 'client_credentials']))->json();
        
        $token = $client['access_token'];

        $response = (Http::withBody("fields name, summary, first_release_date, rating, cover.url,
                    involved_companies.company.name, involved_companies.developer, involved_companies.publisher,
                    genres.id, platforms.id;
                    where rating != null & cover != null & first_release_date != null;
                    sort rating desc;
                    limit 20;")->withHeaders([
                                "Client-ID" => config('services.twitch.igdbclientid'),
                                "Authorization" => "Bearer "  . $token])
        ->post("https://api.igdb.com/v4/games"));

        if (!$response->successful()) {
            return;
        }
 

        $igdbGames = $response->json();
 
        foreach ($igdbGames as $game) {
            $developer  = null;
            $publisher  = null;
 
            if (!empty($game['involved_companies'])) {
                foreach ($game['involved_companies'] as $company) {
                    $companyName = $company['company']['name'] ?? null;
                    if (!empty($company['developer']) && $developer === null) {
                        $developer = $companyName;
                    }
                    if (!empty($company['publisher']) && $publisher === null) {
                        $publisher = $companyName;
                    }
                }
            }
       

        $coverUrl = null;
               if (!empty($game['cover']['url'])) {
                   $coverUrl =  $game['cover']['url'];
               }

        $releaseDate = null;
            if (!empty($game['first_release_date'])) {
                $releaseDate = date('Y-m-d', $game['first_release_date']);
        }

        DB::table('games')->updateOrInsert(
                ['id' => $game['id']],
                [
                    'name'         => $game['name'],
                    'developer'    => $developer,
                    'publisher'    => $publisher,
                    'release_date' => $releaseDate,
                    'description'  => $game['summary'] ?? null,
                    'cover_url'    => $coverUrl,
                    'rating'       => $game['rating'] ?? null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );

        if (!empty($game['genres'])) {
                foreach ($game['genres'] as $genre) {
                    $exists = DB::table('genres')->where('id', $genre['id'])->exists();
                    if ($exists) {
                        DB::table('game_genre')->updateOrInsert([
                            'game_id'  => $game['id'],
                            'genre_id' => $genre['id'],
                        ]);
                    }
                }
        }

        if (!empty($game['platforms'])) {
                foreach ($game['platforms'] as $platform) {
                    $exists = DB::table('platforms')->where('id', $platform['id'])->exists();
                    if ($exists) {
                        DB::table('game_platform')->updateOrInsert([
                            'game_id'     => $game['id'],
                            'platform_id' => $platform['id'],
                        ]);
                    }
                }
            }
        }

            $gameIds = DB::table('games')->pluck('id')->toArray();

        // Users

         $users = [
            [
                'name'     => 'Admin',
                'email'    => 'admin@example.com',
                'password' => 'password',
                'role'     => 'admin',
                'bio'      => 'Administrators',
                'avatar'   => null,
                'isPrivate'=> false,
            ],
            ['name' => 'Kaspars88',    'email' => 'kaspars@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'Man ļoti patīk lomu spēles', 'avatar' => 'https://i.pinimg.com/736x/47/e2/c1/47e2c13c533c5d1e87c98cf6fdd83ff1.jpg', 'isPrivate' => false],
            ['name' => 'LaimaGamer',   'email' => 'laima@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'Indie spēles ir labākās lietas', 'avatar' => null, 'isPrivate' => false],
            ['name' => 'MartinsLV',    'email' => 'martins@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'regulāri piedalos turnīros', 'avatar' => 'https://i.pinimg.com/736x/a9/9d/13/a99d13e02080ac70dd218005e25dc33e.jpg', 'isPrivate' => true],
            ['name' => 'Zane_plays',   'email' => 'zane@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'es spēlēju tikai Souls spēles', 'avatar' => null, 'isPrivate' => false],
            ['name' => 'EdgarsK',      'email' => 'edgars@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'Valorant - imo, CS - 10lvl', 'avatar' => null, 'isPrivate' => false],
            ['name' => 'IevaNeko',     'email' => 'ieva@example.com', 'password' => 'password', 'role' => 'user', 'bio' => '', 'avatar' => null, 'isPrivate' => true],
            ['name' => 'RaivisPro',    'email' => 'raivis@example.com', 'password' => 'password', 'role' => 'user', 'bio' => '', 'avatar' => null, 'isPrivate' => false],
            ['name' => 'AnnaPixel',    'email' => 'anna@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'Man patīk zīmēt', 'avatar' => 'https://i.pinimg.com/736x/b6/01/24/b6012426829dff1e2feb2f817f6391b3.jpg', 'isPrivate' => false],
            ['name' => 'JanisSpeed',   'email' => 'janis@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'Man ļoti patīk pixel art', 'avatar' => null, 'isPrivate' => false],
            ['name' => 'DianaRPG',     'email' => 'diana@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'profesionāls kļūdu detektīvs', 'avatar' => 'https://i.pinimg.com/736x/d7/d2/23/d7d223976fc5d8e02a0584ff8dcb11d1.jpg', 'isPrivate' => false],
            ['name' => 'TomsMetal',    'email' => 'toms@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'es spēlēju šausmu spēles', 'avatar' => 'https://i.pinimg.com/736x/0d/4a/2d/0d4a2d3b6add65506932c2429935c074.jpg', 'isPrivate' => true],
            ['name' => 'SandraQuest',  'email' => 'sandra@example.com', 'password' => 'password', 'role' => 'user', 'bio' => '4000 stundas vizuālo romānu', 'avatar' => null, 'isPrivate' => false],
            ['name' => 'Kristaps_GG',  'email' => 'kristaps@example.com', 'password' => 'password', 'role' => 'user', 'bio' => 'Es skatos tikai turnīrus', 'avatar' => 'https://i.pinimg.com/736x/21/11/f3/2111f394ebf84290a7f2b14d8525d754.jpg', 'isPrivate' => false],
            ['name' => 'ValentinaLV',  'email' => 'valentina@example.com', 'password' => 'password', 'role' => 'user', 'bio' => '', 'avatar' => 'https://i.pinimg.com/736x/89/eb/e3/89ebe3f226d72c28b773b7e73f5a2999.jpg', 'isPrivate' => false],
            ['name' => 'NorbertsX',    'email' => 'norberts@example.com', 'password' => 'password', 'role' => 'user', 'bio' => '', 'avatar' => 'https://i.pinimg.com/736x/25/2d/40/252d40807fced95bacc787c66758502d.jpg', 'isPrivate' => false],
        ];
 
        $userIds = [];
        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name'      => $userData['name'],
                    'password'  => $userData['password'],
                    'role'      => $userData['role'],
                    'bio'       => $userData['bio'],
                    'avatar'    => $userData['avatar'],
                    'isPrivate' => $userData['isPrivate'],
                    'email_verified' => true,
                ]
            );
            $userIds[] = $user->id;
        }
 

        // Collections

        $statuses = ['Spēlēju', 'Pabeigta', 'Plānots', 'Pārtraukts'];
   

        foreach ($userIds as $userId) {
            $userGames = collect($gameIds)->shuffle()->take(rand(3, 7));
            foreach ($userGames as $gameId) {
                $status = $statuses[rand(0, 3)];
                DB::table('collections')->updateOrInsert(
                    ['user_id' => $userId, 'game_id' => $gameId],
                    [
                        'status'     => $status,
                        'user_score' => rand(1, 10),
                        'notes'      => $this->randomNote($status),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
   
   
        // Reviews

        
        $reviews = [
            [
                'title'   => 'Grafika ir augstākās klases',
                'content' => 'Es burtiski varu skatīties uz pikseļiem un pat neapzināties, ka tā ir datorspēle.',
                'rating'  => 9,
            ],
            [
                'title'   => 'Labi, bet man nepatika',
                'content' => 'Spēle ir laba gan koncepcijas, gan izpildījuma ziņā, bet man noteikti bija sajūta, ka kaut kā pietrūkst.',
                'rating'  => 6,
            ],
            [
                'title'   => 'patiesi šedevrs',
                'content' => 'Man nav vārdu, lai aprakstītu, cik laba ir šī spēle, es pat nedomāju, ka varu pienācīgi izteikt savas domas, stāstot kādam par šo spēli',
                'rating'  => 10,
            ],
            [
                'title'   => 'izstrādātāji ir slinki',
                'content' => 'Spēle ir laba, bet šķiet neapstrādāta.',
                'rating'  => 7,
            ],
            [
                'title'   => 'ļoti jautri spēlēt.',
                'content' => 'Esmu ar prieku spēlējis šo spēli vairāk nekā 100 stundas, un man joprojām nav garlaicīgi. Droši vien spēlēšu tikpat ilgi',
                'rating'  => 9,
            ],
            [
                'title'   => 'Laba ideja, slikts izpildījums',
                'content' => 'Lieliska ideja un interesanta spēle, bet viss ir ļoti salauzts un darbojas ar kruķiem',
                'rating'  => 6,
            ],
            [
                'title'   => 'Multiplayer 10/10',
                'content' => 'Spēlēt ar draugiem ir prieks, tur ir bezgalīga satura kaudze, bet spēlēt vienatnē ir diezgan garlaicīgi un vienmuļi',
                'rating'  => 8,
            ],
            [
                'title'   => 'Sižeta attīstība prasa ilgu laiku',
                'content' => 'Beigas ir ļoti labas, bet žēl, ka, lai redzētu beigas, spēlē jāpavada vairāk nekā 50 stundas, jo spēles gaita šeit ir ļoti garlaicīga un bieži atkārtojas',
                'rating'  => 7,
            ],
            [
                'title'   => 'pasaule ir ļoti labi attīstīta!',
                'content' => 'Vari iziet pa katrām durvīm, un visur, kur paskaties, ir tik daudz mazu detaļu!',
                'rating'  => 8,
            ],
            [
                'title'   => 'izskatās labāk nekā spēlējas',
                'content' => 'Labāk ir noskatīties šīs spēles sižetu vietnē YouTube, nekā spēlēt šo spēli pašam.',
                'rating'  => 7,
            ],
        ];
   
        $usedPairs = [];
        $reviewCount = 0;
        $UserIdsNoAdmin = array_slice($userIds, 1);

        shuffle($UserIdsNoAdmin);
        foreach ($UserIdsNoAdmin as $userId) {
            $availableGames = array_diff($gameIds, array_column(
                array_filter($usedPairs, function($pair) use($userId) {return $pair[0] === $userId;}), 1
            ));
 
            $gamesToReview = collect($availableGames)->shuffle()->take(rand(1, 3));
            foreach ($gamesToReview as $gameId) {

                $usedPairs[] = [$userId, $gameId];
 
                $review = $reviews[$reviewCount % 10];
                DB::table('reviews')->updateOrInsert(
                ['user_id'    => $userId,
                'game_id'    => $gameId],    
                [
                    'user_id'    => $userId,
                    'game_id'    => $gameId,
                    'title'      => $review['title'],
                    'content'    => $review['content'],
                    'rating'     => $review['rating'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $reviewCount++;
            }
        }

        
}}
