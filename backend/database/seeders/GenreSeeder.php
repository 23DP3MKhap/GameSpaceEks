<?php

namespace Database\Seeders;
use App\Services\IgdbClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenreSeeder extends Seeder
{
    public function run(IgdbClient $igdb)
    {
        foreach ($igdb->query('genres', "fields name; limit 500;") as $genre) {
            DB::table('genres')->updateOrInsert(
                ['id' => $genre['id']],
                ['name' => $genre['name']]
            );
        }
    }
}