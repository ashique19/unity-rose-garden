<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Flat;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StatementPrintController extends Controller
{
    public function show(Request $request, Flat $flat): View
    {
        $month = $this->resolveMonth($request->query('month'));
        $statement = $flat->statementForMonth($month->toDateString());

        if (! $statement) {
            throw new NotFoundHttpException('No statement for this month.');
        }

        $statement->load(['lines', 'collections']);

        return view('public.statements.print', [
            'flat' => $flat,
            'statement' => $statement,
            'selectedMonth' => $month,
            'gasLine' => $statement->gasLine(),
            'otherLines' => $statement->otherLines(),
        ]);
    }

    private function resolveMonth(?string $month): Carbon
    {
        return BillMonth::parse($month);
    }
}
