<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BillType;
use App\Models\Flat;
use App\Models\MonthlyStatement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlatController extends Controller
{
    public function show(Request $request, Flat $flat): View
    {
        $selectedMonth = $this->resolveMonth($request->query('month'));

        $availableMonths = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->orderByDesc('bill_month')
            ->pluck('bill_month')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->values();

        if ($availableMonths->isNotEmpty() && ! $availableMonths->contains($selectedMonth->format('Y-m'))) {
            // Keep requested month even if empty (shows empty state); default current is fine.
        }

        $statement = $flat->statementForMonth($selectedMonth->toDateString());

        $billTypes = BillType::query()->ordered()->get();

        $summary = [];
        foreach ($billTypes as $type) {
            $line = $statement?->lines->firstWhere('bill_type_key', $type->key);
            $summary[] = [
                'key' => $type->key,
                'label' => $type->label,
                'amount' => $line && $line->enabled ? (float) $line->amount : 0.0,
            ];
        }

        $total = collect($summary)->sum('amount');

        return view('public.flats.show', [
            'flat' => $flat,
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $availableMonths,
            'statement' => $statement,
            'summary' => $summary,
            'total' => $total,
        ]);
    }

    private function resolveMonth(?string $month): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
