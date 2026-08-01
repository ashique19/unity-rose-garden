<?php

namespace Database\Seeders;

use App\Models\BillType;
use Illuminate\Database\Seeder;

class BillTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['key' => 'gas', 'label' => 'Gas', 'nature' => BillType::NATURE_METER_FLAT, 'sort_order' => 1],
            ['key' => 'deep_tubewell', 'label' => 'Deep tube-well', 'nature' => BillType::NATURE_METER_COMMON, 'sort_order' => 2],
            ['key' => 'wasa', 'label' => 'WASA', 'nature' => BillType::NATURE_METER_COMMON, 'sort_order' => 3],
            ['key' => 'cleaner', 'label' => 'Cleaner', 'nature' => BillType::NATURE_OTHER, 'sort_order' => 4],
            ['key' => 'common_electricity', 'label' => 'Common electricity', 'nature' => BillType::NATURE_OTHER, 'sort_order' => 5],
            ['key' => 'garbage', 'label' => 'Garbage', 'nature' => BillType::NATURE_OTHER, 'sort_order' => 6],
        ];

        foreach ($types as $type) {
            BillType::query()->updateOrCreate(
                ['key' => $type['key']],
                $type + ['is_active' => true]
            );
        }

        // Remove legacy single water type if still present after dual split.
        BillType::query()->where('key', 'water')->delete();
    }
}
