<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyStatement;
use App\Models\User;
use App\Services\FcmPushService;
use App\Services\MonthGenerateReadiness;
use App\Services\MonthStatementGenerator;
use App\Support\Auditor;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthGenerateController extends Controller
{
    public function index(Request $request, MonthGenerateReadiness $readiness): View
    {
        $month = $this->resolveMonth($request->query('month'));

        $existingCount = MonthlyStatement::query()
            ->whereDate('bill_month', $month->toDateString())
            ->count();

        return view('admin.generate.index', [
            'selectedMonth' => $month,
            'existingCount' => $existingCount,
            'defaultPricePerKg' => 148,
            'readiness' => $readiness->forMonth($month),
        ]);
    }

    public function store(Request $request, MonthStatementGenerator $generator, FcmPushService $fcm): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'price_per_kg' => ['required', 'numeric', 'min:0'],
        ]);

        $month = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $stats = $generator->generate($month, (float) $data['price_per_kg']);

        Auditor::log('month.generate', null, [
            'month' => $month->toDateString(),
            'price_per_kg' => (float) $data['price_per_kg'],
            'stats' => $stats,
        ]);

        if ($fcm->isConfigured()) {
            $admins = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get();
            $title = 'Month generated';
            $body = sprintf('Statements ready for %s.', $month->format('F Y'));
            foreach ($admins as $admin) {
                $fcm->sendToUser($admin, $title, $body, [
                    'type' => 'month.generate',
                    'month' => $month->format('Y-m'),
                ]);
            }
        }

        return redirect()
            ->route('admin.generate.index', ['month' => $month->format('Y-m')])
            ->with('success', sprintf(
                'Generated %d statements (%d gas, %d water, %d other lines). Collections preserved.',
                $stats['statements'],
                $stats['gas_lines'],
                $stats['water_lines'],
                $stats['other_lines']
            ));
    }

    private function resolveMonth(?string $month): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
