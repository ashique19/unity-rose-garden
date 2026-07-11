<?php

namespace Database\Seeders;

use App\Models\BillType;
use App\Models\Flat;
use App\Models\FlatBillTypeSetting;
use Illuminate\Database\Seeder;

class FlatSeeder extends Seeder
{
    /**
     * Production flats from production_database.sql (ids preserved by insert order).
     * Offline flats: 3A, 5A, 5B → gas disabled.
     */
    public function run(): void
    {
        $flats = [
            ['name' => '2A', 'status' => 'online'],
            ['name' => '2B', 'status' => 'online'],
            ['name' => '3A', 'status' => 'offline'],
            ['name' => '3B', 'status' => 'online'],
            ['name' => '4A', 'status' => 'online'],
            ['name' => '4B', 'status' => 'online'],
            ['name' => '5A', 'status' => 'offline'],
            ['name' => '5B', 'status' => 'offline'],
            ['name' => '6A', 'status' => 'online'],
            ['name' => '6B', 'status' => 'online'],
            ['name' => '7A', 'status' => 'online'],
            ['name' => '7B', 'status' => 'online'],
            ['name' => '8A', 'status' => 'online'],
            ['name' => '8B', 'status' => 'online'],
            ['name' => '9A', 'status' => 'online'],
            ['name' => '9B', 'status' => 'online'],
            ['name' => '10A', 'status' => 'online'],
            ['name' => '10B', 'status' => 'online'],
        ];

        foreach ($flats as $index => $data) {
            $flatNumber = $index + 1;
            Flat::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'contact_name' => 'Resident '.$data['name'],
                    'phone' => sprintf('0170000%04d', $flatNumber),
                    'status' => $data['status'],
                ]
            );
        }

        $billTypes = BillType::query()->get();
        $gasType = $billTypes->firstWhere('key', 'gas');

        foreach (Flat::query()->orderBy('id')->get() as $flat) {
            foreach ($billTypes as $billType) {
                $enabled = true;
                if ($gasType && $billType->id === $gasType->id && $flat->status === 'offline') {
                    $enabled = false;
                }

                FlatBillTypeSetting::query()->updateOrCreate(
                    [
                        'flat_id' => $flat->id,
                        'bill_type_id' => $billType->id,
                    ],
                    ['enabled' => $enabled]
                );
            }
        }
    }
}
