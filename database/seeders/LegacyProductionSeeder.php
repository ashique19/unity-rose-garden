<?php

namespace Database\Seeders;

use App\Models\BillType;
use App\Models\Collection;
use App\Models\Flat;
use App\Models\GasMeterReading;
use App\Models\MonthlyStatement;
use App\Models\StatementLine;
use Illuminate\Database\Seeder;

/**
 * Seeds May/June 2026 gas history derived from production_database.sql.
 */
class LegacyProductionSeeder extends Seeder
{
    public function run(): void
    {
        $gasType = BillType::query()->where('key', 'gas')->firstOrFail();
        $flatsByName = Flat::query()->get()->keyBy('name');

        // Legacy flat_id → name (production dump order)
        $flatIdToName = [
            1 => '2A', 2 => '2B', 3 => '3A', 4 => '3B',
            5 => '4A', 6 => '4B', 7 => '5A', 8 => '5B',
            9 => '6A', 10 => '6B', 11 => '7A', 12 => '7B',
            13 => '8A', 14 => '8B', 15 => '9A', 16 => '9B',
            17 => '10A', 18 => '10B',
        ];

        $bills = [
            '2026-05-01' => [
                'price_per_kg' => 146.00,
                'reading_date' => '2026-05-31',
                'details' => [
                    // flat_id, prev, curr, used_m3, used_kg, payment_status
                    [1, 37.40, 40.73, 3.33, 6.93, 'paid'],
                    [2, 22.46, 23.09, 0.63, 1.31, 'paid'],
                    [4, 42.62, 44.57, 1.95, 4.06, 'paid'],
                    [5, 82.64, 87.56, 4.92, 10.23, 'paid'],
                    [6, 2.41, 8.06, 5.65, 11.75, 'paid'],
                    [9, 37.25, 38.75, 1.50, 3.12, 'paid'],
                    [10, 26.16, 26.94, 0.78, 1.62, 'paid'],
                    [11, 60.60, 62.91, 2.31, 4.80, 'paid'],
                    [12, 99.46, 103.84, 4.38, 9.11, 'paid'],
                    [13, 58.36, 62.92, 4.56, 9.48, 'paid'],
                    [14, 37.90, 38.68, 0.78, 1.62, 'paid'],
                    [15, 49.84, 50.33, 0.49, 1.02, 'paid'],
                    [16, 44.91, 48.47, 3.56, 7.40, 'paid'],
                    [17, 3.08, 9.72, 6.64, 13.81, 'paid'],
                    [18, 96.22, 100.40, 4.18, 8.69, 'paid'],
                ],
            ],
            '2026-06-01' => [
                'price_per_kg' => 148.00,
                'reading_date' => '2026-06-30',
                'details' => [
                    [1, 40.73, 46.28, 5.55, 11.53, 'unpaid'],
                    [2, 23.09, 23.09, 0.00, 0.00, 'unpaid'],
                    [4, 44.57, 50.00, 5.43, 11.29, 'unpaid'],
                    [5, 87.56, 98.46, 10.90, 22.65, 'unpaid'],
                    [6, 8.06, 19.19, 11.13, 23.13, 'unpaid'],
                    [9, 38.75, 43.45, 4.70, 9.77, 'unpaid'],
                    [10, 26.94, 31.81, 4.87, 10.12, 'unpaid'],
                    [11, 62.91, 69.31, 6.40, 13.30, 'unpaid'],
                    [12, 103.84, 114.21, 10.37, 21.55, 'unpaid'],
                    [13, 62.92, 73.02, 10.10, 20.99, 'unpaid'],
                    [14, 38.68, 44.50, 5.82, 12.10, 'unpaid'],
                    [15, 50.33, 56.87, 6.54, 13.59, 'unpaid'],
                    [16, 48.47, 57.66, 9.19, 19.10, 'unpaid'],
                    [17, 9.72, 20.45, 10.73, 22.30, 'unpaid'],
                    [18, 100.40, 112.29, 11.89, 24.71, 'unpaid'],
                ],
            ],
        ];

        // Offline flats had meter readings in production but no gas bills.
        // Import readings only (no statement lines).
        $offlineReadings = [
            '2026-05-01' => [
                'reading_date' => '2026-05-31',
                // flat_id, prev, curr
                'details' => [
                    [3, 0.00, 0.00],   // 3A
                    [7, 21.18, 22.08], // 5A
                    [8, 49.98, 53.01], // 5B
                ],
            ],
            '2026-06-01' => [
                'reading_date' => '2026-06-30',
                'details' => [
                    [3, 0.00, 0.00],
                    [7, 22.08, 28.39],
                    [8, 53.01, 61.92],
                ],
            ],
        ];

        foreach ($offlineReadings as $billMonth => $pack) {
            foreach ($pack['details'] as $row) {
                [$legacyFlatId, $prev, $curr] = $row;
                $flat = $flatsByName->get($flatIdToName[$legacyFlatId] ?? '');
                if (! $flat) {
                    continue;
                }

                GasMeterReading::query()->updateOrCreate(
                    [
                        'flat_id' => $flat->id,
                        'bill_month' => $billMonth,
                    ],
                    [
                        'reading_date' => $pack['reading_date'],
                        'previous_m3' => $prev,
                        'current_m3' => $curr,
                        'confirmed_m3' => $curr,
                    ]
                );
            }
        }

        // Also mirror production point readings into legacy meter_readings (idempotent).
        $this->seedLegacyMeterReadings($flatIdToName, $flatsByName);

        foreach ($bills as $billMonth => $bill) {
            $monthLabel = date('M Y', strtotime($billMonth));

            foreach ($bill['details'] as $row) {
                [$legacyFlatId, $prev, $curr, $usedM3, $usedKg, $paymentStatus] = $row;
                $name = $flatIdToName[$legacyFlatId];
                $flat = $flatsByName->get($name);

                if (! $flat) {
                    continue;
                }

                GasMeterReading::query()->updateOrCreate(
                    [
                        'flat_id' => $flat->id,
                        'bill_month' => $billMonth,
                    ],
                    [
                        'reading_date' => $bill['reading_date'],
                        'previous_m3' => $prev,
                        'current_m3' => $curr,
                        'confirmed_m3' => $curr,
                    ]
                );

                $statement = MonthlyStatement::query()->updateOrCreate(
                    [
                        'flat_id' => $flat->id,
                        'bill_month' => $billMonth,
                    ],
                    []
                );

                $amount = round($usedKg * $bill['price_per_kg'], 2);

                StatementLine::query()->updateOrCreate(
                    [
                        'monthly_statement_id' => $statement->id,
                        'bill_type_key' => 'gas',
                    ],
                    [
                        'bill_type_id' => $gasType->id,
                        'label' => 'Gas – '.$monthLabel,
                        'quantity' => $usedKg,
                        'rate' => $bill['price_per_kg'],
                        'amount' => $amount,
                        'note' => null,
                        'enabled' => true,
                        'meta' => [
                            'bill_month' => $billMonth,
                            'reading_date' => $bill['reading_date'],
                            'previous_m3' => $prev,
                            'current_m3' => $curr,
                            'consumed_m3' => $usedM3,
                            'consumed_kg' => $usedKg,
                            'rate_per_kg' => $bill['price_per_kg'],
                            'total' => $amount,
                        ],
                    ]
                );

                if ($paymentStatus === 'paid' && $amount > 0) {
                    Collection::query()->firstOrCreate(
                        [
                            'monthly_statement_id' => $statement->id,
                            'note' => 'Imported from legacy paid status',
                        ],
                        [
                            'amount' => $amount,
                            'collected_on' => $bill['reading_date'],
                        ]
                    );
                }
            }
        }
    }

