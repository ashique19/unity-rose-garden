<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Flat;
use App\Models\MonthlyStatement;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatementController extends Controller
{
    public function gas(Request $request, Flat $flat): View
    {
        $statement = $this->findStatement($request, $flat);
        $gasLine = $statement?->gasLine();

        return view('public.statements.gas', [
            'flat' => $flat,
            'statement' => $statement,
            'gasLine' => $gasLine,
            'selectedMonth' => $statement
                ? Carbon::parse($statement->bill_month)
                : $this->resolveMonth($request->query('month')),
        ]);
    }

    public function others(Request $request, Flat $flat): View
    {
        $statement = $this->findStatement($request, $flat);
        $otherLines = $statement?->otherLines() ?? collect();

        return view('public.statements.others', [
            'flat' => $flat,
            'statement' => $statement,
            'otherLines' => $otherLines,
            'selectedMonth' => $statement
                ? Carbon::parse($statement->bill_month)
                : $this->resolveMonth($request->query('month')),
        ]);
    }

    private function findStatement(Request $request, Flat $flat): ?MonthlyStatement
    {
        $month = $this->resolveMonth($request->query('month'));

        return $flat->statementForMonth($month->toDateString());
    }

    private function resolveMonth(?string $month): Carbon
    {
        return BillMonth::parse($month);
    }
}
