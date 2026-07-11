<?php

namespace Database\Seeders;

use App\Models\ExpenseHead;
use Illuminate\Database\Seeder;

class ExpenseHeadSeeder extends Seeder
{
    public function run(): void
    {
        $heads = [
            ['key' => 'salary', 'label' => 'Salary', 'sort_order' => 10],
            ['key' => 'repair', 'label' => 'Repair', 'sort_order' => 20],
            ['key' => 'supplies', 'label' => 'Supplies', 'sort_order' => 30],
            ['key' => 'common_electricity_bill', 'label' => 'Common electricity bill', 'sort_order' => 40],
            ['key' => 'gas_purchase', 'label' => 'Gas purchase', 'sort_order' => 50],
            ['key' => 'water_bill', 'label' => 'Water bill', 'sort_order' => 60],
            ['key' => 'cleaner', 'label' => 'Cleaner', 'sort_order' => 70],
            ['key' => 'garbage', 'label' => 'Garbage', 'sort_order' => 80],
            ['key' => 'maintenance', 'label' => 'Maintenance', 'sort_order' => 90],
            ['key' => 'utility', 'label' => 'Utility', 'sort_order' => 100],
            ['key' => 'misc', 'label' => 'Misc', 'sort_order' => 110],
        ];

        foreach ($heads as $head) {
            ExpenseHead::query()->updateOrCreate(
                ['key' => $head['key']],
                [
                    'label' => $head['label'],
                    'sort_order' => $head['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
