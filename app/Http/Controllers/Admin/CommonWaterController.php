<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommonMeterReading;
use App\Models\Flat;
use App\Support\WaterShareCalculator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CommonWaterController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->query('month'));
        $monthKey = $month->toDateString();

        $reading = CommonMeterReading::query()
            ->where('meter_key', 'water')
            ->whereDate('bill_month', $monthKey)
            ->first();

        $enabledCount = Flat::query()
            ->with('billTypeSettings.billType')
            ->get()
            ->filter(fn (Flat $flat) => $flat->isBillTypeEnabled('water'))
            ->count();

        $share = null;
        $shareError = null;
        if ($reading) {
            try {
                $share = WaterShareCalculator::share($reading->total_amount, $enabledCount);
            } catch (InvalidArgumentException $e) {
                $shareError = $e->getMessage();
            }
        }

        return view('admin.water.index', [
            'selectedMonth' => $month,
            'reading' => $reading,
            'enabledCount' => $enabledCount,
            'share' => $share,
            'shareError' => $shareError,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $month = $this->resolveMonth($request->input('bill_month'));

        $data = $request->validate([
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'previous_reading' => ['nullable', 'numeric', 'min:0'],
            'current_reading' => ['nullable', 'numeric', 'min:0'],
            'reading_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $enabledCount = Flat::query()
            ->with('billTypeSettings.billType')
            ->get()
            ->filter(fn (Flat $flat) => $flat->isBillTypeEnabled('water'))
            ->count();

        if ($enabledCount === 0) {
            return back()->withErrors([
                'total_amount' => 'Cannot save water bill: no water-enabled flats.',
            ])->withInput();
        }

        $existing = CommonMeterReading::query()
            ->where('meter_key', 'water')
            ->whereDate('bill_month', $month->toDateString())
            ->first();

        if ($existing) {
            $existing->update([
                'total_amount' => $data['total_amount'],
                'previous_reading' => $data['previous_reading'] ?? null,
                'current_reading' => $data['current_reading'] ?? null,
                'reading_date' => $data['reading_date'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        } else {
            CommonMeterReading::query()->create([
                'meter_key' => 'water',
                'bill_month' => $month->toDateString(),
                'total_amount' => $data['total_amount'],
                'previous_reading' => $data['previous_reading'] ?? null,
                'current_reading' => $data['current_reading'] ?? null,
                'reading_date' => $data['reading_date'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        }

        \App\Support\Auditor::log('water.saved', null, [
            'bill_month' => $month->toDateString(),
            'total_amount' => $data['total_amount'],
            'enabled_flats' => $enabledCount,
        ]);

        return redirect()
            ->route('admin.water.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Common water bill saved. Run Generate month to apply shares to statements.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $month = $this->resolveMonth($request->input('month') ?? $request->query('month'));

        CommonMeterReading::query()
            ->where('meter_key', 'water')
            ->whereDate('bill_month', $month->toDateString())
            ->delete();

        return redirect()
            ->route('admin.water.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Common water entry removed for this month.');
    }

    private function resolveMonth(?string $month): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        if ($month && preg_match('/^\d{4}-\d{2}-\d{2}$/', $month)) {
            return Carbon::parse($month)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
