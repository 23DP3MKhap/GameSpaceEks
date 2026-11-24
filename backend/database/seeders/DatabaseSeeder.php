<?php

namespace Database\Seeders;

use App\Models\UserGame;
use App\Models\Game;
use App\Models\User;
use App\Services\IgdbClient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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

    private function randomProgress(string $status)
    {
        return match ($status) {
            'completed' => [
                'hours_played'       => rand(10, 150),
                'completion_percent' => 100,
                'completed_at'       => now()->subDays(rand(1, 365))->toDateString(),
            ],
            'planned' => [
                'hours_played'       => null,
                'completion_percent' => null,
                'completed_at'       => null,
            ],
            default => [
                'hours_played'       => rand(1, 80),
                'completion_percent' => rand(5, 90),
                'completed_at'       => null,
            ],
        };
    }


    public function run(IgdbClient $igdb)
    {
        $this->call([GenreSeeder::class, PlatformSeeder::class]);



        // Games

        $igdbGames = $igdb->query('games', "fields name, summary, first_release_date, cover.url,
                    involved_companies.company.name, involved_companies.developer, involved_companies.publisher,
                    genres.id, platforms.id;
                    where rating != null & cover != null & first_release_date != null;
                    sort rating desc;
                    limit 20;");

        foreach ($igdbGames as $game) {
        DB::table('games')->updateOrInsert(
                ['id' => $game['id']],
                [
                    ...Game::fromIgdb($game),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );

        if (!empty($game['genres'])) {
                foreach ($game['genres'] as $genre) {
                    $exists = DB::table('genres')->where('id', $genre['id'])->exists();
                    if ($exists) {
                        DB::table('game_genres')->updateOrInsert([
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
                        DB::table('game_platforms')->updateOrInsert([
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
                'username' => 'Admin',
                'email'    => 'admin@example.com',
                'password_hash' => 'password',
                'role'     => 'admin',
                'bio'      => 'Administrators',
                'is_private' => false,
            ],
            ['username' => 'Kaspars88',    'email' => 'kaspars@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'Man ļoti patīk lomu spēles', 'is_private' => false],
            ['username' => 'LaimaGamer',   'email' => 'laima@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'Indie spēles ir labākās lietas', 'is_private' => false],
            ['username' => 'MartinsLV',    'email' => 'martins@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'regulāri piedalos turnīros', 'is_private' => true],
            ['username' => 'ZanePlays',  'email' => 'zane@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'es spēlēju tikai Souls spēles', 'is_private' => false],
            ['username' => 'EdgarsK',      'email' => 'edgars@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'Valorant - imo, CS - 10lvl', 'is_private' => false],
            ['username' => 'IevaNeko',     'email' => 'ieva@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => '', 'is_private' => true],
            ['username' => 'RaivisPro',    'email' => 'raivis@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => '', 'is_private' => false],
            ['username' => 'AnnaPixel',    'email' => 'anna@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'Man patīk zīmēt', 'is_private' => false],
            ['username' => 'JanisSpeed',   'email' => 'janis@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'Man ļoti patīk pixel art', 'is_private' => false],
            ['username' => 'DianaRPG',     'email' => 'diana@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'profesionāls kļūdu detektīvs', 'is_private' => false],
            ['username' => 'TomsMetal',    'email' => 'toms@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'es spēlēju šausmu spēles', 'is_private' => true],
            ['username' => 'SandraQuest',  'email' => 'sandra@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => '4000 stundas vizuālo romānu', 'is_private' => false],
            ['username' => 'KristapsGG', 'email' => 'kristaps@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => 'Es skatos tikai turnīrus', 'is_private' => false],
            ['username' => 'ValentinaLV',  'email' => 'valentina@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => '', 'is_private' => false],
            ['username' => 'NorbertsX',    'email' => 'norberts@example.com', 'password_hash' => 'password', 'role' => 'user', 'bio' => '', 'is_private' => false],
        ];
 
        $userIds = [];
        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'username'      => $userData['username'],
                    'password_hash' => $userData['password_hash'],
                    'role'      => $userData['role'],
                    'bio'       => $userData['bio'],
                    'is_private'    => $userData['is_private'],
                    'email_verified' => true,
                ]
            );
            $userIds[] = $user->id;
        }
 

        // Collections

        $statuses = UserGame::STATUSES;
   

        foreach ($userIds as $userId) {
            $userGames = collect($gameIds)->shuffle()->take(rand(3, 7));
            foreach ($userGames as $gameId) {
                $status = $statuses[rand(0, 3)];
                DB::table('user_games')->updateOrInsert(
                    ['user_id' => $userId, 'game_id' => $gameId],
                    [
                        'status'     => $status,
                        'rating'     => rand(1, 5),
                        'note'       => $this->randomNote($status),
                        ...$this->randomProgress($status),
                        'added_at'   => now(),
                    ]
                );
            }
        }
   
   
        // Reviews

        
        $reviews = [
            [
                'title'   => 'Grafika ir augstākās klases',
                'content' => 'Es burtiski varu skatīties uz pikseļiem un pat neapzināties, ka tā ir datorspēle.',
            ],
            [
                'title'   => 'Labi, bet man nepatika',
                'content' => 'Spēle ir laba gan koncepcijas, gan izpildījuma ziņā, bet man noteikti bija sajūta, ka kaut kā pietrūkst.',
            ],
            [
                'title'   => 'patiesi šedevrs',
                'content' => 'Man nav vārdu, lai aprakstītu, cik laba ir šī spēle, es pat nedomāju, ka varu pienācīgi izteikt savas domas, stāstot kādam par šo spēli',
            ],
            [
                'title'   => 'izstrādātāji ir slinki',
                'content' => 'Spēle ir laba, bet šķiet neapstrādāta.',
            ],
            [
                'title'   => 'ļoti jautri spēlēt.',
                'content' => 'Esmu ar prieku spēlējis šo spēli vairāk nekā 100 stundas, un man joprojām nav garlaicīgi. Droši vien spēlēšu tikpat ilgi',
            ],
            [
                'title'   => 'Laba ideja, slikts izpildījums',
                'content' => 'Lieliska ideja un interesanta spēle, bet viss ir ļoti salauzts un darbojas ar kruķiem',
            ],
            [
                'title'   => 'Multiplayer 10/10',
                'content' => 'Spēlēt ar draugiem ir prieks, tur ir bezgalīga satura kaudze, bet spēlēt vienatnē ir diezgan garlaicīgi un vienmuļi',
            ],
            [
                'title'   => 'Sižeta attīstība prasa ilgu laiku',
                'content' => 'Beigas ir ļoti labas, bet žēl, ka, lai redzētu beigas, spēlē jāpavada vairāk nekā 50 stundas, jo spēles gaita šeit ir ļoti garlaicīga un bieži atkārtojas',
            ],
            [
                'title'   => 'pasaule ir ļoti labi attīstīta!',
                'content' => 'Vari iziet pa katrām durvīm, un visur, kur paskaties, ir tik daudz mazu detaļu!',
            ],
            [
                'title'   => 'izskatās labāk nekā spēlējas',
                'content' => 'Labāk ir noskatīties šīs spēles sižetu vietnē YouTube, nekā spēlēt šo spēli pašam.',
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
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $reviewCount++;
            }
        }

        
}}
