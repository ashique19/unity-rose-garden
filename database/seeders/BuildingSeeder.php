<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    public function run(): void
    {
        Building::query()->updateOrCreate(
            ['name' => 'Unity Rose Garden'],
            [
                'm3_to_kg_rate' => 2.0800,
                'opening_balance' => 0,
            ]
        );
    }
}
