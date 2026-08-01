<?php

namespace App\Services;

use App\Models\BillType;
use App\Models\MonthlyStatement;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GeneratedBillsTree
{
    /**
     * Previous generated months with nested bill-head → flat breakdown.
     *
     * @return Collection<int, array{
     *     month: Carbon,
     *     month_key: string,
     *     statement_count: int,
     *     total: float,
     *     heads: Collection<int, array{
     *         key: string,
     *         label: string,
     *         total: float,
     *         line_count: int,
     *         flats: Collection<int, array{flat: string, amount: float, note: ?string}>
     *     }>
     * }>
     */
    public function months(): Collection
    {
        $statements = MonthlyStatement::query()
            ->with([
                'flat:id,name',
                'lines' => fn ($q) => $q->where('enabled', true)->orderBy('id'),
            ])
            ->orderByDesc('bill_month')
            ->orderBy('flat_id')
            ->get();

        if ($statements->isEmpty()) {
            return collect();
        }

        $labelsByKey = BillType::query()
            ->ordered()
            ->pluck('label', 'key');

        $sortOrder = BillType::query()
            ->ordered()
            ->pluck('sort_order', 'key');

        return $statements
            ->groupBy(fn (MonthlyStatement $statement) => $statement->bill_month->format('Y-m'))
            ->map(function (Collection $monthStatements, string $monthKey) use ($labelsByKey, $sortOrder) {
                $month = BillMonth::parse($monthKey);

                $headBuckets = [];

                foreach ($monthStatements as $statement) {
                    $flatName = $statement->flat?->name ?? ('#'.$statement->flat_id);

                    foreach ($statement->lines as $line) {
                        $key = $line->bill_type_key ?: 'other';
                        if (! isset($headBuckets[$key])) {
                            $headBuckets[$key] = [
                                'key' => $key,
                                'label' => $labelsByKey[$key] ?? ($line->label ?: ucfirst(str_replace('_', ' ', $key))),
                                'total' => 0.0,
                                'line_count' => 0,
                                'flats' => [],
                            ];
                        }

                        $amount = (float) $line->amount;
                        $headBuckets[$key]['total'] += $amount;
                        $headBuckets[$key]['line_count']++;
                        $headBuckets[$key]['flats'][] = [
                            'flat' => $flatName,
                            'amount' => $amount,
                            'note' => $line->note,
                        ];
                    }
                }

                $heads = collect($headBuckets)
                    ->sortBy(fn (array $head) => [
                        (int) ($sortOrder[$head['key']] ?? 999),
                        $head['label'],
                    ])
                    ->values()
                    ->map(function (array $head) {
                        $head['flats'] = collect($head['flats'])
                            ->sortBy('flat', SORT_NATURAL)
                            ->values();

                        return $head;
                    });

                return [
                    'month' => $month,
                    'month_key' => $monthKey,
                    'statement_count' => $monthStatements->count(),
                    'total' => (float) $heads->sum('total'),
                    'heads' => $heads,
                ];
            })
            ->values();
    }
}
