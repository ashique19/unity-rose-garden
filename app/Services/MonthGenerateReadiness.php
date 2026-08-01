<?php

namespace App\Services;

use App\Models\BillType;
use App\Models\ChargeTemplate;
use App\Models\CommonMeterReading;
use App\Models\CustomCharge;
use App\Models\Flat;
use App\Models\GasMeterReading;
use Carbon\Carbon;

class MonthGenerateReadiness
{
    /**
     * Summarize whether enabled charges are entered for the bill month.
     *
     * @return array{
     *     ready: bool,
     *     gas: array{required: int, entered: int, pending_flats: list<string>},
     *     common: list<array{
     *         key: string,
     *         label: string,
     *         entered: bool,
     *         enabled_flats: int
     *     }>,
     *     other: list<array{
     *         key: string,
     *         label: string,
     *         covered_by_template: bool,
     *         required: int,
     *         entered: int,
     *         pending_flats: list<string>
     *     }>,
     *     pending_count: int
     * }
     */
    public function forMonth(Carbon|string $billMonth): array
    {
        $monthKey = Carbon::parse($billMonth)->startOfMonth()->toDateString();

        $flats = Flat::query()
            ->with('billTypeSettings.billType')
            ->orderBy('name')
            ->get()
            ->sortBy(function (Flat $flat) {
                preg_match('/^(\d+)([A-Z])$/i', $flat->name, $m);

                return [isset($m[1]) ? (int) $m[1] : 0, $m[2] ?? $flat->name];
            })
            ->values();

        $gasReadings = GasMeterReading::query()
            ->whereDate('bill_month', $monthKey)
            ->pluck('flat_id')
            ->flip();

        $gasPending = [];
        foreach ($flats as $flat) {
            if (! $flat->isBillTypeEnabled('gas')) {
                continue;
            }
            if (! $gasReadings->has($flat->id)) {
                $gasPending[] = $flat->name;
            }
        }

        $gasRequired = $flats->filter(fn (Flat $flat) => $flat->isBillTypeEnabled('gas'))->count();
        $gasEntered = $gasRequired - count($gasPending);

        $common = [];
        foreach (BillType::activeCommonMeters() as $commonType) {
            $enabledFlats = $flats->filter(
                fn (Flat $flat) => $flat->isBillTypeEnabled($commonType->key)
            )->count();

            $entered = CommonMeterReading::query()
                ->where('meter_key', $commonType->key)
                ->whereDate('bill_month', $monthKey)
                ->exists();

            $common[] = [
                'key' => $commonType->key,
                'label' => $commonType->label,
                'entered' => $entered,
                'enabled_flats' => $enabledFlats,
            ];
        }

        $buildingWideTypeIds = ChargeTemplate::query()
            ->where('is_building_wide', true)
            ->whereNotNull('bill_type_id')
            ->pluck('bill_type_id')
            ->all();

        $customCharges = CustomCharge::query()
            ->whereDate('charge_month', $monthKey)
            ->get(['flat_id', 'bill_type_id']);

        $customByFlatType = [];
        foreach ($customCharges as $charge) {
            if ($charge->bill_type_id) {
                $customByFlatType[$charge->flat_id][$charge->bill_type_id] = true;
            }
        }

        $otherTypes = BillType::query()
            ->ordered()
            ->where('is_active', true)
            ->otherCharges()
            ->get();

        $other = [];
        $otherPendingTotal = 0;

        foreach ($otherTypes as $billType) {
            $enabledFlats = $flats->filter(fn (Flat $flat) => $flat->isBillTypeEnabled($billType->key));
            if ($enabledFlats->isEmpty()) {
                continue;
            }

            $coveredByTemplate = in_array($billType->id, $buildingWideTypeIds, true);

            if ($coveredByTemplate) {
                $other[] = [
                    'key' => $billType->key,
                    'label' => $billType->label,
                    'covered_by_template' => true,
                    'required' => $enabledFlats->count(),
                    'entered' => $enabledFlats->count(),
                    'pending_flats' => [],
                ];

                continue;
            }

            $pending = [];
            foreach ($enabledFlats as $flat) {
                if (! isset($customByFlatType[$flat->id][$billType->id])) {
                    $pending[] = $flat->name;
                }
            }

            $required = $enabledFlats->count();
            $entered = $required - count($pending);
            $otherPendingTotal += count($pending);

            $other[] = [
                'key' => $billType->key,
                'label' => $billType->label,
                'covered_by_template' => false,
                'required' => $required,
                'entered' => $entered,
                'pending_flats' => $pending,
            ];
        }

        $pendingCount = count($gasPending) + $otherPendingTotal;

        return [
            'ready' => $pendingCount === 0,
            'gas' => [
                'required' => $gasRequired,
                'entered' => $gasEntered,
                'pending_flats' => $gasPending,
            ],
            'common' => $common,
            'other' => $other,
            'pending_count' => $pendingCount,
        ];
    }
}
