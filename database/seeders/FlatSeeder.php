<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('flats')->insert([
            ['name'=>'2A'],
            ['name'=>'2B'],
            ['name'=>'3A'],
            ['name'=>'3B'],
            ['name'=>'4A'],
            ['name'=>'4B'],
            ['name'=>'5A'],
            ['name'=>'5B'],
            ['name'=>'6A'],
            ['name'=>'6B'],
            ['name'=>'7A'],
            ['name'=>'7B'],
            ['name'=>'8A'],
            ['name'=>'8B'],
            ['name'=>'9A'],
            ['name'=>'9B'],
            ['name'=>'10A'],
            ['name'=>'10B'],
        ]);
    }
}
