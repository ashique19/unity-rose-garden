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

        $cashIn = (float) AccountLedgerEntry::query()
            ->where('type', AccountLedgerEntry::TYPE_CASH_IN)
            ->sum('amount');

        $cashOut = (float) AccountLedgerEntry::query()
            ->where('type', AccountLedgerEntry::TYPE_CASH_OUT)
            ->sum('amount');

        $opening = (float) ($building?->opening_balance ?? 0);
        $balance = $building?->balance() ?? number_format($opening + $cashIn - $cashOut, 2, '.', '');
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
            ->sortByDesc(fn (MonthlyStatement $s) => (float) $s->pendingAmount())
            ->take(15)
            ->values();

        return view('admin.dashboard.index', [
            'building' => $building,
            'opening' => $opening,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'balance' => $balance,
            'pending' => $pending,
            'recentEntries' => $recentEntries,
            'pendingStatements' => $pendingStatements,
        ]);
    }
}
