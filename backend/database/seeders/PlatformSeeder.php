<?php

namespace Database\Seeders;
use App\Services\IgdbClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    public function run(IgdbClient $igdb)
    {
        foreach ($igdb->query('platforms', "fields name; limit 500;") as $platform) {
            DB::table('platforms')->updateOrInsert(
                ['id' => $platform['id']],
                ['name' => $platform['name']]
            );
        }
    }
}
