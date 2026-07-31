<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedgerEntry;
use App\Models\Building;
use App\Models\Collection;
use App\Models\Flat;
use App\Models\MonthlyStatement;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->query('month'));
        $monthKey = $month->toDateString();

        $statements = MonthlyStatement::query()
            ->with(['flat', 'lines', 'collections'])
            ->whereDate('bill_month', $monthKey)
            ->get()
            ->sortBy(function (MonthlyStatement $statement) {
                $name = $statement->flat?->name ?? '';
                preg_match('/^(\d+)([A-Z])$/i', $name, $m);

                return [isset($m[1]) ? (int) $m[1] : 0, $m[2] ?? $name];
            })
            ->values();

        $flats = Flat::query()->orderBy('name')->get();
        $building = Building::query()->first();

        return view('admin.collections.index', [
            'selectedMonth' => $month,
            'statements' => $statements,
            'flats' => $flats,
            'availableBalance' => $building ? $building->balanceAmount() : 0.0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'monthly_statement_id' => ['required', 'integer', 'exists:monthly_statements,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'collected_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'post_to_ledger' => ['nullable', 'boolean'],
        ]);

        $statement = MonthlyStatement::query()->with('flat')->findOrFail($data['monthly_statement_id']);

        DB::transaction(function () use ($data, $statement, $request) {
            $building = Building::query()->firstOrFail();
            $balanceBefore = $building->balanceAmount();
            $postToLedger = $request->boolean('post_to_ledger');
            $amount = (float) $data['amount'];
            $balanceAfter = $postToLedger ? $balanceBefore + $amount : $balanceBefore;

            $collection = Collection::query()->create([
                'monthly_statement_id' => $statement->id,
                'amount' => $data['amount'],
                'collected_on' => $data['collected_on'],
                'note' => $data['note'] ?? null,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            if ($postToLedger) {
                AccountLedgerEntry::query()->create([
                    'type' => AccountLedgerEntry::TYPE_CASH_IN,
                    'amount' => $data['amount'],
                    'entry_date' => $data['collected_on'],
                    'flat_id' => $statement->flat_id,
                    'collection_id' => $collection->id,
                    'category' => 'collection',
                    'note' => $data['note'] ?? ('Collection – '.$statement->flat?->name),
                ]);
            }

            \App\Support\Auditor::log('collection.created', $collection, [
                'flat' => $statement->flat?->name,
                'amount' => $data['amount'],
                'posted_to_ledger' => $postToLedger,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);
        });

        return redirect()
            ->route('admin.collections.index', [
                'month' => Carbon::parse($statement->bill_month)->format('Y-m'),
            ])
            ->with('success', 'Collection recorded for '.$statement->flat?->name.'.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $month = Carbon::parse($collection->statement->bill_month)->format('Y-m');

        DB::transaction(function () use ($collection) {
            AccountLedgerEntry::query()
                ->where('collection_id', $collection->id)
                ->delete();
            $collection->delete();
        });

        return redirect()
            ->route('admin.collections.index', ['month' => $month])
            ->with('success', 'Collection removed.');
    }

    private function resolveMonth(?string $month): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return BillMonth::parse($month);
        }

        $latest = MonthlyStatement::query()->max('bill_month');
        if ($latest) {
            return BillMonth::parse(Carbon::parse($latest)->toDateString());
        }

        return BillMonth::parse(null);
    }
}