    /**
     * @param  array<int, string>  $flatIdToName
     * @param  \Illuminate\Support\Collection<string, Flat>  $flatsByName
     */
    private function seedLegacyMeterReadings(array $flatIdToName, $flatsByName): void
    {
        // From production_database.sql meter_readings
        $rows = [
            // April baseline
            [17, '2026-04-30', 3.08], [18, '2026-04-30', 96.22], [1, '2026-04-30', 37.40],
            [2, '2026-04-30', 22.46], [3, '2026-04-30', 0.00], [4, '2026-04-30', 42.62],
            [5, '2026-04-30', 82.64], [6, '2026-04-30', 2.41], [7, '2026-04-30', 21.18],
            [8, '2026-04-30', 49.98], [9, '2026-04-30', 37.25], [10, '2026-04-30', 26.16],
            [11, '2026-04-30', 60.60], [12, '2026-04-30', 99.46], [13, '2026-04-30', 58.36],
            [14, '2026-04-30', 37.90], [15, '2026-04-30', 49.84], [16, '2026-04-30', 44.91],
            // May
            [9, '2026-05-31', 38.75], [10, '2026-05-31', 26.94], [4, '2026-05-31', 44.57],
            [5, '2026-05-31', 87.56], [7, '2026-05-31', 22.08], [2, '2026-05-31', 23.09],
            [11, '2026-05-31', 62.91], [3, '2026-05-31', 0.00], [12, '2026-05-31', 103.84],
            [17, '2026-05-31', 9.72], [13, '2026-05-31', 62.92], [6, '2026-05-31', 8.06],
            [15, '2026-05-31', 50.33], [14, '2026-05-31', 38.68], [18, '2026-05-31', 100.40],
            [16, '2026-05-31', 48.47], [1, '2026-05-31', 40.73], [8, '2026-05-31', 53.01],
            // June
            [16, '2026-06-30', 57.66], [14, '2026-06-30', 44.50], [12, '2026-06-30', 114.21],
            [10, '2026-06-30', 31.81], [8, '2026-06-30', 61.92], [6, '2026-06-30', 19.19],
            [4, '2026-06-30', 50.00], [1, '2026-06-30', 46.28], [3, '2026-06-30', 0.00],
            [5, '2026-06-30', 98.46], [7, '2026-06-30', 28.39], [9, '2026-06-30', 43.45],
            [11, '2026-06-30', 69.31], [13, '2026-06-30', 73.02], [15, '2026-06-30', 56.87],
            [17, '2026-06-30', 20.45], [18, '2026-06-30', 112.29], [2, '2026-06-30', 23.09],
        ];

        foreach ($rows as [$legacyFlatId, $date, $unit]) {
            $flat = $flatsByName->get($flatIdToName[$legacyFlatId] ?? '');
            if (! $flat) {
                continue;
            }

            \App\Models\MeterReading::query()->updateOrCreate(
                [
                    'flat_id' => $flat->id,
                    'reading_date' => $date,
                ],
                [
                    'reading_unit' => $unit,
                ]
            );
        }
    }
}
