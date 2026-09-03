<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Flat;
use App\Models\MonthlyStatement;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function building(Request $request): View
    {
        return view('public.statements.print-building', $this->buildingPrintData($request));
    }

    public function buildingPos(Request $request): View
    {
        return view('public.statements.print-building-pos', $this->buildingPrintData($request));
    }

    /**
     * @return array{
     *     selectedMonth: Carbon,
     *     rows: Collection<int, array<string, mixed>>,
     *     availableMonths: Collection<int, string>,
     *     grandTotal: float
     * }
     */
    private function buildingPrintData(Request $request): array
    {
        $month = $this->resolveMonth($request->query('month'), preferLatest: true);
        $monthKey = $month->toDateString();

        $statements = MonthlyStatement::query()
            ->with(['flat', 'lines'])
            ->whereDate('bill_month', $monthKey)
            ->get()
            ->sortBy(function (MonthlyStatement $statement) {
                $name = $statement->flat?->name ?? '';
                preg_match('/^(\d+)([A-Z])$/i', $name, $m);

                return [isset($m[1]) ? (int) $m[1] : 0, $m[2] ?? $name];
            })
            ->values();

        $rows = $statements->map(function (MonthlyStatement $statement) {
            $gasLine = $statement->gasLine();
            $otherLines = $statement->otherLines()->where('enabled', true)->values();
            $meta = $gasLine?->meta ?? [];

            $gasAmount = $gasLine && $gasLine->enabled ? (float) $gasLine->amount : 0.0;
            $otherAmount = (float) $otherLines->sum(fn ($line) => (float) $line->amount);
            $total = (float) $statement->lines
                ->where('enabled', true)
                ->sum(fn ($line) => (float) $line->amount);

            return [
                'flat_name' => $statement->flat?->name ?? '—',
                'previous_m3' => $gasLine ? (float) ($meta['previous_m3'] ?? 0) : null,
                'current_m3' => $gasLine ? (float) ($meta['current_m3'] ?? 0) : null,
                'consumed_kg' => $gasLine
                    ? (float) ($meta['consumed_kg'] ?? $gasLine->quantity ?? 0)
                    : null,
                'rate_per_kg' => $gasLine
                    ? (float) ($meta['rate_per_kg'] ?? $gasLine->rate ?? 0)
                    : null,
                'gas_amount' => $gasLine ? $gasAmount : null,
                'other_lines' => $otherLines,
                'other_amount' => $otherAmount,
                'total' => $total,
            ];
        });

        $availableMonths = MonthlyStatement::query()
            ->select('bill_month')
            ->distinct()
            ->orderByDesc('bill_month')
            ->pluck('bill_month')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m'));

        return [
            'selectedMonth' => $month,
            'rows' => $rows,
            'availableMonths' => $availableMonths,
            'grandTotal' => (float) $rows->sum('total'),
        ];
    }

    private function resolveMonth(?string $month, bool $preferLatest = false): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return BillMonth::parse($month);
        }

        if ($preferLatest) {
            $latest = MonthlyStatement::query()->max('bill_month');
            if ($latest) {
                return BillMonth::parse(Carbon::parse($latest)->toDateString());
            }
        }

        return BillMonth::parse($month);
    }
}
