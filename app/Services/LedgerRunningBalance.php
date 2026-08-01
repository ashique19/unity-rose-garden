<?php

namespace App\Services;

use App\Models\AccountLedgerEntry;
use App\Models\Building;
use Illuminate\Support\Collection;

class LedgerRunningBalance
{
    /**
     * Balance before/after for each entry, based on full cashbook history
     * (opening + all prior cash in/out), not only the filtered page set.
     *
     * @param  Collection<int, AccountLedgerEntry>  $entries
     * @return array<int, array{before: float, after: float}>
     */
    public function forEntries(Collection $entries, ?Building $building = null): array
    {
        if ($entries->isEmpty()) {
            return [];
        }

        $building ??= Building::query()->first();
        $opening = (float) ($building?->opening_balance ?? 0);

        $chrono = $entries
            ->sortBy(fn (AccountLedgerEntry $entry) => sprintf(
                '%s-%010d',
                $entry->entry_date?->format('Y-m-d') ?? '0000-00-00',
                (int) $entry->id
            ))
            ->values();

        /** @var AccountLedgerEntry $latest */
        $latest = $chrono->last();
        $latestDate = $latest->entry_date?->toDateString();

        $history = AccountLedgerEntry::query()
            ->where(function ($query) use ($latest, $latestDate) {
                $query->whereDate('entry_date', '<', $latestDate)
                    ->orWhere(function ($q) use ($latest, $latestDate) {
                        $q->whereDate('entry_date', $latestDate)
                            ->where('id', '<=', $latest->id);
                    });
            })
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get(['id', 'type', 'amount', 'entry_date']);

        $wantedIds = $entries->pluck('id')->map(fn ($id) => (int) $id)->all();
        $balance = $opening;
        $result = [];

        foreach ($history as $entry) {
            $before = $balance;
            $delta = $entry->type === AccountLedgerEntry::TYPE_CASH_IN
                ? (float) $entry->amount
                : -(float) $entry->amount;
            $after = $before + $delta;
            $balance = $after;

            $entryId = (int) $entry->id;
            if (in_array($entryId, $wantedIds, true)) {
                $result[$entryId] = [
                    'before' => $before,
                    'after' => $after,
                ];
            }
        }

        return $result;
    }
}
