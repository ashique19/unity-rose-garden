<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedgerEntry;
use App\Models\Building;
use App\Models\MonthlyStatement;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $building = Building::query()->first();

        $opening = (float) ($building?->opening_balance ?? 0);
        $balance = $building?->balance() ?? number_format($opening, 2, '.', '');
        $pending = Building::totalPendingCollections();

        $recentEntries = AccountLedgerEntry::query()
            ->with('flat')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $pendingStatements = MonthlyStatement::query()
            ->with(['flat', 'lines', 'collections'])
            ->get()
            ->filter(fn (MonthlyStatement $s) => (float) $s->pendingAmount() > 0)
            ->sortBy(function (MonthlyStatement $s) {
                $name = $s->flat?->name ?? '';
                preg_match('/^(\d+)([A-Z])$/i', $name, $m);

                return [
                    isset($m[1]) ? (int) $m[1] : 0,
                    $m[2] ?? $name,
                    $s->bill_month?->toDateString() ?? '',
                ];
            })
            ->values();

        $latestBillMonth = MonthlyStatement::query()->max('bill_month');
        $printMonth = $latestBillMonth
            ? \Carbon\Carbon::parse($latestBillMonth)->format('Y-m')
            : now()->format('Y-m');

        return view('admin.dashboard.index', [
            'building' => $building,
            'opening' => $opening,
            'balance' => $balance,
            'pending' => $pending,
            'recentEntries' => $recentEntries,
            'pendingStatements' => $pendingStatements,
            'printMonth' => $printMonth,
        ]);
    }
}
