<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            BuildingSeeder::class,
            BillTypeSeeder::class,
            RoleSeeder::class,
            FlatSeeder::class,
            UserSeeder::class,
            ChargeTemplateSeeder::class,
            LegacyProductionSeeder::class,
        ]);
    }
}
