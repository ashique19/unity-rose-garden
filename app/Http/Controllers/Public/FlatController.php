<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BillType;
use App\Models\Flat;
use App\Models\MonthlyStatement;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlatController extends Controller
{
    public function show(Request $request, Flat $flat): View
    {
        $availableMonths = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->orderByDesc('bill_month')
            ->pluck('bill_month')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->values();

        $monthQuery = $request->query('month');
        if (is_string($monthQuery) && $monthQuery !== '') {
            $selectedMonth = $this->resolveMonth($monthQuery);
        } elseif ($availableMonths->isNotEmpty()) {
            $selectedMonth = $this->resolveMonth($availableMonths->first());
        } else {
            $selectedMonth = $this->resolveMonth(null);
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
        return BillMonth::parse($month);
    }
}
