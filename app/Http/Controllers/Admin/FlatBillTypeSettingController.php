<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillType;
use App\Models\ChargeTemplate;
use App\Models\Flat;
use App\Models\FlatBillTypeSetting;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FlatBillTypeSettingController extends Controller
{
    public function index(): View
    {
        $flats = $this->sortedFlats(
            Flat::query()->with(['billTypeSettings.billType'])->get()
        );

        $billTypes = BillType::query()->ordered()->get();

        return view('admin.flats.bill-type-settings', compact('flats', 'billTypes'));
    }

    public function storeBillType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:bill_types,key'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $key = ! empty($data['key']) ? Str::lower($data['key']) : Str::slug($data['label'], '_');
        if ($key === '') {
            $key = 'bill_'.Str::lower(Str::random(6));
        }

        if (BillType::query()->where('key', $key)->exists()) {
            return back()->withErrors([
                'key' => 'A bill type with key “'.$key.'” already exists.',
            ])->withInput();
        }

        $billType = DB::transaction(function () use ($data, $key) {
            $billType = BillType::query()->create([
                'key' => $key,
                'label' => $data['label'],
                'nature' => BillType::NATURE_OTHER,
                'is_active' => true,
                'sort_order' => ((int) BillType::query()->max('sort_order')) + 10,
            ]);

            $now = now();
            $rows = Flat::query()->pluck('id')->map(fn ($flatId) => [
                'flat_id' => $flatId,
                'bill_type_id' => $billType->id,
                'enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows !== []) {
                FlatBillTypeSetting::query()->insert($rows);
            }

            if (array_key_exists('default_amount', $data) && $data['default_amount'] !== null && $data['default_amount'] !== '') {
                $chargeKey = $key;
                if (ChargeTemplate::query()->where('charge_key', $chargeKey)->exists()) {
                    $chargeKey = $chargeKey.'_'.Str::lower(Str::random(4));
                }

                ChargeTemplate::query()->create([
                    'bill_type_id' => $billType->id,
                    'charge_key' => $chargeKey,
                    'label' => $data['label'],
                    'default_amount' => $data['default_amount'],
                    'is_building_wide' => true,
                ]);
            }

            return $billType;
        });

        Auditor::log('bill_type.created', $billType, [
            'key' => $billType->key,
            'label' => $billType->label,
        ]);

        return redirect()
            ->route('admin.flat-bill-type-settings.index')
            ->with('success', 'Bill type “'.$billType->label.'” added.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'array'],
        ]);

        $enabledMap = $validated['enabled'] ?? [];

        $flats = Flat::query()->get();
        $billTypes = BillType::query()->get();

        foreach ($flats as $flat) {
            foreach ($billTypes as $billType) {
                $key = $flat->id.'_'.$billType->id;
                FlatBillTypeSetting::query()->updateOrCreate(
                    [
                        'flat_id' => $flat->id,
                        'bill_type_id' => $billType->id,
                    ],
                    [
                        'enabled' => isset($enabledMap[$key]),
                    ]
                );
            }
        }

        return back()->with('success', 'Bill type participation updated.');
    }

    private function sortedFlats(Collection $flats): Collection
    {
        return $flats->sortBy(function (Flat $flat) {
            preg_match('/^(\d+)([A-Z])$/i', $flat->name, $m);

            return [isset($m[1]) ? (int) $m[1] : 0, $m[2] ?? $flat->name];
        })->values();
    }
}
