<?php

namespace App\Services;

use App\Models\BillType;
use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\CommonMeterReading;
use App\Models\CustomCharge;
use App\Models\Flat;
use App\Models\GasMeterReading;
use App\Models\MonthlyStatement;
use App\Models\StatementLine;
use App\Support\WaterShareCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MonthStatementGenerator
{
    /**
     * Upsert monthly statements and lines for all flats.
     * Collections on existing statements are never deleted.
     *
     * @return array{statements: int, gas_lines: int, water_lines: int, other_lines: int, skipped_gas: int}
     */
    public function generate(Carbon|string $billMonth, float $pricePerKg): array
    {
        $billMonth = Carbon::parse($billMonth)->startOfMonth();
        $monthKey = $billMonth->toDateString();
        $monthLabel = $billMonth->format('M Y');

        $building = Building::query()->first();
        $m3ToKg = (float) ($building?->m3_to_kg_rate ?? 2.08);

        $gasType = BillType::query()->where('key', 'gas')->first();
        $waterType = BillType::query()->where('key', 'water')->first();
        $billTypes = BillType::query()->ordered()->get()->keyBy('key');
        $buildingWideTemplates = ChargeTemplate::query()
            ->where('is_building_wide', true)
            ->with('billType')
            ->get();

        $waterReading = CommonMeterReading::query()
            ->where('meter_key', 'water')
            ->whereDate('bill_month', $monthKey)
            ->first();

        $flats = Flat::query()->with('billTypeSettings.billType')->orderBy('id')->get();
        $waterEnabledFlats = $flats->filter(fn (Flat $flat) => $flat->isBillTypeEnabled('water'));
        $waterEnabledCount = $waterEnabledFlats->count();

        $waterShare = null;
        if ($waterReading) {
            if ($waterEnabledCount === 0) {
                throw new InvalidArgumentException('Cannot generate water lines: no water-enabled flats.');
            }
            $waterShare = WaterShareCalculator::share($waterReading->total_amount, $waterEnabledCount);
        }

        $stats = [
            'statements' => 0,
            'gas_lines' => 0,
            'water_lines' => 0,
            'other_lines' => 0,
            'skipped_gas' => 0,
        ];

        DB::transaction(function () use (
            $monthKey,
            $monthLabel,
            $pricePerKg,
            $m3ToKg,
            $gasType,
            $waterType,
            $billTypes,
            $buildingWideTemplates,
            $flats,
            $waterReading,
            $waterShare,
            &$stats
        ) {
            foreach ($flats as $flat) {
                $statement = MonthlyStatement::query()
                    ->where('flat_id', $flat->id)
                    ->whereDate('bill_month', $monthKey)
                    ->first();

                if (! $statement) {
                    $statement = MonthlyStatement::query()->create([
                        'flat_id' => $flat->id,
                        'bill_month' => $monthKey,
                    ]);
                }
                $stats['statements']++;

                // --- Gas line ---
                if ($gasType && $flat->isBillTypeEnabled('gas')) {
                    $reading = GasMeterReading::query()
                        ->where('flat_id', $flat->id)
                        ->whereDate('bill_month', $monthKey)
                        ->first();

                    if ($reading) {
                        $consumedM3 = $reading->consumedM3();
                        $consumedKg = round($consumedM3 * $m3ToKg, 2);
                        $amount = round($consumedKg * $pricePerKg, 2);
                        $current = (float) ($reading->confirmed_m3 ?? $reading->current_m3);

                        StatementLine::query()->updateOrCreate(
                            [
                                'monthly_statement_id' => $statement->id,
                                'bill_type_key' => 'gas',
                            ],
                            [
                                'bill_type_id' => $gasType->id,
                                'label' => 'Gas – '.$monthLabel,
                                'quantity' => $consumedKg,
                                'rate' => $pricePerKg,
                                'amount' => $amount,
                                'note' => null,
                                'enabled' => true,
                                'meta' => [
                                    'bill_month' => $monthKey,
                                    'reading_date' => optional($reading->reading_date)?->toDateString(),
                                    'previous_m3' => (float) $reading->previous_m3,
                                    'current_m3' => $current,
                                    'consumed_m3' => $consumedM3,
                                    'consumed_kg' => $consumedKg,
                                    'rate_per_kg' => $pricePerKg,
                                    'm3_to_kg_rate' => $m3ToKg,
                                    'total' => $amount,
                                ],
                            ]
                        );
                        $stats['gas_lines']++;
                    } else {
                        StatementLine::query()
                            ->where('monthly_statement_id', $statement->id)
                            ->where('bill_type_key', 'gas')
                            ->delete();
                        $stats['skipped_gas']++;
                    }
                } else {
                    StatementLine::query()
                        ->where('monthly_statement_id', $statement->id)
                        ->where('bill_type_key', 'gas')
                        ->delete();
                }

                // --- Water line ---
                if ($waterType && $waterReading && $waterShare !== null && $flat->isBillTypeEnabled('water')) {
                    StatementLine::query()->updateOrCreate(
                        [
                            'monthly_statement_id' => $statement->id,
                            'bill_type_key' => 'water',
                        ],
                        [
                            'bill_type_id' => $waterType->id,
                            'label' => 'Common water – '.$monthLabel,
                            'quantity' => 1,
                            'rate' => $waterShare,
                            'amount' => $waterShare,
                            'note' => $waterReading->note,
                            'enabled' => true,
                            'meta' => [
                                'common_total' => (float) $waterReading->total_amount,
                                'share' => $waterShare,
                                'source' => 'common_meter',
                            ],
                        ]
                    );
                    $stats['water_lines']++;
                } else {
                    StatementLine::query()
                        ->where('monthly_statement_id', $statement->id)
                        ->where('bill_type_key', 'water')
                        ->delete();
                }

                // --- Other lines (not gas, not water) ---
                StatementLine::query()
                    ->where('monthly_statement_id', $statement->id)
                    ->whereNotIn('bill_type_key', ['gas', 'water'])
                    ->delete();

                $charges = $this->resolveOtherCharges($flat, $monthKey, $buildingWideTemplates);

                foreach ($charges as $charge) {
                    $typeKey = $charge['bill_type_key'];
                    if (! $flat->isBillTypeEnabled($typeKey)) {
                        continue;
                    }

                    $billType = $billTypes->get($typeKey);

                    StatementLine::query()->create([
                        'monthly_statement_id' => $statement->id,
                        'bill_type_id' => $billType?->id,
                        'bill_type_key' => $typeKey,
                        'label' => $charge['label'],
                        'quantity' => 1,
                        'rate' => $charge['amount'],
                        'amount' => $charge['amount'],
                        'note' => $charge['notes'],
                        'enabled' => true,
                        'meta' => [
                            'source' => $charge['source'],
                        ],
                    ]);
                    $stats['other_lines']++;
                }
            }
        });

        return $stats;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ChargeTemplate>  $buildingWideTemplates
     * @return list<array{bill_type_key: string, label: string, amount: float, notes: ?string, source: string}>
     */
    private function resolveOtherCharges(Flat $flat, string $monthKey, $buildingWideTemplates): array
    {
        $charges = [];
        $coveredTypeIds = [];

        $customCharges = CustomCharge::query()
            ->with('billType')
            ->where('flat_id', $flat->id)
            ->whereDate('charge_month', $monthKey)
            ->get();

        foreach ($customCharges as $custom) {
            $typeKey = $custom->billType?->key ?? 'other';
            if (in_array($typeKey, ['gas', 'water'], true)) {
                continue;
            }

            $charges[] = [
                'bill_type_key' => $typeKey,
                'label' => $custom->label,
                'amount' => (float) $custom->amount,
                'notes' => $custom->notes,
                'source' => 'custom',
            ];

            if ($custom->bill_type_id) {
                $coveredTypeIds[] = $custom->bill_type_id;
            }
        }

        foreach ($buildingWideTemplates as $template) {
            $typeKey = $template->billType?->key;
            if (! $typeKey || in_array($typeKey, ['gas', 'water'], true)) {
                continue;
            }
            if ($template->bill_type_id && in_array($template->bill_type_id, $coveredTypeIds, true)) {
                continue;
            }

            $charges[] = [
                'bill_type_key' => $typeKey,
                'label' => $template->label.' – '.Carbon::parse($monthKey)->format('M Y'),
                'amount' => (float) $template->default_amount,
                'notes' => null,
                'source' => 'template',
            ];
        }

        return $charges;
    }
}
