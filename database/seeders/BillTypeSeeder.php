<?php

namespace Database\Seeders;

use App\Models\BillType;
use Illuminate\Database\Seeder;

class BillTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['key' => 'gas', 'label' => 'Gas', 'nature' => 'meter_flat', 'sort_order' => 1],
            ['key' => 'water', 'label' => 'Water', 'nature' => 'meter_common', 'sort_order' => 2],
            ['key' => 'cleaner', 'label' => 'Cleaner', 'nature' => 'other', 'sort_order' => 3],
            ['key' => 'common_electricity', 'label' => 'Common electricity', 'nature' => 'other', 'sort_order' => 4],
            ['key' => 'garbage', 'label' => 'Garbage', 'nature' => 'other', 'sort_order' => 5],
        ];

        foreach ($types as $type) {
            BillType::query()->updateOrCreate(
                ['key' => $type['key']],
                $type + ['is_active' => true]
            );
        }
    }
}
