<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillType;
use App\Models\CommonMeterReading;
use App\Models\Flat;
use App\Support\Auditor;
use App\Support\BillMonth;
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
        $flats = Flat::query()->with('billTypeSettings.billType')->get();

        $bills = BillType::activeCommonMeters()->map(function (BillType $type) use ($monthKey, $flats) {
            $reading = CommonMeterReading::query()
                ->where('meter_key', $type->key)
                ->whereDate('bill_month', $monthKey)
                ->first();

            $enabledCount = $flats->filter(
                fn (Flat $flat) => $flat->isBillTypeEnabled($type->key)
            )->count();

            $share = null;
            $shareError = null;
            if ($reading) {
                try {
                    $share = WaterShareCalculator::share(
                        $reading->total_amount,
                        $enabledCount,
                        $type->label
                    );
                } catch (InvalidArgumentException $e) {
                    $shareError = $e->getMessage();
                }
            }

            return [
                'type' => $type,
                'reading' => $reading,
                'enabled_count' => $enabledCount,
                'share' => $share,
                'share_error' => $shareError,
            ];
        });

        return view('admin.water.index', [
            'selectedMonth' => $month,
            'bills' => $bills,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $month = $this->resolveMonth($request->input('bill_month'));

        $data = $request->validate([
            'meter_key' => ['required', 'string', 'exists:bill_types,key'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'previous_reading' => ['nullable', 'numeric', 'min:0'],
            'current_reading' => ['nullable', 'numeric', 'min:0'],
            'reading_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $billType = BillType::query()
            ->where('key', $data['meter_key'])
            ->where('nature', BillType::NATURE_METER_COMMON)
            ->first();

        if (! $billType) {
            return back()->withErrors([
                'meter_key' => 'Invalid water bill type.',
            ])->withInput();
        }

        $enabledCount = Flat::query()
            ->with('billTypeSettings.billType')
            ->get()
            ->filter(fn (Flat $flat) => $flat->isBillTypeEnabled($billType->key))
            ->count();

        if ($enabledCount === 0) {
            return back()->withErrors([
                'total_amount' => "Cannot save {$billType->label}: no enabled flats. Enable at least one flat under bill type settings.",
            ])->withInput();
        }

        $existing = CommonMeterReading::query()
            ->where('meter_key', $billType->key)
            ->whereDate('bill_month', $month->toDateString())
            ->first();

        // Plain common bills (e.g. deep tube-well) only store month + amount (+ note).
        $usesMeterReadings = $billType->usesMeterReadings();

        $payload = [
            'total_amount' => $data['total_amount'],
            'previous_reading' => $usesMeterReadings ? ($data['previous_reading'] ?? null) : null,
            'current_reading' => $usesMeterReadings ? ($data['current_reading'] ?? null) : null,
            'reading_date' => $usesMeterReadings ? ($data['reading_date'] ?? null) : null,
            'note' => $data['note'] ?? null,
        ];

        if ($existing) {
            $existing->update($payload);
        } else {
            CommonMeterReading::query()->create($payload + [
                'meter_key' => $billType->key,
                'bill_month' => $month->toDateString(),
            ]);
        }

        Auditor::log('water.saved', null, [
            'meter_key' => $billType->key,
            'bill_month' => $month->toDateString(),
            'total_amount' => $data['total_amount'],
            'enabled_flats' => $enabledCount,
        ]);

        return redirect()
            ->route('admin.water.index', ['month' => $month->format('Y-m')])
            ->with('success', $billType->label.' saved. Run Generate month to apply equal shares to enabled flats.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $month = $this->resolveMonth($request->input('month') ?? $request->query('month'));

        $data = $request->validate([
            'meter_key' => ['required', 'string', 'exists:bill_types,key'],
        ]);

        $billType = BillType::query()
            ->where('key', $data['meter_key'])
            ->where('nature', BillType::NATURE_METER_COMMON)
            ->first();

        if (! $billType) {
            return back()->withErrors(['meter_key' => 'Invalid water bill type.']);
        }

        CommonMeterReading::query()
            ->where('meter_key', $billType->key)
            ->whereDate('bill_month', $month->toDateString())
            ->delete();

        return redirect()
            ->route('admin.water.index', ['month' => $month->format('Y-m')])
            ->with('success', $billType->label.' entry removed for this month.');
    }

    private function resolveMonth(?string $month): Carbon
    {
        return BillMonth::parse($month);
    }
}
