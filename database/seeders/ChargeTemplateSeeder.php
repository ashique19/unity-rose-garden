<?php

namespace Database\Seeders;

use App\Models\BillType;
use App\Models\ChargeTemplate;
use Illuminate\Database\Seeder;

class ChargeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $cleaner = BillType::query()->where('key', 'cleaner')->first();
        $electricity = BillType::query()->where('key', 'common_electricity')->first();
        $garbage = BillType::query()->where('key', 'garbage')->first();

        $templates = [
            [
                'charge_key' => 'cleaner',
                'label' => 'Cleaner',
                'default_amount' => 200,
                'is_building_wide' => true,
                'bill_type_id' => $cleaner?->id,
            ],
            [
                'charge_key' => 'common_electricity',
                'label' => 'Common electricity',
                'default_amount' => 150,
                'is_building_wide' => true,
                'bill_type_id' => $electricity?->id,
            ],
            [
                'charge_key' => 'garbage',
                'label' => 'Garbage',
                'default_amount' => 100,
                'is_building_wide' => true,
                'bill_type_id' => $garbage?->id,
            ],
        ];

        foreach ($templates as $template) {
            ChargeTemplate::query()->updateOrCreate(
                ['charge_key' => $template['charge_key']],
                $template
            );
        }
    }
}
